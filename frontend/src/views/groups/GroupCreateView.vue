<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useGroupsStore } from '@/stores/groups'
import { useFriendsStore } from '@/stores/friends'
import { onMounted } from 'vue'

const router = useRouter()
const groupsStore = useGroupsStore()
const friendsStore = useFriendsStore()

const name = ref('')
const description = ref('')
const type = ref<'home' | 'trip' | 'couple' | 'other'>('other')
const selectedMembers = ref<number[]>([])
const loading = ref(false)
const error = ref('')

const groupTypes = [
  { value: 'home', label: 'Home' },
  { value: 'trip', label: 'Trip' },
  { value: 'couple', label: 'Couple' },
  { value: 'other', label: 'Other' },
]

onMounted(() => {
  friendsStore.fetchFriends()
})

async function handleSubmit() {
  error.value = ''
  loading.value = true

  try {
    const group = await groupsStore.createGroup({
      name: name.value,
      description: description.value || undefined,
      type: type.value,
      member_ids: selectedMembers.value,
    })
    router.push(`/groups/${group.id}`)
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to create group'
  } finally {
    loading.value = false
  }
}

function toggleMember(userId: number) {
  const index = selectedMembers.value.indexOf(userId)
  if (index === -1) {
    selectedMembers.value.push(userId)
  } else {
    selectedMembers.value.splice(index, 1)
  }
}
</script>

<template>
  <div class="max-w-2xl mx-auto">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Create a Group</h1>
      <p class="text-gray-600">Set up a new group to split expenses</p>
    </div>

    <form @submit.prevent="handleSubmit" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div v-if="error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        {{ error }}
      </div>

      <div class="space-y-6">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
            Group name *
          </label>
          <input
            id="name"
            v-model="name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
            placeholder="e.g., Weekend Trip, Apartment Bills"
          />
        </div>

        <div>
          <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
            Description
          </label>
          <textarea
            id="description"
            v-model="description"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
            placeholder="What is this group for?"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Group type
          </label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="gt in groupTypes"
              :key="gt.value"
              type="button"
              @click="type = gt.value as typeof type"
              :class="[
                'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                type === gt.value
                  ? 'bg-teal-100 text-teal-700 border-2 border-teal-500'
                  : 'bg-gray-100 text-gray-700 border-2 border-transparent hover:bg-gray-200',
              ]"
            >
              {{ gt.label }}
            </button>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Add members (optional)
          </label>
          <div v-if="friendsStore.friends.length === 0" class="text-sm text-gray-500">
            No friends yet.
            <router-link to="/friends" class="text-teal-600 hover:underline">Add friends first</router-link>
          </div>
          <div v-else class="space-y-2 max-h-48 overflow-y-auto">
            <label
              v-for="friend in friendsStore.friends"
              :key="friend.id"
              class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer"
            >
              <input
                type="checkbox"
                :checked="selectedMembers.includes(friend.id)"
                @change="toggleMember(friend.id)"
                class="h-4 w-4 text-teal-600 rounded focus:ring-teal-500"
              />
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                  <span class="text-sm font-medium text-gray-600">
                    {{ friend.name.charAt(0) }}
                  </span>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ friend.name }}</p>
                  <p class="text-xs text-gray-500">{{ friend.email }}</p>
                </div>
              </div>
            </label>
          </div>
        </div>
      </div>

      <div class="mt-8 flex justify-end gap-3">
        <router-link
          to="/groups"
          class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
        >
          Cancel
        </router-link>
        <button
          type="submit"
          :disabled="loading || !name"
          class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          {{ loading ? 'Creating...' : 'Create Group' }}
        </button>
      </div>
    </form>
  </div>
</template>
