import api from './client'
import type { Expense, PaginatedResponse } from '@/types'

interface CreateExpenseData {
  description: string
  amount: number
  currency?: string
  date?: string
  group_id?: number
  category_id?: number
  split_type: 'equal' | 'percentage' | 'shares' | 'exact'
  splits: Array<{ user_id: number; value: number }>
  notes?: string
  is_recurring?: boolean
  recurring_frequency?: 'daily' | 'weekly' | 'monthly' | 'yearly'
}

interface ExpenseFilters {
  group_id?: number
  category_id?: number
  from_date?: string
  to_date?: string
  page?: number
}

export const expensesApi = {
  getAll(filters: ExpenseFilters = {}) {
    const params = new URLSearchParams()
    if (filters.group_id) params.append('group_id', filters.group_id.toString())
    if (filters.category_id) params.append('category_id', filters.category_id.toString())
    if (filters.from_date) params.append('from_date', filters.from_date)
    if (filters.to_date) params.append('to_date', filters.to_date)
    if (filters.page) params.append('page', filters.page.toString())
    return api.get<PaginatedResponse<Expense>>(`/expenses?${params}`)
  },

  get(id: number) {
    return api.get<{ expense: Expense }>(`/expenses/${id}`)
  },

  create(data: CreateExpenseData) {
    return api.post<{ message: string; expense: Expense }>('/expenses', data)
  },

  update(id: number, data: Partial<CreateExpenseData>) {
    return api.put<{ message: string; expense: Expense }>(`/expenses/${id}`, data)
  },

  delete(id: number) {
    return api.delete(`/expenses/${id}`)
  },

  uploadReceipt(id: number, file: File) {
    const formData = new FormData()
    formData.append('receipt', file)
    return api.post(`/expenses/${id}/receipt`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
}
