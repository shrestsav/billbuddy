import api from './client'
import type { User } from '@/types'

export const usersApi = {
  search(email: string) {
    return api.get<{ users: User[] }>(`/users/search?email=${encodeURIComponent(email)}`)
  },

  updateProfile(data: { name?: string; email?: string }) {
    return api.put<{ message: string; user: User }>('/users/profile', data)
  },

  updateSettings(data: { currency_preference?: string; timezone?: string }) {
    return api.put<{ message: string; user: User }>('/users/settings', data)
  },

  uploadAvatar(file: File) {
    const formData = new FormData()
    formData.append('avatar', file)
    return api.post<{ message: string; avatar_url: string }>('/users/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
}
