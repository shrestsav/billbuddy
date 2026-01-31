import api from './client'
import type { Group } from '@/types'

interface CategorySpending {
  category: string
  icon: string
  color: string
  total: number
  count: number
  percentage: number
}

interface TimeSpending {
  period: string
  total: number
  count: number
}

interface GroupSummary {
  group: Pick<Group, 'id' | 'name' | 'currency'>
  total_expenses: number
  expense_count: number
  member_count: number
  average_expense: number
  top_spenders: Array<{ user: { id: number; name: string }; total: number }>
  category_breakdown: Array<{ category: string; total: number }>
  balances: any[]
}

interface MonthlySummary {
  month: string
  total_paid: number
  your_share: number
  total_owed: number
  expense_count: number
}

export const analyticsApi = {
  getSpendingByCategory(params?: { from_date?: string; to_date?: string; group_id?: number }) {
    const queryParams = new URLSearchParams()
    if (params?.from_date) queryParams.append('from_date', params.from_date)
    if (params?.to_date) queryParams.append('to_date', params.to_date)
    if (params?.group_id) queryParams.append('group_id', params.group_id.toString())
    return api.get<{ spending_by_category: CategorySpending[]; total: number }>(
      `/analytics/spending-by-category?${queryParams}`
    )
  },

  getSpendingOverTime(params?: { period?: 'daily' | 'weekly' | 'monthly'; from_date?: string; to_date?: string; group_id?: number }) {
    const queryParams = new URLSearchParams()
    if (params?.period) queryParams.append('period', params.period)
    if (params?.from_date) queryParams.append('from_date', params.from_date)
    if (params?.to_date) queryParams.append('to_date', params.to_date)
    if (params?.group_id) queryParams.append('group_id', params.group_id.toString())
    return api.get<{ spending_over_time: TimeSpending[]; period: string }>(
      `/analytics/spending-over-time?${queryParams}`
    )
  },

  getGroupSummary(groupId: number) {
    return api.get<GroupSummary>(`/analytics/group-summary/${groupId}`)
  },

  getMonthlySummary(month?: string) {
    const params = month ? `?month=${month}` : ''
    return api.get<MonthlySummary>(`/analytics/monthly-summary${params}`)
  },
}
