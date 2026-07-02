<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({ email: '', password: '' })
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref<string | null>(null)

async function submit() {
  errorMessage.value = null
  loading.value = true
  try {
    const user = await auth.login(form.email, form.password)
    const redirect = route.query.redirect as string | undefined
    if (redirect) router.push(redirect)
    else router.push(user.role === 'gate' ? '/validate' : '/dashboard')
  } catch (e: unknown) {
    const status = (e as { response?: { status?: number } })?.response?.status
    errorMessage.value =
      status === 401 ? 'Credenciales incorrectas.' : 'No se pudo iniciar sesión. Intenta de nuevo.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center" class="fill-height">
      <v-col cols="12" sm="8" md="5" lg="4">
        <v-card class="pa-2" elevation="4">
          <v-card-item class="text-center pt-6">
            <v-icon icon="mdi-qrcode" size="48" color="primary" />
            <v-card-title class="text-h5 mt-2">QR Ticketing</v-card-title>
            <v-card-subtitle>Panel de administración</v-card-subtitle>
          </v-card-item>

          <v-card-text>
            <v-alert
              v-if="errorMessage"
              type="error"
              variant="tonal"
              density="compact"
              class="mb-4"
            >
              {{ errorMessage }}
            </v-alert>

            <v-form @submit.prevent="submit">
              <v-text-field
                v-model="form.email"
                label="Email"
                type="email"
                prepend-inner-icon="mdi-email-outline"
                autocomplete="username"
                required
              />
              <v-text-field
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                label="Contraseña"
                prepend-inner-icon="mdi-lock-outline"
                :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                autocomplete="current-password"
                required
                @click:append-inner="showPassword = !showPassword"
              />
              <v-btn
                type="submit"
                color="primary"
                size="large"
                block
                :loading="loading"
                class="mt-2"
              >
                Ingresar
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>
