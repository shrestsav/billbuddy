import api from './client'
import type { Group, Expense, Settlement, Balance, PaginatedResponse } from '@/types'

interface CreateGroupData {
  name: string
  description?: string
  type?: 'home' | 'trip' | 'couple' | 'other'
  currency?: string
  member_ids?: number[]
}

export const groupsApi = {
  getAll() {
    return api.get<{ groups: Group[] }>('/groups')
  },

  get(id: number) {
    return api.get<{ group: Group }>(`/groups/${id}`)
  },

  create(data: CreateGroupData) {
    return api.post<{ message: string; group: Group }>('/groups', data)
  },

  update(id: number, data: Partial<CreateGroupData>) {
    return api.put<{ message: string; group: Group }>(`/groups/${id}`, data)
  },

  delete(id: number) {
    return api.delete(`/groups/${id}`)
  },

  addMember(groupId: number, userId: number) {
    return api.post(`/groups/${groupId}/members`, { user_id: userId })
  },

  removeMember(groupId: number, userId: number) {
    return api.delete(`/groups/${groupId}/members/${userId}`)
  },

  updateMemberRole(groupId: number, userId: number, role: 'admin' | 'member') {
    return api.put(`/groups/${groupId}/members/${userId}/role`, { role })
  },

  getBalances(groupId: number) {
    return api.get<{ balances: Balance[] }>(`/groups/${groupId}/balances`)
  },

  getExpenses(groupId: number, page = 1) {
    return api.get<PaginatedResponse<Expense>>(`/groups/${groupId}/expenses?page=${page}`)
  },

  getSettlements(groupId: number, page = 1) {
    return api.get<PaginatedResponse<Settlement>>(`/groups/${groupId}/settlements?page=${page}`)
  },
}
