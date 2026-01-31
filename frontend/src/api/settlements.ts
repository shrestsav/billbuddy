import api from './client'
import type { Settlement, Balance, SimplifiedDebt, PaginatedResponse } from '@/types'

interface CreateSettlementData {
  payee_id: number
  amount: number
  currency?: string
  group_id?: number
  notes?: string
  date?: string
}

interface BalancesResponse {
  balances: Balance[]
  total_owed: number
  total_owing: number
  net_balance: number
}

export const settlementsApi = {
  getAll(groupId?: number, page = 1) {
    const params = new URLSearchParams({ page: page.toString() })
    if (groupId) params.append('group_id', groupId.toString())
    return api.get<PaginatedResponse<Settlement>>(`/settlements?${params}`)
  },

  get(id: number) {
    return api.get<{ settlement: Settlement }>(`/settlements/${id}`)
  },

  create(data: CreateSettlementData) {
    return api.post<{ message: string; settlement: Settlement }>('/settlements', data)
  },

  getBalances(groupId?: number) {
    const params = groupId ? `?group_id=${groupId}` : ''
    return api.get<BalancesResponse>(`/balances${params}`)
  },

  getSimplifiedBalances(groupId?: number) {
    const params = groupId ? `?group_id=${groupId}` : ''
    return api.get<{ simplified_debts: SimplifiedDebt[] }>(`/balances/simplified${params}`)
  },
}
