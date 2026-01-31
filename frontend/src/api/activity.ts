import api from './client'
import type { ActivityLog, PaginatedResponse } from '@/types'

export const activityApi = {
  getAll(params?: { group_id?: number; limit?: number }) {
    const queryParams = new URLSearchParams()
    if (params?.group_id) queryParams.append('group_id', params.group_id.toString())
    if (params?.limit) queryParams.append('limit', params.limit.toString())
    return api.get<PaginatedResponse<ActivityLog>>(`/activity?${queryParams}`)
  },
}
