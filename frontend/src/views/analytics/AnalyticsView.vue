<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { analyticsApi } from '@/api/analytics'
import { Chart as ChartJS, ArcElement, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js'
import { Pie, Bar } from 'vue-chartjs'

ChartJS.register(ArcElement, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const authStore = useAuthStore()

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

const categorySpending = ref<CategorySpending[]>([])
const timeSpending = ref<TimeSpending[]>([])
const monthlySummary = ref<{
  month: string
  total_paid: number
  your_share: number
  total_owed: number
  expense_count: number
} | null>(null)
const loading = ref(true)
const period = ref<'daily' | 'weekly' | 'monthly'>('monthly')

onMounted(async () => {
  await fetchData()
})

async function fetchData() {
  loading.value = true
  try {
    const [categoryRes, timeRes, summaryRes] = await Promise.all([
      analyticsApi.getSpendingByCategory(),
      analyticsApi.getSpendingOverTime({ period: period.value }),
      analyticsApi.getMonthlySummary(),
    ])
    categorySpending.value = categoryRes.data.spending_by_category
    timeSpending.value = timeRes.data.spending_over_time
    monthlySummary.value = summaryRes.data
  } finally {
    loading.value = false
  }
}

const pieChartData = computed(() => ({
  labels: categorySpending.value.map(c => c.category),
  datasets: [{
    data: categorySpending.value.map(c => c.total),
    backgroundColor: categorySpending.value.map(c => c.color),
    borderWidth: 0,
  }],
}))

const barChartData = computed(() => ({
  labels: timeSpending.value.map(t => t.period),
  datasets: [{
    label: 'Spending',
    data: timeSpending.value.map(t => t.total),
    backgroundColor: '#0d9488',
    borderRadius: 4,
  }],
}))

const pieChartOptions = {
  responsive: true,
  plugins: {
    legend: {
      position: 'right' as const,
    },
  },
}

const barChartOptions = {
  responsive: true,
  plugins: {
    legend: {
      display: false,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
    },
  },
}

function formatCurrency(amount: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: authStore.user?.currency_preference || 'USD',
  }).format(amount)
}

async function changePeriod(newPeriod: 'daily' | 'weekly' | 'monthly') {
  period.value = newPeriod
  const timeRes = await analyticsApi.getSpendingOverTime({ period: newPeriod })
  timeSpending.value = timeRes.data.spending_over_time
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Analytics</h1>
      <p class="text-gray-600">Insights into your spending</p>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
    </div>

    <template v-else>
      <!-- Monthly Summary -->
      <div v-if="monthlySummary" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
          <p class="text-sm text-gray-600">Total Paid</p>
          <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(monthlySummary.total_paid) }}</p>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
          <p class="text-sm text-gray-600">Your Share</p>
          <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(monthlySummary.your_share) }}</p>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
          <p class="text-sm text-gray-600">Total Owed</p>
          <p class="text-2xl font-bold text-red-600">{{ formatCurrency(monthlySummary.total_owed) }}</p>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
          <p class="text-sm text-gray-600">Expenses</p>
          <p class="text-2xl font-bold text-gray-900">{{ monthlySummary.expense_count }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Spending by Category -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Spending by Category</h2>
          <div v-if="categorySpending.length === 0" class="text-center py-8 text-gray-500">
            No spending data yet
          </div>
          <div v-else class="h-64">
            <Pie :data="pieChartData" :options="pieChartOptions" />
          </div>
        </div>

        <!-- Spending Over Time -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Spending Over Time</h2>
            <div class="flex gap-1">
              <button
                v-for="p in ['daily', 'weekly', 'monthly'] as const"
                :key="p"
                @click="changePeriod(p)"
                :class="[
                  'px-3 py-1 text-sm rounded-lg capitalize',
                  period === p
                    ? 'bg-teal-100 text-teal-700'
                    : 'text-gray-600 hover:bg-gray-100',
                ]"
              >
                {{ p }}
              </button>
            </div>
          </div>
          <div v-if="timeSpending.length === 0" class="text-center py-8 text-gray-500">
            No spending data yet
          </div>
          <div v-else class="h-64">
            <Bar :data="barChartData" :options="barChartOptions" />
          </div>
        </div>
      </div>

      <!-- Category Breakdown -->
      <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Category Breakdown</h2>
        </div>
        <div class="p-4">
          <div v-if="categorySpending.length === 0" class="text-center py-6 text-gray-500">
            No spending data yet
          </div>
          <div v-else class="space-y-3">
            <div
              v-for="cat in categorySpending"
              :key="cat.category"
              class="flex items-center gap-4"
            >
              <div class="w-32 truncate text-sm font-medium text-gray-900">{{ cat.category }}</div>
              <div class="flex-1">
                <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                  <div
                    class="h-full rounded-full"
                    :style="{
                      width: cat.percentage + '%',
                      backgroundColor: cat.color,
                    }"
                  />
                </div>
              </div>
              <div class="w-24 text-right text-sm text-gray-600">
                {{ formatCurrency(cat.total) }}
              </div>
              <div class="w-16 text-right text-sm text-gray-500">
                {{ cat.percentage }}%
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
