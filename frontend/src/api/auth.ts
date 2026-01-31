import api from './client'
import type { User } from '@/types'

interface AuthResponse {
  message: string
  user: User
  token: string
}

interface RegisterData {
  name: string
  email: string
  password: string
  password_confirmation: string
  currency_preference?: string
  timezone?: string
}

interface LoginData {
  email: string
  password: string
  device_name?: string
}

export const authApi = {
  register(data: RegisterData) {
    return api.post<AuthResponse>('/auth/register', data)
  },

  login(data: LoginData) {
    return api.post<AuthResponse>('/auth/login', data)
  },

  logout() {
    return api.post('/auth/logout')
  },

  getUser() {
    return api.get<{ user: User }>('/auth/user')
  },

  forgotPassword(email: string) {
    return api.post('/auth/forgot-password', { email })
  },

  resetPassword(data: { token: string; email: string; password: string; password_confirmation: string }) {
    return api.post('/auth/reset-password', data)
  },

  resendVerification() {
    return api.post('/auth/resend-verification')
  },
}
