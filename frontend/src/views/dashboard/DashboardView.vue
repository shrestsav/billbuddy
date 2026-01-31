<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useGroupsStore } from '@/stores/groups'
import { settlementsApi } from '@/api/settlements'
import { activityApi } from '@/api/activity'
import type { Balance, ActivityLog } from '@/types'
import {
  ArrowUpIcon,
  ArrowDownIcon,
  UserGroupIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline'
import { formatDistanceToNow } from 'date-fns'

const authStore = useAuthStore()
const groupsStore = useGroupsStore()

const balances = ref<Balance[]>([])
const totalOwed = ref(0)
const totalOwing = ref(0)
const netBalance = ref(0)
const activities = ref<ActivityLog[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    await groupsStore.fetchGroups()

    const [balanceRes, activityRes] = await Promise.all([
      settlementsApi.getBalances(),
      activityApi.getAll({ limit: 10 }),
    ])

    balances.value = balanceRes.data.balances
    totalOwed.value = balanceRes.data.total_owed
    totalOwing.value = balanceRes.data.total_owing
    netBalance.value = balanceRes.data.net_balance
    activities.value = activityRes.data.data
  } finally {
    loading.value = false
  }
})

const balanceColor = computed(() => {
  if (netBalance.value > 0) return 'text-green-600'
  if (netBalance.value < 0) return 'text-red-600'
  return 'text-gray-600'
})

function formatCurrency(amount: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: authStore.user?.currency_preference || 'USD',
  }).format(amount)
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">
        Welcome back, {{ authStore.user?.name?.split(' ')[0] }}!
      </h1>
      <p class="text-gray-600">Here's your expense summary</p>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
    </div>

    <template v-else>
      <!-- Balance Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-2 bg-green-100 rounded-lg">
              <ArrowDownIcon class="h-5 w-5 text-green-600" />
            </div>
            <span class="text-sm text-gray-600">You are owed</span>
          </div>
          <p class="text-2xl font-bold text-green-600">{{ formatCurrency(totalOwed) }}</p>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-2 bg-red-100 rounded-lg">
              <ArrowUpIcon class="h-5 w-5 text-red-600" />
            </div>
            <span class="text-sm text-gray-600">You owe</span>
          </div>
          <p class="text-2xl font-bold text-red-600">{{ formatCurrency(totalOwing) }}</p>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-2 bg-gray-100 rounded-lg">
              <span class="text-lg">$</span>
            </div>
            <span class="text-sm text-gray-600">Net balance</span>
          </div>
          <p class="text-2xl font-bold" :class="balanceColor">
            {{ formatCurrency(Math.abs(netBalance)) }}
            <span v-if="netBalance !== 0" class="text-sm font-normal">
              {{ netBalance > 0 ? 'in your favor' : 'to pay' }}
            </span>
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Groups -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Your Groups</h2>
            <router-link
              to="/groups/new"
              class="flex items-center gap-1 text-sm text-teal-600 hover:text-teal-700"
            >
              <PlusIcon class="h-4 w-4" />
              New Group
            </router-link>
          </div>
          <div class="p-4">
            <div v-if="groupsStore.groups.length === 0" class="text-center py-6 text-gray-500">
              <UserGroupIcon class="h-12 w-12 mx-auto text-gray-300 mb-2" />
              <p>No groups yet</p>
              <router-link to="/groups/new" class="text-teal-600 hover:underline">
                Create your first group
              </router-link>
            </div>
            <ul v-else class="divide-y divide-gray-100">
              <li v-for="group in groupsStore.groups.slice(0, 5)" :key="group.id">
                <router-link
                  :to="`/groups/${group.id}`"
                  class="flex items-center gap-3 py-3 hover:bg-gray-50 -mx-4 px-4 rounded-lg"
                >
                  <div class="p-2 bg-teal-100 rounded-lg">
                    <UserGroupIcon class="h-5 w-5 text-teal-600" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 truncate">{{ group.name }}</p>
                    <p class="text-sm text-gray-500">
                      {{ group.members_count }} members
                    </p>
                  </div>
                </router-link>
              </li>
            </ul>
            <router-link
              v-if="groupsStore.groups.length > 5"
              to="/groups"
              class="block text-center text-sm text-teal-600 hover:underline mt-4"
            >
              View all groups
            </router-link>
          </div>
        </div>

        <!-- Balances -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Balances</h2>
            <router-link to="/settlements" class="text-sm text-teal-600 hover:text-teal-700">
              Settle up
            </router-link>
          </div>
          <div class="p-4">
            <div v-if="balances.length === 0" class="text-center py-6 text-gray-500">
              <p>All settled up!</p>
            </div>
            <ul v-else class="divide-y divide-gray-100">
              <li
                v-for="balance in balances.slice(0, 5)"
                :key="balance.user.id"
                class="flex items-center justify-between py-3"
              >
                <div class="flex items-center gap-3">
                  <img
                    v-if="balance.user.avatar"
                    :src="balance.user.avatar"
                    class="h-8 w-8 rounded-full"
                  />
                  <div
                    v-else
                    class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center"
                  >
                    <span class="text-sm font-medium text-gray-600">
                      {{ balance.user.name.charAt(0) }}
                    </span>
                  </div>
                  <span class="font-medium text-gray-900">{{ balance.user.name }}</span>
                </div>
                <span
                  :class="[
                    'font-medium',
                    balance.direction === 'owed_to_you' ? 'text-green-600' : 'text-red-600',
                  ]"
                >
                  {{ balance.direction === 'owed_to_you' ? '+' : '-' }}{{ formatCurrency(balance.amount) }}
                </span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
        </div>
        <div class="p-4">
          <div v-if="activities.length === 0" class="text-center py-6 text-gray-500">
            <p>No recent activity</p>
          </div>
          <ul v-else class="divide-y divide-gray-100">
            <li v-for="activity in activities" :key="activity.id" class="py-3">
              <div class="flex items-start gap-3">
                <div class="flex-1">
                  <p class="text-sm text-gray-900">
                    <span class="font-medium">{{ activity.user?.name }}</span>
                    {{ activity.description }}
                  </p>
                  <p class="text-xs text-gray-500 mt-1">
                    {{ formatDistanceToNow(new Date(activity.created_at), { addSuffix: true }) }}
                    <span v-if="activity.group"> in {{ activity.group.name }}</span>
                  </p>
                </div>
              </div>
            </li>
          </ul>
          <router-link
            to="/activity"
            class="block text-center text-sm text-teal-600 hover:underline mt-4"
          >
            View all activity
          </router-link>
        </div>
      </div>
    </template>
  </div>
</template>
