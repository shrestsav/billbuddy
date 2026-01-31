import { defineStore } from 'pinia'
import { ref } from 'vue'
import { categoriesApi } from '@/api/categories'
import type { Category } from '@/types'

export const useCategoriesStore = defineStore('categories', () => {
  const categories = ref<Category[]>([])
  const loading = ref(false)

  async function fetchCategories() {
    if (categories.value.length > 0) return

    loading.value = true
    try {
      const response = await categoriesApi.getAll()
      categories.value = response.data.categories
    } finally {
      loading.value = false
    }
  }

  return {
    categories,
    loading,
    fetchCategories,
  }
})
