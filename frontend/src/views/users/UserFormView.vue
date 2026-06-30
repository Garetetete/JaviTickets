<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useSnackbar } from '@/composables/useSnackbar'
import * as usersApi from '@/api/users'
import type { UserPayload } from '@/types/api'

const route = useRoute()
const router = useRouter()
const { success } = useSnackbar()
const { loading, error, validationErrors, execute } = useApi()

const isEdit = computed(() => !!route.params.id)
const id = computed(() => Number(route.params.id))
const loadingDetail = ref(false)

const ROLE_OPTIONS = [
  { title: 'Admin', value: 'admin' },
  { title: 'Puerta (gate)', value: 'gate' },
]

const form = reactive<UserPayload>({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'admin',
  event_id: null,
})

const isGate = computed(() => form.role === 'gate')

onMounted(async () => {
  if (isEdit.value) {
    loadingDetail.value = true
    const user = await execute(() => usersApi.getUser(id.value))
    if (user) {
      form.name = user.name
      form.email = user.email
      form.role = user.role
      form.event_id = user.event_id ?? null
    }
    loadingDetail.value = false
  }
})

async function submit() {
  // Construimos el payload según el modo: sin password en edición; sin event_id si no es gate.
  const payload: UserPayload = {
    name: form.name,
    email: form.email,
    role: form.role,
    event_id: isGate.value ? form.event_id ?? null : null,
  }
  if (!isEdit.value) {
    payload.password = form.password
    payload.password_confirmation = form.password_confirmation
  }

  const result = await execute(() =>
    isEdit.value ? usersApi.updateUser(id.value, payload) : usersApi.createUser(payload),
  )
  if (result) {
    success(isEdit.value ? 'Usuario actualizado' : 'Usuario creado')
    router.push('/users')
  }
}
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">{{ isEdit ? 'Editar usuario' : 'Nuevo usuario' }}</h1>

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
            v-model="form.email"
            label="Email *"
            type="email"
            :error-messages="validationErrors['email']"
          />
          <v-select
            v-model="form.role"
            label="Rol *"
            :items="ROLE_OPTIONS"
            :error-messages="validationErrors['role']"
          />
          <v-text-field
            v-if="isGate"
            v-model.number="form.event_id"
            label="ID de evento asignado"
            type="number"
            hint="Evento al que queda atado el usuario de puerta."
            :error-messages="validationErrors['event_id']"
          />

          <template v-if="!isEdit">
            <v-text-field
              v-model="form.password"
              label="Contraseña *"
              type="password"
              :error-messages="validationErrors['password']"
            />
            <v-text-field
              v-model="form.password_confirmation"
              label="Confirmar contraseña *"
              type="password"
              :error-messages="validationErrors['password_confirmation']"
            />
          </template>

          <div class="d-flex ga-2 mt-2">
            <v-btn type="submit" color="primary" :loading="loading">Guardar</v-btn>
            <v-btn variant="text" @click="router.push('/users')">Cancelar</v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </div>
</template>
