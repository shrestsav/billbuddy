<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useGroupsStore } from '@/stores/groups'
import { useAuthStore } from '@/stores/auth'
import { groupsApi } from '@/api/groups'
import type { Expense, Balance } from '@/types'
import {
  UserGroupIcon,
  CurrencyDollarIcon,
  PlusIcon,
  Cog6ToothIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import { format } from 'date-fns'

const route = useRoute()
const router = useRouter()
const groupsStore = useGroupsStore()
const authStore = useAuthStore()

const expenses = ref<Expense[]>([])
const balances = ref<Balance[]>([])
const loading = ref(true)
const activeTab = ref<'expenses' | 'balances' | 'members'>('expenses')

interface GroupBalanceResponse {
  user: { id: number; name: string }
  balances: Balance[]
  total_owed: number
  total_owing: number
}

const groupId = computed(() => Number(route.params.id))
const group = computed(() => groupsStore.currentGroup)
const isAdmin = computed(() => {
  if (!group.value || !authStore.user) return false
  return group.value.created_by === authStore.user.id
})

onMounted(async () => {
  try {
    await groupsStore.fetchGroup(groupId.value)
    const [expensesRes, balancesRes] = await Promise.all([
      groupsApi.getExpenses(groupId.value),
      groupsApi.getBalances(groupId.value),
    ])
    expenses.value = expensesRes.data.data

    // Extract current user's balances from the nested structure
    const allBalances = balancesRes.data.balances as unknown as GroupBalanceResponse[]
    const currentUserBalance = allBalances.find(b => b.user.id === authStore.user?.id)
    balances.value = currentUserBalance?.balances || []
  } finally {
    loading.value = false
  }
})

async function deleteGroup() {
  if (!confirm('Are you sure you want to delete this group? This action cannot be undone.')) {
    return
  }
  await groupsStore.deleteGroup(groupId.value)
  router.push('/groups')
}

function formatCurrency(amount: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: group.value?.currency || 'USD',
  }).format(amount)
}
</script>

<template>
  <div>
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
    </div>

    <template v-else-if="group">
      <div class="mb-6">
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-teal-100 rounded-xl">
              <UserGroupIcon class="h-8 w-8 text-teal-600" />
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">{{ group.name }}</h1>
              <p v-if="group.description" class="text-gray-600">{{ group.description }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <router-link
              :to="`/expenses/new?group=${group.id}`"
              class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700"
            >
              <PlusIcon class="h-5 w-5" />
              Add Expense
            </router-link>
            <button
              v-if="isAdmin"
              @click="deleteGroup"
              class="p-2 text-red-600 hover:bg-red-50 rounded-lg"
              title="Delete group"
            >
              <TrashIcon class="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-6">
          <button
            v-for="tab in ['expenses', 'balances', 'members'] as const"
            :key="tab"
            @click="activeTab = tab"
            :class="[
              'py-3 px-1 border-b-2 text-sm font-medium capitalize',
              activeTab === tab
                ? 'border-teal-500 text-teal-600'
                : 'border-transparent text-gray-500 hover:text-gray-700',
            ]"
          >
            {{ tab }}
          </button>
        </nav>
      </div>

      <!-- Expenses Tab -->
      <div v-if="activeTab === 'expenses'">
        <div v-if="expenses.length === 0" class="text-center py-12">
          <CurrencyDollarIcon class="h-12 w-12 mx-auto text-gray-300 mb-3" />
          <p class="text-gray-500">No expenses yet</p>
          <router-link
            :to="`/expenses/new?group=${group.id}`"
            class="text-teal-600 hover:underline"
          >
            Add the first expense
          </router-link>
        </div>

        <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 divide-y">
          <router-link
            v-for="expense in expenses"
            :key="expense.id"
            :to="`/expenses/${expense.id}`"
            class="flex items-center justify-between p-4 hover:bg-gray-50"
          >
            <div class="flex items-center gap-4">
              <div class="p-2 bg-gray-100 rounded-lg">
                <CurrencyDollarIcon class="h-5 w-5 text-gray-600" />
              </div>
              <div>
                <p class="font-medium text-gray-900">{{ expense.description }}</p>
                <p class="text-sm text-gray-500">
                  Paid by {{ expense.payer?.name }} on {{ format(new Date(expense.date), 'MMM d, yyyy') }}
                </p>
              </div>
            </div>
            <p class="font-semibold text-gray-900">{{ formatCurrency(expense.amount) }}</p>
          </router-link>
        </div>
      </div>

      <!-- Balances Tab -->
      <div v-else-if="activeTab === 'balances'">
        <div v-if="balances.length === 0" class="text-center py-12">
          <p class="text-gray-500">All settled up!</p>
        </div>

        <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 divide-y">
          <div
            v-for="balance in balances"
            :key="balance.user.id"
            class="flex items-center justify-between p-4"
          >
            <div class="flex items-center gap-3">
              <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                <span class="font-medium text-gray-600">{{ balance.user.name.charAt(0) }}</span>
              </div>
              <span class="font-medium text-gray-900">{{ balance.user.name }}</span>
            </div>
            <span
              :class="[
                'font-semibold',
                balance.direction === 'owed_to_you' ? 'text-green-600' : 'text-red-600',
              ]"
            >
              {{ balance.direction === 'owed_to_you' ? 'owes you' : 'you owe' }}
              {{ formatCurrency(balance.amount) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Members Tab -->
      <div v-else-if="activeTab === 'members'">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 divide-y">
          <div
            v-for="member in group.members"
            :key="member.id"
            class="flex items-center justify-between p-4"
          >
            <div class="flex items-center gap-3">
              <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                <span class="font-medium text-gray-600">{{ member.name.charAt(0) }}</span>
              </div>
              <div>
                <p class="font-medium text-gray-900">{{ member.name }}</p>
                <p class="text-sm text-gray-500">{{ member.email }}</p>
              </div>
            </div>
            <span
              v-if="member.id === group.created_by"
              class="text-xs bg-teal-100 text-teal-700 px-2 py-1 rounded-full"
            >
              Admin
            </span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
