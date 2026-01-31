import { defineStore } from 'pinia'
import { ref } from 'vue'
import { expensesApi } from '@/api/expenses'
import type { Expense } from '@/types'

export const useExpensesStore = defineStore('expenses', () => {
  const expenses = ref<Expense[]>([])
  const currentExpense = ref<Expense | null>(null)
  const loading = ref(false)
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0,
  })

  async function fetchExpenses(filters: {
    group_id?: number
    category_id?: number
    from_date?: string
    to_date?: string
    page?: number
  } = {}) {
    loading.value = true
    try {
      const response = await expensesApi.getAll(filters)
      expenses.value = response.data.data
      pagination.value = {
        currentPage: response.data.current_page,
        lastPage: response.data.last_page,
        total: response.data.total,
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchExpense(id: number) {
    loading.value = true
    try {
      const response = await expensesApi.get(id)
      currentExpense.value = response.data.expense
      return response.data.expense
    } finally {
      loading.value = false
    }
  }

  async function createExpense(data: {
    description: string
    amount: number
    currency?: string
    date?: string
    group_id?: number
    category_id?: number
    split_type: 'equal' | 'percentage' | 'shares' | 'exact'
    splits: Array<{ user_id: number; value: number }>
    notes?: string
  }) {
    const response = await expensesApi.create(data)
    expenses.value.unshift(response.data.expense)
    return response.data.expense
  }

  async function updateExpense(id: number, data: {
    description?: string
    amount?: number
    currency?: string
    date?: string
    category_id?: number
    split_type?: 'equal' | 'percentage' | 'shares' | 'exact'
    splits?: Array<{ user_id: number; value: number }>
    notes?: string
  }) {
    const response = await expensesApi.update(id, data)
    const index = expenses.value.findIndex(e => e.id === id)
    if (index !== -1) {
      expenses.value[index] = response.data.expense
    }
    if (currentExpense.value?.id === id) {
      currentExpense.value = response.data.expense
    }
    return response.data.expense
  }

  async function deleteExpense(id: number) {
    await expensesApi.delete(id)
    expenses.value = expenses.value.filter(e => e.id !== id)
    if (currentExpense.value?.id === id) {
      currentExpense.value = null
    }
  }

  return {
    expenses,
    currentExpense,
    loading,
    pagination,
    fetchExpenses,
    fetchExpense,
    createExpense,
    updateExpense,
    deleteExpense,
  }
})
