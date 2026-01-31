import { defineStore } from 'pinia'
import { ref } from 'vue'
import { friendsApi } from '@/api/friends'
import type { User, Friend } from '@/types'

export const useFriendsStore = defineStore('friends', () => {
  const friends = ref<User[]>([])
  const pendingReceived = ref<Friend[]>([])
  const pendingSent = ref<Friend[]>([])
  const loading = ref(false)

  async function fetchFriends() {
    loading.value = true
    try {
      const response = await friendsApi.getAll()
      friends.value = response.data.friends
    } finally {
      loading.value = false
    }
  }

  async function fetchPending() {
    loading.value = true
    try {
      const response = await friendsApi.getPending()
      pendingReceived.value = response.data.received
      pendingSent.value = response.data.sent
    } finally {
      loading.value = false
    }
  }

  async function inviteFriend(email: string) {
    const response = await friendsApi.invite(email)
    pendingSent.value.push(response.data.friend_request)
    return response.data
  }

  async function acceptRequest(id: number) {
    const response = await friendsApi.accept(id)
    pendingReceived.value = pendingReceived.value.filter(r => r.id !== id)
    friends.value.push(response.data.friend)
    return response.data.friend
  }

  async function rejectRequest(id: number) {
    await friendsApi.reject(id)
    pendingReceived.value = pendingReceived.value.filter(r => r.id !== id)
  }

  async function removeFriend(id: number) {
    await friendsApi.remove(id)
    friends.value = friends.value.filter(f => f.id !== id)
  }

  return {
    friends,
    pendingReceived,
    pendingSent,
    loading,
    fetchFriends,
    fetchPending,
    inviteFriend,
    acceptRequest,
    rejectRequest,
    removeFriend,
  }
})
