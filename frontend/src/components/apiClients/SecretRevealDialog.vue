<script setup lang="ts">
import { ref, watch } from 'vue'
import { useConfirm } from '@/composables/useConfirm'
import { useSnackbar } from '@/composables/useSnackbar'

const props = defineProps<{
  modelValue: boolean
  secret: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const { confirm } = useConfirm()
const { success, error } = useSnackbar()

// Marca si el usuario ya copió el secret en la sesión actual del dialog.
const copied = ref(false)

watch(
  () => props.modelValue,
  (open) => {
    if (open) copied.value = false
  },
)

async function copy() {
  if (!props.secret) return
  try {
    await navigator.clipboard.writeText(props.secret)
    copied.value = true
    success('Copiado al portapapeles')
  } catch {
    error('No se pudo copiar al portapapeles')
  }
}

/** Cierra definitivamente y limpia el secret de memoria. */
function close() {
  emit('update:modelValue', false)
}

async function requestClose() {
  if (!copied.value) {
    const ok = await confirm({
      title: 'Cerrar sin copiar',
      message: '¿Seguro? No podrás ver este secret de nuevo.',
      confirmText: 'Cerrar de todos modos',
      cancelText: 'Volver',
    })
    if (!ok) return
  }
  close()
}
</script>

<template>
  <v-dialog
    :model-value="modelValue"
    max-width="560"
    persistent
    @update:model-value="(v) => { if (!v) requestClose() }"
  >
    <v-card>
      <v-card-title class="text-h6">Client secret generado</v-card-title>
      <v-card-text>
        <v-alert type="warning" variant="tonal" class="mb-4">
          <strong class="text-error">Este secret NO se volverá a mostrar.</strong>
          Cópialo y guárdalo en un lugar seguro ahora.
        </v-alert>

        <v-text-field
          :model-value="secret ?? ''"
          label="client_secret"
          readonly
          variant="outlined"
          append-inner-icon="mdi-content-copy"
          @click:append-inner="copy"
        />

        <v-btn color="primary" prepend-icon="mdi-content-copy" variant="tonal" @click="copy">
          Copiar al portapapeles
        </v-btn>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="requestClose">Cerrar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
