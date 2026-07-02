<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useSnackbar } from '@/composables/useSnackbar'
import * as ticketTypesApi from '@/api/ticketTypes'
import * as eventsApi from '@/api/events'
import * as toursApi from '@/api/tours'
import type { TicketTypePayload } from '@/types/api'
import type { Event, Tour } from '@/types/domain'

const route = useRoute()
const router = useRouter()
const { success } = useSnackbar()
const { loading, error, validationErrors, execute } = useApi()

const isEdit = computed(() => !!route.params.id)
const id = computed(() => Number(route.params.id))
const loadingDetail = ref(false)

const events = ref<Event[]>([])
const tours = ref<Tour[]>([])
const loadingEvents = ref(false)

const CURRENCIES = ['USD', 'PEN', 'EUR', 'COP', 'MXN']

const form = reactive<TicketTypePayload>({
  tour_id: null,
  event_id: null,
  slug: '',
  name: '',
  price: 0,
  currency: 'USD',
  quota: null,
})

/** Cupo: cadena vacía en el input -> null (sin límite). */
const quotaModel = computed<number | null>({
  get: () => form.quota,
  set: (v) => {
    form.quota = v === null || (v as unknown as string) === '' || Number.isNaN(v) ? null : Number(v)
  },
})

onMounted(async () => {
  loadingEvents.value = true
  const [evRes, tRes] = await Promise.all([
    execute(() => eventsApi.getEvents({ per_page: 100 })),
    execute(() => toursApi.getTours({ per_page: 100 })),
  ])
  if (evRes) events.value = evRes.data
  if (tRes) tours.value = tRes.data
  loadingEvents.value = false

  if (isEdit.value) {
    loadingDetail.value = true
    const tt = await execute(() => ticketTypesApi.getTicketType(id.value))
    if (tt) {
      form.tour_id = tt.tour_id ?? null
      form.event_id = tt.event_id ?? null
      form.slug = tt.slug ?? ''
      form.name = tt.name
      form.price = tt.price
      form.currency = tt.currency
      form.quota = tt.quota ?? null
    }
    loadingDetail.value = false
  }
})

async function submit() {
  const result = await execute(() =>
    isEdit.value ? ticketTypesApi.updateTicketType(id.value, form) : ticketTypesApi.createTicketType(form),
  )
  if (result) {
    success(isEdit.value ? 'Tipo de ticket actualizado' : 'Tipo de ticket creado')
    router.push('/ticket-types')
  }
}
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">{{ isEdit ? 'Editar tipo de ticket' : 'Nuevo tipo de ticket' }}</h1>

    <v-card max-width="720">
      <v-card-text>
        <v-skeleton-loader v-if="loadingDetail" type="article" />
        <v-form v-else @submit.prevent="submit">
          <v-alert v-if="error && !Object.keys(validationErrors).length" type="error" variant="tonal" class="mb-4">
            {{ error }}
          </v-alert>

          <v-select
            v-model="form.tour_id"
            label="Tour *"
            :items="tours"
            item-title="name"
            item-value="id"
            :loading="loadingEvents"
            :error-messages="validationErrors['tour_id']"
          />
          <v-select
            v-model="form.event_id"
            label="Evento (opcional)"
            :items="events"
            item-title="name"
            item-value="id"
            :loading="loadingEvents"
            clearable
            :error-messages="validationErrors['event_id']"
          />
          <v-text-field
            v-model="form.name"
            label="Nombre *"
            :error-messages="validationErrors['name']"
          />
          <v-text-field
            v-model="form.slug"
            label="Slug *"
            hint="Identificador único, ej. vip, general"
            :error-messages="validationErrors['slug']"
          />
          <v-text-field
            v-model.number="form.price"
            label="Precio *"
            type="number"
            :error-messages="validationErrors['price']"
          />
          <v-select
            v-model="form.currency"
            label="Moneda *"
            :items="CURRENCIES"
            :error-messages="validationErrors['currency']"
          />
          <v-text-field
            v-model.number="quotaModel"
            label="Cupo"
            type="number"
            hint="Déjalo vacío para cupo sin límite"
            persistent-hint
            :error-messages="validationErrors['quota']"
          />

          <div class="d-flex ga-2 mt-4">
            <v-btn type="submit" color="primary" :loading="loading">Guardar</v-btn>
            <v-btn variant="text" @click="router.push('/ticket-types')">Cancelar</v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </div>
</template>
