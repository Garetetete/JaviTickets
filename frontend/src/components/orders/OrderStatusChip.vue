<script setup lang="ts">
import AppStatusChip from '@/components/common/AppStatusChip.vue'
import { ORDER_STATUS_MAP } from '@/constants/status'

defineProps<{ value: string | null | undefined }>()
</script>

<template>
  <AppStatusChip :value="value" :map="ORDER_STATUS_MAP" />
</template>
