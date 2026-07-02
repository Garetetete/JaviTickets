<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import GateLayout from '@/layouts/GateLayout.vue'
import AppSnackbar from '@/components/common/AppSnackbar.vue'
import AppConfirmDialog from '@/components/common/AppConfirmDialog.vue'

const route = useRoute()
const layout = computed(() => route.meta.layout as 'admin' | 'gate' | undefined)
</script>

<template>
  <v-app>
    <AdminLayout v-if="layout === 'admin'">
      <router-view />
    </AdminLayout>
    <GateLayout v-else-if="layout === 'gate'">
      <router-view />
    </GateLayout>
    <v-main v-else>
      <router-view />
    </v-main>

    <!-- Overlays globales -->
    <AppSnackbar />
    <AppConfirmDialog />
  </v-app>
</template>
