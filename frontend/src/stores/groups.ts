import { defineStore } from 'pinia'
import { ref } from 'vue'
import { groupsApi } from '@/api/groups'
import type { Group } from '@/types'

export const useGroupsStore = defineStore('groups', () => {
  const groups = ref<Group[]>([])
  const currentGroup = ref<Group | null>(null)
  const loading = ref(false)

  async function fetchGroups() {
    loading.value = true
    try {
      const response = await groupsApi.getAll()
      groups.value = response.data.groups
    } finally {
      loading.value = false
    }
  }

  async function fetchGroup(id: number) {
    loading.value = true
    try {
      const response = await groupsApi.get(id)
      currentGroup.value = response.data.group
      return response.data.group
    } finally {
      loading.value = false
    }
  }

  async function createGroup(data: {
    name: string
    description?: string
    type?: 'home' | 'trip' | 'couple' | 'other'
    currency?: string
    member_ids?: number[]
  }) {
    const response = await groupsApi.create(data)
    groups.value.push(response.data.group)
    return response.data.group
  }

  async function updateGroup(id: number, data: {
    name?: string
    description?: string
    type?: 'home' | 'trip' | 'couple' | 'other'
    currency?: string
  }) {
    const response = await groupsApi.update(id, data)
    const index = groups.value.findIndex(g => g.id === id)
    if (index !== -1) {
      groups.value[index] = response.data.group
    }
    if (currentGroup.value?.id === id) {
      currentGroup.value = response.data.group
    }
    return response.data.group
  }

  async function deleteGroup(id: number) {
    await groupsApi.delete(id)
    groups.value = groups.value.filter(g => g.id !== id)
    if (currentGroup.value?.id === id) {
      currentGroup.value = null
    }
  }

  return {
    groups,
    currentGroup,
    loading,
    fetchGroups,
    fetchGroup,
    createGroup,
    updateGroup,
    deleteGroup,
  }
})
