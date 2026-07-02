<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useSnackbar } from '@/composables/useSnackbar'
import * as apiClientsApi from '@/api/apiClients'
import SecretRevealDialog from '@/components/apiClients/SecretRevealDialog.vue'
import type { ApiClientPayload } from '@/types/api'

const route = useRoute()
const router = useRouter()
const { success } = useSnackbar()
const { loading, error, validationErrors, execute } = useApi()

const isEdit = computed(() => !!route.params.id)
const id = computed(() => Number(route.params.id))
const loadingDetail = ref(false)

const SCOPE_OPTIONS = [
  { title: 'Crear órdenes (orders:write)', value: 'orders:write' },
  { title: 'Leer tickets/catálogo (tickets:read)', value: 'tickets:read' },
]

const form = reactive<ApiClientPayload>({
  name: '',
  client_id: '',
  scopes: [],
  webhook_secret: '',
})

// El backend rechaza un client_id puramente numérico (not_regex /^\d+$/).
const clientIdRules = [
  (v: string) => !!v || 'El client_id es obligatorio',
  (v: string) => !/^\d+$/.test(v) || 'El client_id no puede ser puramente numérico',
]

const revealOpen = ref(false)
const revealedSecret = ref<string | null>(null)

onMounted(async () => {
  if (isEdit.value) {
    loadingDetail.value = true
    const client = await execute(() => apiClientsApi.getApiClient(id.value))
    if (client) {
      form.name = client.name
      form.client_id = client.client_id
      form.scopes = client.scopes ?? []
      // El secret no se devuelve por la API; se deja vacío (no se reescribe salvo que el admin lo cambie).
      form.webhook_secret = ''
    }
    loadingDetail.value = false
  }
})

async function submit() {
  if (isEdit.value) {
    const result = await execute(() => apiClientsApi.updateApiClient(id.value, form))
    if (result) {
      success('Cliente API actualizado')
      router.push('/api-clients')
    }
    return
  }

  const created = await execute(() => apiClientsApi.createApiClient(form))
  if (created) {
    success('Cliente API creado')
    if (created.client_secret) {
      // Mostramos el secret antes de salir: solo aparece esta vez.
      revealedSecret.value = created.client_secret
      revealOpen.value = true
    } else {
      router.push('/api-clients')
    }
  }
}

function onRevealClosed(open: boolean) {
  revealOpen.value = open
  if (!open) {
    revealedSecret.value = null
    router.push('/api-clients')
  }
}
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">{{ isEdit ? 'Editar cliente API' : 'Nuevo cliente API' }}</h1>

    <v-card max-width="720">
      <v-card-text>
        <v-skeleton-loader v-if="loadingDetail" type="article" />
        <v-form v-else @submit.prevent="submit">
          <v-alert v-if="error && !Object.keys(validationErrors).length" type="error" variant="tonal" class="mb-4">
            {{ error }}
          </v-alert>

          <v-text-field
            v-model="form.name"
            label="Nombre *"
            :error-messages="validationErrors['name']"
          />
          <v-text-field
            v-model="form.client_id"
            label="client_id *"
            :rules="clientIdRules"
            hint="Identificador del cliente máquina. No puede ser puramente numérico (ej. usa 'wp-store', no '12345')."
            persistent-hint
            :error-messages="validationErrors['client_id']"
          />
          <v-select
            v-model="form.scopes"
            label="Scopes *"
            :items="SCOPE_OPTIONS"
            multiple
            chips
            :error-messages="validationErrors['scopes']"
          />
          <v-text-field
            v-model="form.webhook_secret"
            label="Webhook secret"
            hint="Secreto HMAC para firmar webhooks de pago. Opcional."
            :error-messages="validationErrors['webhook_secret']"
          />

          <div class="d-flex ga-2 mt-2">
            <v-btn type="submit" color="primary" :loading="loading">Guardar</v-btn>
            <v-btn variant="text" @click="router.push('/api-clients')">Cancelar</v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>

    <SecretRevealDialog
      :model-value="revealOpen"
      :secret="revealedSecret"
      @update:model-value="onRevealClosed"
    />
  </div>
</template>
