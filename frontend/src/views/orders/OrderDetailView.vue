<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOrdersStore } from '@/stores/orders'
import { useConfirm } from '@/composables/useConfirm'
import { useSnackbar } from '@/composables/useSnackbar'
import * as ordersApi from '@/api/orders'
import OrderStatusChip from '@/components/orders/OrderStatusChip.vue'
import TicketStatusChip from '@/components/tickets/TicketStatusChip.vue'
import type { PaymentReceipt, Ticket } from '@/types/domain'

const route = useRoute()
const router = useRouter()
const store = useOrdersStore()
const { confirm } = useConfirm()
const { success, error } = useSnackbar()

const orderId = Number(route.params.id)

const order = computed(() => store.item)
const receipts = ref<PaymentReceipt[]>([])
const receiptsLoading = ref(false)
const downloadingId = ref<number | null>(null)
const acting = ref(false)

const rejectDialog = ref(false)
const rejectReason = ref('')

const canAct = computed(() => {
  const status = order.value?.payment_status
  return status === 'pending_payment' || status === 'pending_verification'
})

const showTickets = computed(() => order.value?.payment_status === 'verified')

const ticketHeaders = [
  { title: 'Código', key: 'code' },
  { title: 'Estado', key: 'status', sortable: false },
  { title: 'Sección', key: 'section' },
  { title: 'Asiento', key: 'seat' },
]

function formatDate(value?: string | null) {
  if (!value) return '—'
  return new Date(value).toLocaleString('es')
}

async function loadOrder() {
  try {
    await store.fetchOne(orderId)
  } catch {
    error('No se pudo cargar la orden')
  }
}

async function loadReceipts() {
  receiptsLoading.value = true
  try {
    receipts.value = await ordersApi.getReceipts(orderId)
  } catch {
    receipts.value = []
  } finally {
    receiptsLoading.value = false
  }
}

async function verifyPayment() {
  const ok = await confirm({
    title: 'Verificar pago',
    message: '¿Confirmas que el pago de esta orden es válido? Se emitirán los tickets.',
    confirmText: 'Verificar',
    color: 'success',
  })
  if (!ok) return
  acting.value = true
  try {
    await store.verify(orderId)
    success('Pago verificado y tickets emitidos')
  } catch {
    error('No se pudo verificar el pago')
  } finally {
    acting.value = false
  }
}

function openReject() {
  rejectReason.value = ''
  rejectDialog.value = true
}

async function confirmReject() {
  if (!rejectReason.value.trim()) return
  acting.value = true
  try {
    await store.reject(orderId, rejectReason.value.trim())
    success('Orden rechazada')
    rejectDialog.value = false
  } catch {
    error('No se pudo rechazar la orden')
  } finally {
    acting.value = false
  }
}

async function download(receipt: PaymentReceipt) {
  downloadingId.value = receipt.id
  try {
    const res = await ordersApi.downloadReceipt(orderId, receipt.id)
    const blob = new Blob([res.data], {
      type: receipt.mime_type ?? 'application/octet-stream',
    })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = receipt.original_name ?? `desprendible-${receipt.id}`
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(url)
  } catch {
    error('No se pudo descargar el desprendible')
  } finally {
    downloadingId.value = null
  }
}

onMounted(() => {
  loadOrder()
  loadReceipts()
})
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <v-btn icon="mdi-arrow-left" variant="text" @click="router.push('/orders')" />
      <h1 class="text-h5 ml-2">Orden #{{ orderId }}</h1>
    </div>

    <template v-if="order">
      <!-- 1. Encabezado -->
      <v-card class="mb-4">
        <v-card-text>
          <div class="d-flex align-center flex-wrap ga-6">
            <div>
              <div class="text-caption text-medium-emphasis">ID</div>
              <div>{{ order.id }}</div>
            </div>
            <div>
              <div class="text-caption text-medium-emphasis">Ref. externa</div>
              <div>{{ order.external_reference }}</div>
            </div>
            <div>
              <div class="text-caption text-medium-emphasis">Estado</div>
              <OrderStatusChip :value="order.payment_status" />
            </div>
            <div>
              <div class="text-caption text-medium-emphasis">Monto</div>
              <div>{{ Number(order.amount).toFixed(2) }} {{ order.currency }}</div>
            </div>
            <div>
              <div class="text-caption text-medium-emphasis">Fecha</div>
              <div>{{ formatDate(order.created_at) }}</div>
            </div>
            <v-spacer />
            <div v-if="canAct" class="d-flex ga-2">
              <v-btn
                color="success"
                prepend-icon="mdi-check"
                :loading="acting"
                @click="verifyPayment"
              >
                Verificar pago
              </v-btn>
              <v-btn
                color="error"
                variant="outlined"
                prepend-icon="mdi-close"
                :disabled="acting"
                @click="openReject"
              >
                Rechazar
              </v-btn>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <!-- 2. Datos del cliente -->
      <v-card class="mb-4">
        <v-card-title>Datos del cliente</v-card-title>
        <v-card-text>
          <v-row dense>
            <v-col cols="12" sm="6" md="4">
              <div class="text-caption text-medium-emphasis">Nombre</div>
              <div>{{ order.customer?.full_name ?? '—' }}</div>
            </v-col>
            <v-col cols="12" sm="6" md="4">
              <div class="text-caption text-medium-emphasis">Email</div>
              <div>{{ order.customer?.email ?? '—' }}</div>
            </v-col>
            <v-col cols="12" sm="6" md="4">
              <div class="text-caption text-medium-emphasis">Teléfono</div>
              <div>{{ order.customer?.phone ?? '—' }}</div>
            </v-col>
            <v-col cols="12" sm="6" md="4">
              <div class="text-caption text-medium-emphasis">Tipo de documento</div>
              <div>{{ order.customer?.document_type ?? '—' }}</div>
            </v-col>
            <v-col cols="12" sm="6" md="4">
              <div class="text-caption text-medium-emphasis">Número de documento</div>
              <div>{{ order.customer?.document_number ?? '—' }}</div>
            </v-col>
            <v-col cols="12" sm="6" md="4">
              <div class="text-caption text-medium-emphasis">Dirección</div>
              <div>{{ order.customer?.address ?? '—' }}</div>
            </v-col>
            <v-col cols="12" sm="6" md="4">
              <div class="text-caption text-medium-emphasis">Ciudad de residencia</div>
              <div>{{ order.customer?.city_residence ?? '—' }}</div>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- 3. Desprendibles -->
      <v-card class="mb-4">
        <v-card-title>Desprendibles de pago</v-card-title>
        <v-card-text>
          <v-progress-linear v-if="receiptsLoading" indeterminate />
          <v-list v-else-if="receipts.length" lines="two">
            <v-list-item v-for="r in receipts" :key="r.id">
              <v-list-item-title>{{ r.original_name ?? `Desprendible #${r.id}` }}</v-list-item-title>
              <v-list-item-subtitle>
                {{ r.mime_type ?? 'archivo' }} · {{ formatDate(r.created_at) }}
              </v-list-item-subtitle>
              <template #append>
                <v-btn
                  variant="text"
                  prepend-icon="mdi-download"
                  :loading="downloadingId === r.id"
                  @click="download(r)"
                >
                  Descargar
                </v-btn>
              </template>
            </v-list-item>
          </v-list>
          <div v-else class="text-medium-emphasis">No hay desprendibles cargados.</div>
        </v-card-text>
      </v-card>

      <!-- 4. Tickets emitidos -->
      <v-card v-if="showTickets" class="mb-4">
        <v-card-title>Tickets emitidos</v-card-title>
        <v-card-text>
          <v-data-table
            :headers="ticketHeaders"
            :items="(order.tickets ?? [])"
            :items-per-page="-1"
            hide-default-footer
          >
            <template #[`item.status`]="{ item }">
              <TicketStatusChip :value="(item as Ticket).status" />
            </template>
            <template #[`item.section`]="{ item }">
              {{ (item as Ticket).section ?? '—' }}
            </template>
            <template #[`item.seat`]="{ item }">
              {{ (item as Ticket).seat ?? '—' }}
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>
    </template>

    <v-progress-linear v-else-if="store.loading" indeterminate />

    <!-- Dialog rechazar -->
    <v-dialog v-model="rejectDialog" max-width="480">
      <v-card>
        <v-card-title>Rechazar orden</v-card-title>
        <v-card-text>
          <v-textarea
            v-model="rejectReason"
            label="Motivo del rechazo"
            :rules="[(v: string) => !!v?.trim() || 'El motivo es requerido']"
            rows="3"
            autofocus
            required
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" :disabled="acting" @click="rejectDialog = false">Cancelar</v-btn>
          <v-btn
            color="error"
            :loading="acting"
            :disabled="!rejectReason.trim()"
            @click="confirmReject"
          >
            Rechazar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
