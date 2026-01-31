import api from './client'
import type { User, Friend } from '@/types'

export const friendsApi = {
  getAll() {
    return api.get<{ friends: User[] }>('/friends')
  },

  getPending() {
    return api.get<{ received: Friend[]; sent: Friend[] }>('/friends/pending')
  },

  invite(email: string) {
    return api.post<{ message: string; friend_request: Friend }>('/friends/invite', { email })
  },

  accept(id: number) {
    return api.put<{ message: string; friend: User }>(`/friends/${id}/accept`)
  },

  reject(id: number) {
    return api.put(`/friends/${id}/reject`)
  },

  remove(id: number) {
    return api.delete(`/friends/${id}`)
  },
}
