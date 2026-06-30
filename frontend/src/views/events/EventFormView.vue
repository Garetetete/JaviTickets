<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useSnackbar } from '@/composables/useSnackbar'
import * as eventsApi from '@/api/events'
import * as toursApi from '@/api/tours'
import type { EventPayload } from '@/types/api'
import type { Tour } from '@/types/domain'

const route = useRoute()
const router = useRouter()
const { success } = useSnackbar()
const { loading, error, validationErrors, execute } = useApi()

const isEdit = computed(() => !!route.params.id)
const id = computed(() => Number(route.params.id))
const loadingDetail = ref(false)

const tours = ref<Tour[]>([])
const loadingTours = ref(false)

const form = reactive<EventPayload>({
  tour_id: null,
  slug: '',
  name: '',
  country: '',
  city: '',
  venue: '',
  event_date: '',
  capacity: null,
  seating_type: 'general',
  is_active: true,
})

/** La API devuelve la fecha en ISO; el input datetime-local necesita "YYYY-MM-DDTHH:mm". */
function toLocalInput(value: string | null | undefined): string {
  if (!value) return ''
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return ''
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

watch(
  () => form.seating_type,
  (type) => {
    if (type === 'seated') form.capacity = null
  },
)

onMounted(async () => {
  loadingTours.value = true
  const res = await execute(() => toursApi.getTours({ per_page: 100 }))
  if (res) tours.value = res.data
  loadingTours.value = false

  if (isEdit.value) {
    loadingDetail.value = true
    const event = await execute(() => eventsApi.getEvent(id.value))
    if (event) {
      form.tour_id = event.tour_id
      form.slug = event.slug ?? ''
      form.name = event.name
      form.country = event.country ?? ''
      form.city = event.city ?? ''
      form.venue = event.venue ?? ''
      form.event_date = toLocalInput(event.event_date)
      form.capacity = event.capacity ?? null
      form.seating_type = event.seating_type
      form.is_active = event.is_active ?? true
    }
    loadingDetail.value = false
  }
})

async function submit() {
  const payload: EventPayload = { ...form, event_date: form.event_date || null }
  const result = await execute(() =>
    isEdit.value ? eventsApi.updateEvent(id.value, payload) : eventsApi.createEvent(payload),
  )
  if (result) {
    success(isEdit.value ? 'Evento actualizado' : 'Evento creado')
    router.push('/events')
  }
}
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">{{ isEdit ? 'Editar evento' : 'Nuevo evento' }}</h1>

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
            :loading="loadingTours"
            :error-messages="validationErrors['tour_id']"
          />
          <v-text-field
            v-model="form.name"
            label="Nombre *"
            :error-messages="validationErrors['name']"
          />
          <v-text-field
            v-model="form.slug"
            label="Slug *"
            hint="Identificador URL único, ej. bogota-2026"
            :error-messages="validationErrors['slug']"
          />
          <v-text-field
            v-model="form.venue"
            label="Recinto / Venue"
            :error-messages="validationErrors['venue']"
          />
          <div class="d-flex ga-3">
            <v-text-field
              v-model="form.country"
              label="País"
              :error-messages="validationErrors['country']"
            />
            <v-text-field
              v-model="form.city"
              label="Ciudad"
              :error-messages="validationErrors['city']"
            />
          </div>
          <v-text-field
            v-model="form.event_date"
            label="Fecha y hora"
            type="datetime-local"
            :error-messages="validationErrors['event_date']"
          />

          <v-radio-group
            v-model="form.seating_type"
            label="Tipo de asientos"
            inline
            :error-messages="validationErrors['seating_type']"
          >
            <v-radio label="General" value="general" />
            <v-radio label="Numerado" value="seated" />
          </v-radio-group>

          <v-tooltip
            v-if="form.seating_type === 'seated'"
            text="La capacidad se define por los asientos"
            location="top"
          >
            <template #activator="{ props: tooltipProps }">
              <div v-bind="tooltipProps">
                <v-text-field
                  v-model.number="form.capacity"
                  label="Capacidad"
                  type="number"
                  disabled
                  :error-messages="validationErrors['capacity']"
                />
              </div>
            </template>
          </v-tooltip>
          <v-text-field
            v-else
            v-model.number="form.capacity"
            label="Capacidad *"
            type="number"
            :error-messages="validationErrors['capacity']"
          />

          <v-switch v-model="form.is_active" label="Activo" color="primary" />

          <div class="d-flex ga-2 mt-2">
            <v-btn type="submit" color="primary" :loading="loading">Guardar</v-btn>
            <v-btn variant="text" @click="router.push('/events')">Cancelar</v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </div>
</template>
