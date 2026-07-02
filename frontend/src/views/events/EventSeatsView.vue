<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useConfirm } from '@/composables/useConfirm'
import { useSnackbar } from '@/composables/useSnackbar'
import * as eventsApi from '@/api/events'
import * as seatsApi from '@/api/seats'
import type { Seat } from '@/types/domain'
import type { SeatPayload, SeatRangePayload } from '@/types/api'

const route = useRoute()
const router = useRouter()
const { confirm } = useConfirm()
const { success, error } = useSnackbar()

const eventId = computed(() => Number(route.params.id))
const eventName = ref('')
const seats = ref<Seat[]>([])
const loading = ref(false)

// Diálogo "Agregar asiento".
const single = useApi()
const showSingle = ref(false)
const singleForm = reactive<SeatPayload>({ section: '', row: '', seat_number: '' })

// Diálogo "Generar rango".
const range = useApi()
const showRange = ref(false)
const rangeForm = reactive<SeatRangePayload>({
  section: '',
  row_from: '',
  row_to: '',
  seats_per_row: 1,
  prefix: '',
})

/** Color del chip según el estado del asiento. */
function seatColor(seat: Seat): string {
  if (seat.deleted_at) return 'grey'
  return seat.status === 'occupied' ? 'orange' : 'success'
}

function seatLabel(seat: Seat): string {
  if (seat.label) return seat.label
  const parts = [seat.section, seat.row, seat.seat_number].filter(Boolean)
  return parts.join(' · ')
}

async function loadEvent() {
  try {
    const event = await eventsApi.getEvent(eventId.value)
    eventName.value = event.name
  } catch {
    eventName.value = ''
  }
}

async function loadSeats() {
  loading.value = true
  try {
    seats.value = await seatsApi.getSeats(eventId.value)
  } catch {
    error('No se pudieron cargar los asientos')
  } finally {
    loading.value = false
  }
}

function openSingle() {
  singleForm.section = ''
  singleForm.row = ''
  singleForm.seat_number = ''
  single.error.value = null
  single.validationErrors.value = {}
  showSingle.value = true
}

async function submitSingle() {
  const result = await single.execute(() => seatsApi.createSeat(eventId.value, singleForm))
  if (result) {
    success('Asiento agregado')
    showSingle.value = false
    await loadSeats()
  }
}

function openRange() {
  rangeForm.section = ''
  rangeForm.row_from = ''
  rangeForm.row_to = ''
  rangeForm.seats_per_row = 1
  rangeForm.prefix = ''
  range.error.value = null
  range.validationErrors.value = {}
  showRange.value = true
}

async function submitRange() {
  const result = await range.execute(() => seatsApi.generateSeats(eventId.value, rangeForm))
  if (result) {
    success('Asientos generados')
    showRange.value = false
    await loadSeats()
  }
}

async function removeSeat(seat: Seat) {
  const ok = await confirm({
    title: 'Eliminar asiento',
    message: `¿Eliminar el asiento "${seatLabel(seat)}"?`,
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    await seatsApi.deleteSeat(seat.id)
    success('Asiento eliminado')
    await loadSeats()
  } catch {
    error('No se pudo eliminar el asiento')
  }
}

onMounted(async () => {
  await Promise.all([loadEvent(), loadSeats()])
})
</script>

<template>
  <div>
    <div class="d-flex align-center flex-wrap ga-3 mb-4">
      <v-btn icon="mdi-arrow-left" variant="text" @click="router.push('/events')" />
      <h1 class="text-h5">Asientos{{ eventName ? ` — ${eventName}` : '' }}</h1>
      <v-spacer />
      <v-btn color="primary" prepend-icon="mdi-seat-outline" @click="openSingle">
        Agregar asiento
      </v-btn>
      <v-btn color="primary" variant="tonal" prepend-icon="mdi-table-large" @click="openRange">
        Generar rango
      </v-btn>
    </div>

    <v-card>
      <v-card-text>
        <div v-if="loading" class="d-flex justify-center pa-8">
          <v-progress-circular indeterminate color="primary" />
        </div>
        <div v-else-if="!seats.length" class="text-medium-emphasis text-center pa-8">
          No hay asientos. Usa "Agregar asiento" o "Generar rango" para crearlos.
        </div>
        <div v-else>
          <div class="d-flex flex-wrap ga-4 mb-4">
            <div class="d-flex align-center ga-1">
              <v-icon icon="mdi-circle" color="success" size="small" /> Libre
            </div>
            <div class="d-flex align-center ga-1">
              <v-icon icon="mdi-circle" color="orange" size="small" /> Ocupado
            </div>
            <div class="d-flex align-center ga-1">
              <v-icon icon="mdi-circle" color="grey" size="small" /> Eliminado
            </div>
          </div>

          <div class="d-flex flex-wrap ga-2">
            <v-chip
              v-for="seat in seats"
              :key="seat.id"
              :color="seatColor(seat)"
              label
              variant="flat"
            >
              {{ seatLabel(seat) }}
              <template v-if="!seat.deleted_at">
                <v-tooltip
                  v-if="seat.status === 'occupied'"
                  text="No se puede eliminar un asiento ocupado"
                  location="top"
                >
                  <template #activator="{ props: tooltipProps }">
                    <span v-bind="tooltipProps" class="ml-1">
                      <v-btn icon="mdi-delete" size="x-small" variant="text" disabled />
                    </span>
                  </template>
                </v-tooltip>
                <v-btn
                  v-else
                  icon="mdi-delete"
                  size="x-small"
                  variant="text"
                  class="ml-1"
                  @click="removeSeat(seat)"
                />
              </template>
            </v-chip>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <!-- Diálogo: agregar asiento -->
    <v-dialog v-model="showSingle" max-width="480">
      <v-card>
        <v-card-title>Agregar asiento</v-card-title>
        <v-card-text>
          <v-alert
            v-if="single.error.value && !Object.keys(single.validationErrors.value).length"
            type="error"
            variant="tonal"
            class="mb-4"
          >
            {{ single.error.value }}
          </v-alert>
          <v-form @submit.prevent="submitSingle">
            <v-text-field
              v-model="singleForm.section"
              label="Sección *"
              :error-messages="single.validationErrors.value['section']"
            />
            <v-text-field
              v-model="singleForm.row"
              label="Fila"
              :error-messages="single.validationErrors.value['row']"
            />
            <v-text-field
              v-model="singleForm.seat_number"
              label="Número de asiento *"
              :error-messages="single.validationErrors.value['seat_number']"
            />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showSingle = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="single.loading.value" @click="submitSingle">Guardar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Diálogo: generar rango -->
    <v-dialog v-model="showRange" max-width="480">
      <v-card>
        <v-card-title>Generar rango de asientos</v-card-title>
        <v-card-text>
          <v-alert
            v-if="range.error.value && !Object.keys(range.validationErrors.value).length"
            type="error"
            variant="tonal"
            class="mb-4"
          >
            {{ range.error.value }}
          </v-alert>
          <v-form @submit.prevent="submitRange">
            <v-text-field
              v-model="rangeForm.section"
              label="Sección *"
              :error-messages="range.validationErrors.value['section']"
            />
            <v-row>
              <v-col cols="6">
                <v-text-field
                  v-model="rangeForm.row_from"
                  label="Fila desde *"
                  :error-messages="range.validationErrors.value['row_from']"
                />
              </v-col>
              <v-col cols="6">
                <v-text-field
                  v-model="rangeForm.row_to"
                  label="Fila hasta *"
                  :error-messages="range.validationErrors.value['row_to']"
                />
              </v-col>
            </v-row>
            <v-text-field
              v-model.number="rangeForm.seats_per_row"
              label="Asientos por fila *"
              type="number"
              :error-messages="range.validationErrors.value['seats_per_row']"
            />
            <v-text-field
              v-model="rangeForm.prefix"
              label="Prefijo"
              :error-messages="range.validationErrors.value['prefix']"
            />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showRange = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="range.loading.value" @click="submitRange">Generar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
