<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { validateTicket } from '@/api/validate'
import { getEvents } from '@/api/events'
import { useAuthStore } from '@/stores/auth'
import { VALIDATE_REASON_LABELS } from '@/constants/status'
import type { ValidateResult } from '@/types/api'
import type { Event } from '@/types/domain'

const auth = useAuthStore()

const events = ref<Event[]>([])
// El gate está atado a su evento; el admin elige uno.
const selectedEvent = ref<number | null>(auth.user?.event_id ?? null)
const qrToken = ref('')
const loading = ref(false)
const result = ref<ValidateResult | null>(null)
const tokenField = ref<HTMLInputElement | null>(null)
const history = ref<Array<{ code: string; result: string; at: string }>>([])
let clearTimer: ReturnType<typeof setTimeout> | undefined

const canValidate = computed(() => !!selectedEvent.value && !!qrToken.value.trim())

const isOk = computed(() => result.value?.result === 'valid')
const isWarn = computed(() => result.value?.result === 'already_used')
const alertColor = computed(() => (isOk.value ? 'success' : isWarn.value ? 'warning' : 'error'))
const reasonLabel = computed(() =>
  result.value ? (VALIDATE_REASON_LABELS[result.value.result] ?? result.value.result) : '',
)

onMounted(async () => {
  if (auth.isAdmin) {
    const res = await getEvents({ per_page: 100 })
    events.value = res.data
    if (!selectedEvent.value && events.value.length) selectedEvent.value = events.value[0].id
  }
  focusInput()
})

function focusInput() {
  nextTick(() => tokenField.value?.focus())
}

async function validate() {
  if (!canValidate.value || loading.value) return
  loading.value = true
  try {
    const res = await validateTicket(qrToken.value.trim(), selectedEvent.value as number)
    result.value = res
    history.value.unshift({
      code: res.ticket?.code ?? qrToken.value.trim().slice(0, 12),
      result: res.result,
      at: new Date().toLocaleTimeString(),
    })
    if (history.value.length > 10) history.value.pop()
  } catch {
    result.value = { result: 'invalid', ticket: null }
  } finally {
    loading.value = false
    qrToken.value = ''
    focusInput()
    if (clearTimer) clearTimeout(clearTimer)
    clearTimer = setTimeout(() => (result.value = null), 5000)
  }
}
</script>

<template>
  <div>
    <v-card class="mb-4">
      <v-card-text>
        <v-select
          v-if="auth.isAdmin"
          v-model="selectedEvent"
          :items="events"
          item-title="name"
          item-value="id"
          label="Evento a validar"
          hide-details
          class="mb-4"
        />
        <div v-else class="text-body-2 text-medium-emphasis mb-2">
          Evento asignado: <strong>#{{ selectedEvent ?? '—' }}</strong>
        </div>

        <v-text-field
          ref="tokenField"
          v-model="qrToken"
          label="QR token (escanear o pegar)"
          prepend-inner-icon="mdi-qrcode"
          autofocus
          clearable
          @keyup.enter="validate"
        />
        <v-btn
          color="primary"
          size="x-large"
          block
          :loading="loading"
          :disabled="!canValidate"
          @click="validate"
        >
          Validar
        </v-btn>
      </v-card-text>
    </v-card>

    <!-- Resultado visual grande, perceptible a distancia -->
    <v-alert
      v-if="result"
      :color="alertColor"
      variant="flat"
      prominent
      class="text-center py-8"
    >
      <div class="text-h3 font-weight-bold mb-2">
        <v-icon :icon="isOk ? 'mdi-check-circle' : isWarn ? 'mdi-alert' : 'mdi-close-circle'" size="56" />
      </div>
      <div v-if="isOk" class="text-h4 font-weight-bold">VÁLIDO</div>
      <div v-else class="text-h4 font-weight-bold">{{ reasonLabel.toUpperCase() }}</div>
      <div v-if="result.ticket" class="text-h6 mt-3">
        {{ result.ticket.holder_name ?? '—' }}
      </div>
      <div v-if="result.ticket" class="text-body-1">
        {{ result.ticket.ticket_type ?? '' }}
        <template v-if="result.ticket.seat"> · Asiento {{ result.ticket.seat }}</template>
      </div>
      <div v-if="isWarn && result.ticket?.used_at" class="text-body-2 mt-2">
        Último uso: {{ result.ticket.used_at }}
      </div>
    </v-alert>

    <!-- Historial de la sesión -->
    <v-card v-if="history.length" class="mt-4" variant="tonal">
      <v-card-title class="text-subtitle-2">Últimos escaneos</v-card-title>
      <v-list density="compact">
        <v-list-item v-for="(h, i) in history" :key="i" :title="h.code" :subtitle="h.at">
          <template #append>
            <v-chip
              size="small"
              :color="h.result === 'valid' ? 'success' : h.result === 'already_used' ? 'warning' : 'error'"
            >
              {{ VALIDATE_REASON_LABELS[h.result] ?? h.result }}
            </v-chip>
          </template>
        </v-list-item>
      </v-list>
    </v-card>
  </div>
</template>
