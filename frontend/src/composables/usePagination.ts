import { ref } from 'vue'
import type { PaginationParams } from '@/types/domain'

export interface SortItem {
  key: string
  order: 'asc' | 'desc'
}

/** Estado de paginación compatible con v-data-table-server. */
export function usePagination(initialPerPage = 20) {
  const page = ref(1)
  const itemsPerPage = ref(initialPerPage)
  const sortBy = ref<SortItem[]>([])

  function toParams(): PaginationParams {
    const params: PaginationParams = {
      page: page.value,
      per_page: itemsPerPage.value,
    }
    if (sortBy.value.length > 0) {
      params.sort_by = sortBy.value[0].key
      params.sort_dir = sortBy.value[0].order
    }
    return params
  }

  /** Sincroniza desde el evento update:options de v-data-table-server. */
  function setFromOptions(opts: { page: number; itemsPerPage: number; sortBy: SortItem[] }) {
    page.value = opts.page
    itemsPerPage.value = opts.itemsPerPage
    sortBy.value = opts.sortBy ?? []
  }

  return { page, itemsPerPage, sortBy, toParams, setFromOptions }
}
