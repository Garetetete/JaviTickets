<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div>
    <v-app-bar flat color="primary">
      <v-app-bar-title>
        <v-icon icon="mdi-qrcode-scan" start /> Validación en puerta
      </v-app-bar-title>
      <v-spacer />
      <span class="text-body-2 mr-3 d-none d-sm-flex">{{ auth.user?.name }}</span>
      <v-btn icon="mdi-logout" variant="text" @click="logout" />
    </v-app-bar>

    <v-main>
      <v-container class="d-flex justify-center pa-4">
        <div style="width: 100%; max-width: 720px">
          <slot />
        </div>
      </v-container>
    </v-main>
  </div>
</template>
