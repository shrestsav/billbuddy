<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { expensesApi } from '@/api/expenses'
import type { Expense } from '@/types'
import {
  CurrencyDollarIcon,
  CalendarIcon,
  TagIcon,
  UserIcon,
  DocumentTextIcon,
  TrashIcon,
  PencilIcon,
  ArrowLeftIcon,
} from '@heroicons/vue/24/outline'
import { format } from 'date-fns'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const expense = ref<Expense | null>(null)
const loading = ref(true)
const error = ref('')

const expenseId = computed(() => Number(route.params.id))

const canEdit = computed(() => {
  if (!expense.value || !authStore.user) return false
  return expense.value.paid_by === authStore.user.id
})

onMounted(async () => {
  try {
    const response = await expensesApi.get(expenseId.value)
    expense.value = response.data.expense
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to load expense'
  } finally {
    loading.value = false
  }
})

async function deleteExpense() {
  if (!confirm('Are you sure you want to delete this expense? This action cannot be undone.')) {
    return
  }
  try {
    await expensesApi.delete(expenseId.value)
    router.push('/expenses')
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to delete expense'
  }
}

function formatCurrency(amount: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: expense.value?.currency || 'USD',
  }).format(amount)
}
</script>

<template>
  <div>
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
    </div>

    <div v-else-if="error" class="text-center py-12">
      <p class="text-red-600">{{ error }}</p>
      <router-link to="/expenses" class="text-teal-600 hover:underline mt-2 inline-block">
        Back to expenses
      </router-link>
    </div>

    <template v-else-if="expense">
      <!-- Header -->
      <div class="mb-6">
        <button
          @click="router.back()"
          class="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeftIcon class="h-5 w-5" />
          Back
        </button>

        <div class="flex items-start justify-between">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-teal-100 rounded-xl">
              <CurrencyDollarIcon class="h-8 w-8 text-teal-600" />
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">{{ expense.description }}</h1>
              <p class="text-3xl font-bold text-teal-600 mt-1">{{ formatCurrency(expense.amount) }}</p>
            </div>
          </div>
          <div v-if="canEdit" class="flex items-center gap-2">
            <button
              @click="deleteExpense"
              class="p-2 text-red-600 hover:bg-red-50 rounded-lg"
              title="Delete expense"
            >
              <TrashIcon class="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>

      <!-- Details -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 divide-y">
        <!-- Paid by -->
        <div class="flex items-center gap-4 p-4">
          <UserIcon class="h-5 w-5 text-gray-400" />
          <div>
            <p class="text-sm text-gray-500">Paid by</p>
            <p class="font-medium text-gray-900">{{ expense.payer?.name }}</p>
          </div>
        </div>

        <!-- Date -->
        <div class="flex items-center gap-4 p-4">
          <CalendarIcon class="h-5 w-5 text-gray-400" />
          <div>
            <p class="text-sm text-gray-500">Date</p>
            <p class="font-medium text-gray-900">{{ format(new Date(expense.date), 'MMMM d, yyyy') }}</p>
          </div>
        </div>

        <!-- Category -->
        <div v-if="expense.category" class="flex items-center gap-4 p-4">
          <TagIcon class="h-5 w-5 text-gray-400" />
          <div>
            <p class="text-sm text-gray-500">Category</p>
            <p class="font-medium text-gray-900">{{ expense.category.name }}</p>
          </div>
        </div>

        <!-- Group -->
        <div v-if="expense.group" class="flex items-center gap-4 p-4">
          <UserIcon class="h-5 w-5 text-gray-400" />
          <div>
            <p class="text-sm text-gray-500">Group</p>
            <router-link
              :to="`/groups/${expense.group.id}`"
              class="font-medium text-teal-600 hover:underline"
            >
              {{ expense.group.name }}
            </router-link>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="expense.notes" class="flex items-start gap-4 p-4">
          <DocumentTextIcon class="h-5 w-5 text-gray-400 mt-0.5" />
          <div>
            <p class="text-sm text-gray-500">Notes</p>
            <p class="font-medium text-gray-900">{{ expense.notes }}</p>
          </div>
        </div>
      </div>

      <!-- Splits -->
      <div class="mt-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Split Details</h2>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 divide-y">
          <div
            v-for="split in expense.splits"
            :key="split.id"
            class="flex items-center justify-between p-4"
          >
            <div class="flex items-center gap-3">
              <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                <span class="font-medium text-gray-600">{{ split.user?.name?.charAt(0) }}</span>
              </div>
              <span class="font-medium text-gray-900">{{ split.user?.name }}</span>
            </div>
            <span class="font-semibold text-gray-900">{{ formatCurrency(split.amount) }}</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
