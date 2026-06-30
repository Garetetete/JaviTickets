<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useSnackbar } from '@/composables/useSnackbar'
import * as toursApi from '@/api/tours'
import type { TourPayload } from '@/types/api'

const route = useRoute()
const router = useRouter()
const { success } = useSnackbar()
const { loading, error, validationErrors, execute } = useApi()

const isEdit = computed(() => !!route.params.id)
const id = computed(() => Number(route.params.id))
const loadingDetail = ref(false)

const form = reactive<TourPayload>({
  name: '',
  artist_name: '',
  description: '',
  owner_name: '',
  owner_email: '',
  is_active: true,
})

onMounted(async () => {
  if (isEdit.value) {
    loadingDetail.value = true
    const tour = await execute(() => toursApi.getTour(id.value))
    if (tour) {
      form.name = tour.name
      form.artist_name = tour.artist_name ?? ''
      form.description = tour.description ?? ''
      form.owner_name = tour.owner_name ?? ''
      form.owner_email = tour.owner_email ?? ''
      form.is_active = tour.is_active ?? true
    }
    loadingDetail.value = false
  }
})

async function submit() {
  const result = await execute(() =>
    isEdit.value ? toursApi.updateTour(id.value, form) : toursApi.createTour(form),
  )
  if (result) {
    success(isEdit.value ? 'Tour actualizado' : 'Tour creado')
    router.push('/tours')
  }
}
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">{{ isEdit ? 'Editar tour' : 'Nuevo tour' }}</h1>

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
            v-model="form.artist_name"
            label="Artista"
            :error-messages="validationErrors['artist_name']"
          />
          <v-textarea
            v-model="form.description"
            label="Descripción"
            rows="3"
            :error-messages="validationErrors['description']"
          />
          <v-text-field
            v-model="form.owner_name"
            label="Nombre del responsable"
            :error-messages="validationErrors['owner_name']"
          />
          <v-text-field
            v-model="form.owner_email"
            label="Email del responsable"
            :error-messages="validationErrors['owner_email']"
          />
          <v-switch v-model="form.is_active" label="Activo" color="primary" />

          <div class="d-flex ga-2 mt-2">
            <v-btn type="submit" color="primary" :loading="loading">Guardar</v-btn>
            <v-btn variant="text" @click="router.push('/tours')">Cancelar</v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </div>
</template>
