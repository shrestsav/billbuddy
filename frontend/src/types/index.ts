export interface User {
  id: number
  name: string
  email: string
  avatar: string | null
  currency_preference: string
  timezone: string
  email_verified_at: string | null
  created_at: string
}

export interface Group {
  id: number
  name: string
  description: string | null
  type: 'home' | 'trip' | 'couple' | 'other'
  currency: string
  created_by: number
  creator?: User
  members?: User[]
  members_count?: number
  expenses_count?: number
  created_at: string
}

export interface Category {
  id: number
  name: string
  icon: string
  color: string
}

export interface ExpenseSplit {
  id: number
  expense_id: number
  user_id: number
  amount: number
  percentage: number | null
  shares: number | null
  user?: User
}

export interface Expense {
  id: number
  description: string
  amount: number
  currency: string
  date: string
  paid_by: number
  group_id: number | null
  category_id: number | null
  split_type: 'equal' | 'percentage' | 'shares' | 'exact'
  notes: string | null
  receipt_path: string | null
  is_recurring: boolean
  recurring_frequency: 'daily' | 'weekly' | 'monthly' | 'yearly' | null
  payer?: User
  group?: Group
  category?: Category
  splits?: ExpenseSplit[]
  created_at: string
}

export interface Settlement {
  id: number
  payer_id: number
  payee_id: number
  amount: number
  currency: string
  group_id: number | null
  notes: string | null
  date: string
  payer?: User
  payee?: User
  group?: Group
  created_at: string
}

export interface Friend {
  id: number
  user_id: number
  friend_id: number
  status: 'pending' | 'accepted'
  user?: User
  friend?: User
  created_at: string
}

export interface Balance {
  user: User
  amount: number
  direction: 'owed_to_you' | 'you_owe'
}

export interface SimplifiedDebt {
  from: User
  to: User
  amount: number
}

export interface ActivityLog {
  id: number
  user_id: number
  group_id: number | null
  action: string
  description: string
  user?: User
  group?: Group
  created_at: string
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface ApiResponse<T> {
  message?: string
  [key: string]: T | string | undefined
}
