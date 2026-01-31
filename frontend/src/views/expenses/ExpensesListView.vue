<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useExpensesStore } from '@/stores/expenses'
import { useCategoriesStore } from '@/stores/categories'
import { useAuthStore } from '@/stores/auth'
import { CurrencyDollarIcon, PlusIcon, FunnelIcon } from '@heroicons/vue/24/outline'
import { format } from 'date-fns'

const route = useRoute()
const expensesStore = useExpensesStore()
const categoriesStore = useCategoriesStore()
const authStore = useAuthStore()

const showFilters = ref(false)
const filters = ref({
  group_id: route.query.group ? Number(route.query.group) : undefined,
  category_id: undefined as number | undefined,
  from_date: '',
  to_date: '',
})

onMounted(() => {
  expensesStore.fetchExpenses(filters.value)
  categoriesStore.fetchCategories()
})

watch(filters, () => {
  expensesStore.fetchExpenses(filters.value)
}, { deep: true })

function formatCurrency(amount: number, currency: string) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || authStore.user?.currency_preference || 'USD',
  }).format(amount)
}

function loadMore() {
  if (expensesStore.pagination.currentPage < expensesStore.pagination.lastPage) {
    expensesStore.fetchExpenses({
      ...filters.value,
      page: expensesStore.pagination.currentPage + 1,
    })
  }
}
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Expenses</h1>
        <p class="text-gray-600">Track and manage your expenses</p>
      </div>
      <div class="flex items-center gap-2">
        <button
          @click="showFilters = !showFilters"
          :class="[
            'p-2 rounded-lg border',
            showFilters ? 'bg-teal-50 border-teal-200 text-teal-600' : 'border-gray-200 text-gray-600 hover:bg-gray-50',
          ]"
        >
          <FunnelIcon class="h-5 w-5" />
        </button>
        <router-link
          to="/expenses/new"
          class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700"
        >
          <PlusIcon class="h-5 w-5" />
          Add Expense
        </router-link>
      </div>
    </div>

    <!-- Filters -->
    <div v-if="showFilters" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
          <select
            v-model="filters.category_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
          >
            <option :value="undefined">All categories</option>
            <option v-for="cat in categoriesStore.categories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">From date</label>
          <input
            v-model="filters.from_date"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">To date</label>
          <input
            v-model="filters.to_date"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
          />
        </div>
        <div class="flex items-end">
          <button
            @click="filters = { group_id: undefined, category_id: undefined, from_date: '', to_date: '' }"
            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"
          >
            Clear filters
          </button>
        </div>
      </div>
    </div>

    <div v-if="expensesStore.loading && expensesStore.expenses.length === 0" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
    </div>

    <div v-else-if="expensesStore.expenses.length === 0" class="text-center py-12">
      <CurrencyDollarIcon class="h-16 w-16 mx-auto text-gray-300 mb-4" />
      <h3 class="text-lg font-medium text-gray-900 mb-2">No expenses yet</h3>
      <p class="text-gray-500 mb-4">Start tracking your shared expenses</p>
      <router-link
        to="/expenses/new"
        class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700"
      >
        <PlusIcon class="h-5 w-5" />
        Add your first expense
      </router-link>
    </div>

    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 divide-y">
      <router-link
        v-for="expense in expensesStore.expenses"
        :key="expense.id"
        :to="`/expenses/${expense.id}`"
        class="flex items-center justify-between p-4 hover:bg-gray-50"
      >
        <div class="flex items-center gap-4">
          <div
            class="p-2 rounded-lg"
            :style="{ backgroundColor: expense.category?.color + '20' || '#f3f4f6' }"
          >
            <CurrencyDollarIcon
              class="h-5 w-5"
              :style="{ color: expense.category?.color || '#6b7280' }"
            />
          </div>
          <div>
            <p class="font-medium text-gray-900">{{ expense.description }}</p>
            <p class="text-sm text-gray-500">
              {{ expense.payer?.name }} paid on {{ format(new Date(expense.date), 'MMM d, yyyy') }}
              <span v-if="expense.group" class="text-teal-600">in {{ expense.group.name }}</span>
            </p>
          </div>
        </div>
        <p class="font-semibold text-gray-900">
          {{ formatCurrency(expense.amount, expense.currency) }}
        </p>
      </router-link>
    </div>

    <div
      v-if="expensesStore.pagination.currentPage < expensesStore.pagination.lastPage"
      class="mt-4 text-center"
    >
      <button
        @click="loadMore"
        :disabled="expensesStore.loading"
        class="px-4 py-2 text-teal-600 hover:bg-teal-50 rounded-lg disabled:opacity-50"
      >
        {{ expensesStore.loading ? 'Loading...' : 'Load more' }}
      </button>
    </div>
  </div>
</template>
