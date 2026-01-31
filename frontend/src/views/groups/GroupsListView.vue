<script setup lang="ts">
import { onMounted } from 'vue'
import { useGroupsStore } from '@/stores/groups'
import { UserGroupIcon, PlusIcon } from '@heroicons/vue/24/outline'

const groupsStore = useGroupsStore()

onMounted(() => {
  groupsStore.fetchGroups()
})
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Groups</h1>
        <p class="text-gray-600">Manage your expense groups</p>
      </div>
      <router-link
        to="/groups/new"
        class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors"
      >
        <PlusIcon class="h-5 w-5" />
        New Group
      </router-link>
    </div>

    <div v-if="groupsStore.loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
    </div>

    <div v-else-if="groupsStore.groups.length === 0" class="text-center py-12">
      <UserGroupIcon class="h-16 w-16 mx-auto text-gray-300 mb-4" />
      <h3 class="text-lg font-medium text-gray-900 mb-2">No groups yet</h3>
      <p class="text-gray-500 mb-4">Create a group to start splitting expenses with friends</p>
      <router-link
        to="/groups/new"
        class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700"
      >
        <PlusIcon class="h-5 w-5" />
        Create your first group
      </router-link>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <router-link
        v-for="group in groupsStore.groups"
        :key="group.id"
        :to="`/groups/${group.id}`"
        class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start gap-4">
          <div class="p-3 bg-teal-100 rounded-lg">
            <UserGroupIcon class="h-6 w-6 text-teal-600" />
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-gray-900 truncate">{{ group.name }}</h3>
            <p v-if="group.description" class="text-sm text-gray-500 truncate mt-1">
              {{ group.description }}
            </p>
            <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
              <span>{{ group.members_count }} members</span>
              <span>{{ group.expenses_count || 0 }} expenses</span>
            </div>
          </div>
        </div>
      </router-link>
    </div>
  </div>
</template>
