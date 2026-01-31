<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useExpensesStore } from '@/stores/expenses'
import { useGroupsStore } from '@/stores/groups'
import { useFriendsStore } from '@/stores/friends'
import { useCategoriesStore } from '@/stores/categories'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const expensesStore = useExpensesStore()
const groupsStore = useGroupsStore()
const friendsStore = useFriendsStore()
const categoriesStore = useCategoriesStore()
const authStore = useAuthStore()

const description = ref('')
const amount = ref<number | null>(null)
const date = ref(new Date().toISOString().split('T')[0])
const groupId = ref<number | undefined>(route.query.group ? Number(route.query.group) : undefined)
const categoryId = ref<number | undefined>()
const splitType = ref<'equal' | 'percentage' | 'shares' | 'exact'>('equal')
const notes = ref('')
const selectedUsers = ref<number[]>([])
const splitValues = ref<Record<number, number>>({})
const loading = ref(false)
const error = ref('')

onMounted(async () => {
  await Promise.all([
    groupsStore.fetchGroups(),
    friendsStore.fetchFriends(),
    categoriesStore.fetchCategories(),
  ])

  if (authStore.user) {
    selectedUsers.value = [authStore.user.id]
  }

  if (groupId.value) {
    await groupsStore.fetchGroup(groupId.value)
    if (groupsStore.currentGroup?.members) {
      selectedUsers.value = groupsStore.currentGroup.members.map(m => m.id)
    }
  }
})

const availableUsers = computed(() => {
  if (groupId.value && groupsStore.currentGroup?.members) {
    return groupsStore.currentGroup.members
  }

  const users = [...friendsStore.friends]
  if (authStore.user && !users.find(u => u.id === authStore.user!.id)) {
    users.unshift(authStore.user)
  }
  return users
})

const splits = computed(() => {
  if (!amount.value || selectedUsers.value.length === 0) return []

  switch (splitType.value) {
    case 'equal':
      const equalAmount = amount.value / selectedUsers.value.length
      return selectedUsers.value.map(userId => ({
        user_id: userId,
        value: 1,
        displayAmount: equalAmount,
      }))

    case 'percentage':
    case 'shares':
    case 'exact':
      return selectedUsers.value.map(userId => ({
        user_id: userId,
        value: splitValues.value[userId] || 0,
        displayAmount: splitType.value === 'exact'
          ? splitValues.value[userId] || 0
          : splitType.value === 'percentage'
            ? (amount.value! * (splitValues.value[userId] || 0)) / 100
            : (amount.value! * (splitValues.value[userId] || 0)) / totalShares.value,
      }))
  }
})

const totalShares = computed(() => {
  return Object.values(splitValues.value).reduce((sum, val) => sum + (val || 0), 0)
})

const totalPercentage = computed(() => {
  return Object.values(splitValues.value).reduce((sum, val) => sum + (val || 0), 0)
})

const isValid = computed(() => {
  if (!description.value || !amount.value || selectedUsers.value.length === 0) return false

  if (splitType.value === 'percentage' && Math.abs(totalPercentage.value - 100) > 0.01) {
    return false
  }

  if (splitType.value === 'exact') {
    const totalExact = Object.values(splitValues.value).reduce((sum, val) => sum + (val || 0), 0)
    if (Math.abs(totalExact - amount.value) > 0.01) return false
  }

  return true
})

function toggleUser(userId: number) {
  const index = selectedUsers.value.indexOf(userId)
  if (index === -1) {
    selectedUsers.value.push(userId)
  } else if (selectedUsers.value.length > 1) {
    selectedUsers.value.splice(index, 1)
    delete splitValues.value[userId]
  }
}

async function handleSubmit() {
  if (!isValid.value) return

  error.value = ''
  loading.value = true

  try {
    const splitData = selectedUsers.value.map(userId => ({
      user_id: userId,
      value: splitType.value === 'equal' ? 1 : (splitValues.value[userId] || 0),
    }))

    await expensesStore.createExpense({
      description: description.value,
      amount: amount.value!,
      date: date.value,
      group_id: groupId.value,
      category_id: categoryId.value,
      split_type: splitType.value,
      splits: splitData,
      notes: notes.value || undefined,
    })

    router.push('/expenses')
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to create expense'
  } finally {
    loading.value = false
  }
}

function getUserName(userId: number) {
  const user = availableUsers.value.find(u => u.id === userId)
  return user?.name || 'Unknown'
}
</script>

<template>
  <div class="max-w-2xl mx-auto">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Add Expense</h1>
      <p class="text-gray-600">Record a new shared expense</p>
    </div>

    <form @submit.prevent="handleSubmit" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div v-if="error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        {{ error }}
      </div>

      <div class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
          <input
            v-model="description"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
            placeholder="e.g., Dinner at restaurant"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
            <input
              v-model="amount"
              type="number"
              step="0.01"
              min="0.01"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
              placeholder="0.00"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input
              v-model="date"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Group</label>
            <select
              v-model="groupId"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
            >
              <option :value="undefined">No group</option>
              <option v-for="group in groupsStore.groups" :key="group.id" :value="group.id">
                {{ group.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select
              v-model="categoryId"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
            >
              <option :value="undefined">Select category</option>
              <option v-for="cat in categoriesStore.categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Split with *</label>
          <div class="space-y-2 max-h-40 overflow-y-auto border border-gray-200 rounded-lg p-2">
            <label
              v-for="user in availableUsers"
              :key="user.id"
              class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer"
            >
              <input
                type="checkbox"
                :checked="selectedUsers.includes(user.id)"
                @change="toggleUser(user.id)"
                class="h-4 w-4 text-teal-600 rounded focus:ring-teal-500"
              />
              <span class="text-sm">{{ user.name }}</span>
              <span v-if="user.id === authStore.user?.id" class="text-xs text-gray-500">(you)</span>
            </label>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Split type</label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="type in ['equal', 'percentage', 'shares', 'exact'] as const"
              :key="type"
              type="button"
              @click="splitType = type"
              :class="[
                'px-4 py-2 rounded-lg text-sm font-medium capitalize transition-colors',
                splitType === type
                  ? 'bg-teal-100 text-teal-700 border-2 border-teal-500'
                  : 'bg-gray-100 text-gray-700 border-2 border-transparent hover:bg-gray-200',
              ]"
            >
              {{ type }}
            </button>
          </div>
        </div>

        <div v-if="splitType !== 'equal' && selectedUsers.length > 0">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ splitType === 'percentage' ? 'Percentages' : splitType === 'shares' ? 'Shares' : 'Amounts' }}
          </label>
          <div class="space-y-2">
            <div
              v-for="userId in selectedUsers"
              :key="userId"
              class="flex items-center gap-3"
            >
              <span class="w-32 text-sm truncate">{{ getUserName(userId) }}</span>
              <input
                v-model.number="splitValues[userId]"
                type="number"
                step="0.01"
                min="0"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                :placeholder="splitType === 'percentage' ? '%' : splitType === 'shares' ? 'shares' : '0.00'"
              />
            </div>
          </div>
          <p v-if="splitType === 'percentage'" class="mt-2 text-sm text-gray-500">
            Total: {{ totalPercentage }}%
            <span v-if="Math.abs(totalPercentage - 100) > 0.01" class="text-red-500">(must be 100%)</span>
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
          <textarea
            v-model="notes"
            rows="2"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
            placeholder="Optional notes..."
          />
        </div>
      </div>

      <div class="mt-8 flex justify-end gap-3">
        <router-link
          to="/expenses"
          class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
        >
          Cancel
        </router-link>
        <button
          type="submit"
          :disabled="loading || !isValid"
          class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          {{ loading ? 'Adding...' : 'Add Expense' }}
        </button>
      </div>
    </form>
  </div>
</template>
