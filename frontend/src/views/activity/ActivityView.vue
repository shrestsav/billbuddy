<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { activityApi } from '@/api/activity'
import type { ActivityLog } from '@/types'
import { ClockIcon } from '@heroicons/vue/24/outline'
import { formatDistanceToNow } from 'date-fns'

const activities = ref<ActivityLog[]>([])
const loading = ref(true)
const hasMore = ref(true)

onMounted(async () => {
  await fetchActivities()
})

async function fetchActivities() {
  loading.value = true
  try {
    const response = await activityApi.getAll({ limit: 50 })
    activities.value = response.data.data
    hasMore.value = response.data.current_page < response.data.last_page
  } finally {
    loading.value = false
  }
}

function getActionIcon(action: string) {
  if (action.includes('expense')) return '💰'
  if (action.includes('settlement')) return '💵'
  if (action.includes('group')) return '👥'
  if (action.includes('friend')) return '🤝'
  if (action.includes('member')) return '➕'
  return '📝'
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Activity</h1>
      <p class="text-gray-600">Recent activity across your groups</p>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
    </div>

    <div v-else-if="activities.length === 0" class="text-center py-12">
      <ClockIcon class="h-16 w-16 mx-auto text-gray-300 mb-4" />
      <h3 class="text-lg font-medium text-gray-900 mb-2">No activity yet</h3>
      <p class="text-gray-500">Activity from your groups and friends will appear here</p>
    </div>

    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200">
      <div class="divide-y divide-gray-100">
        <div
          v-for="activity in activities"
          :key="activity.id"
          class="p-4 hover:bg-gray-50"
        >
          <div class="flex items-start gap-4">
            <div class="text-2xl">{{ getActionIcon(activity.action) }}</div>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-900">
                <span class="font-medium">{{ activity.user?.name }}</span>
                {{ activity.description }}
              </p>
              <div class="flex items-center gap-3 mt-1">
                <span class="text-xs text-gray-500">
                  {{ formatDistanceToNow(new Date(activity.created_at), { addSuffix: true }) }}
                </span>
                <span v-if="activity.group" class="text-xs text-teal-600">
                  {{ activity.group.name }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
