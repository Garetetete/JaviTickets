<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const drawer = ref(true)

const nav = [
  { title: 'Dashboard', icon: 'mdi-view-dashboard', to: '/dashboard' },
  { title: 'Tours', icon: 'mdi-music', to: '/tours' },
  { title: 'Eventos', icon: 'mdi-calendar-star', to: '/events' },
  { title: 'Tipos de Ticket', icon: 'mdi-ticket-confirmation', to: '/ticket-types' },
  { title: 'Órdenes', icon: 'mdi-cart', to: '/orders' },
  { title: 'Tickets', icon: 'mdi-ticket', to: '/tickets' },
  { title: 'Escaneos', icon: 'mdi-qrcode-scan', to: '/scans' },
  { title: 'Logs de auditoría', icon: 'mdi-history', to: '/audit-logs' },
  { title: 'API Clients', icon: 'mdi-key-variant', to: '/api-clients' },
  { title: 'Usuarios', icon: 'mdi-account-group', to: '/users' },
]

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div>
    <v-navigation-drawer v-model="drawer" :permanent="$vuetify.display.mdAndUp">
      <div class="pa-4 text-h6 font-weight-bold text-primary">QR Ticketing</div>
      <v-divider />
      <v-list nav density="comfortable">
        <v-list-item
          v-for="item in nav"
          :key="item.to"
          :to="item.to"
          :prepend-icon="item.icon"
          :title="item.title"
          color="primary"
        />
      </v-list>
    </v-navigation-drawer>

    <v-app-bar flat border>
      <v-app-bar-nav-icon @click="drawer = !drawer" />
      <v-app-bar-title>Panel de administración</v-app-bar-title>
      <v-spacer />
      <span class="text-body-2 mr-3 d-none d-sm-flex">{{ auth.user?.name }}</span>
      <v-btn icon="mdi-logout" variant="text" @click="logout" />
    </v-app-bar>

    <v-main>
      <v-container fluid class="pa-4 pa-md-6">
        <slot />
      </v-container>
    </v-main>
  </div>
</template>
