import api from './client'
import type { Category } from '@/types'

export const categoriesApi = {
  getAll() {
    return api.get<{ categories: Category[] }>('/categories')
  },
}
