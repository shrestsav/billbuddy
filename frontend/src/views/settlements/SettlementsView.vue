<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useFriendsStore } from '@/stores/friends'
import { settlementsApi } from '@/api/settlements'
import type { Settlement, SimplifiedDebt } from '@/types'
import { BanknotesIcon, ArrowRightIcon } from '@heroicons/vue/24/outline'
import { format } from 'date-fns'

const authStore = useAuthStore()
const friendsStore = useFriendsStore()

const settlements = ref<Settlement[]>([])
const simplifiedDebts = ref<SimplifiedDebt[]>([])
const loading = ref(true)
const showSettleModal = ref(false)
const settleData = ref({
  payee_id: undefined as number | undefined,
  amount: null as number | null,
  notes: '',
})
const settleLoading = ref(false)
const settleError = ref('')

onMounted(async () => {
  try {
    await friendsStore.fetchFriends()
    const [settlementsRes, debtsRes] = await Promise.all([
      settlementsApi.getAll(),
      settlementsApi.getSimplifiedBalances(),
    ])
    settlements.value = settlementsRes.data.data
    simplifiedDebts.value = debtsRes.data.simplified_debts
  } finally {
    loading.value = false
  }
})

const userDebts = computed(() => {
  if (!authStore.user) return []
  return simplifiedDebts.value.filter(d => d.from.id === authStore.user!.id)
})

const userCredits = computed(() => {
  if (!authStore.user) return []
  return simplifiedDebts.value.filter(d => d.to.id === authStore.user!.id)
})

function openSettleModal(debt: SimplifiedDebt) {
  settleData.value = {
    payee_id: debt.to.id,
    amount: debt.amount,
    notes: '',
  }
  showSettleModal.value = true
}

async function handleSettle() {
  if (!settleData.value.payee_id || !settleData.value.amount) return

  settleError.value = ''
  settleLoading.value = true

  try {
    const result = await settlementsApi.create({
      payee_id: settleData.value.payee_id,
      amount: settleData.value.amount,
      notes: settleData.value.notes || undefined,
    })
    settlements.value.unshift(result.data.settlement)

    const debtsRes = await settlementsApi.getSimplifiedBalances()
    simplifiedDebts.value = debtsRes.data.simplified_debts

    showSettleModal.value = false
  } catch (e: any) {
    settleError.value = e.response?.data?.message || 'Failed to record settlement'
  } finally {
    settleLoading.value = false
  }
}

function formatCurrency(amount: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: authStore.user?.currency_preference || 'USD',
  }).format(amount)
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Settle Up</h1>
      <p class="text-gray-600">Record payments and settle debts</p>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
    </div>

    <template v-else>
      <!-- Simplified Debts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- You Owe -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">You Owe</h2>
          </div>
          <div class="p-4">
            <div v-if="userDebts.length === 0" class="text-center py-6 text-gray-500">
              You don't owe anyone!
            </div>
            <div v-else class="space-y-3">
              <div
                v-for="debt in userDebts"
                :key="debt.to.id"
                class="flex items-center justify-between p-3 bg-red-50 rounded-lg"
              >
                <div class="flex items-center gap-3">
                  <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                    <span class="font-medium text-red-600">{{ debt.to.name.charAt(0) }}</span>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">{{ debt.to.name }}</p>
                    <p class="text-sm text-red-600">{{ formatCurrency(debt.amount) }}</p>
                  </div>
                </div>
                <button
                  @click="openSettleModal(debt)"
                  class="px-3 py-1.5 bg-teal-600 text-white text-sm rounded-lg hover:bg-teal-700"
                >
                  Settle
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Owed to You -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Owed to You</h2>
          </div>
          <div class="p-4">
            <div v-if="userCredits.length === 0" class="text-center py-6 text-gray-500">
              No one owes you anything
            </div>
            <div v-else class="space-y-3">
              <div
                v-for="credit in userCredits"
                :key="credit.from.id"
                class="flex items-center justify-between p-3 bg-green-50 rounded-lg"
              >
                <div class="flex items-center gap-3">
                  <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                    <span class="font-medium text-green-600">{{ credit.from.name.charAt(0) }}</span>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">{{ credit.from.name }}</p>
                    <p class="text-sm text-green-600">{{ formatCurrency(credit.amount) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Settlements -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Recent Settlements</h2>
        </div>
        <div class="p-4">
          <div v-if="settlements.length === 0" class="text-center py-6 text-gray-500">
            <BanknotesIcon class="h-12 w-12 mx-auto text-gray-300 mb-2" />
            <p>No settlements yet</p>
          </div>
          <div v-else class="divide-y divide-gray-100">
            <div
              v-for="settlement in settlements"
              :key="settlement.id"
              class="flex items-center justify-between py-3"
            >
              <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                  <span class="font-medium">{{ settlement.payer?.name }}</span>
                  <ArrowRightIcon class="h-4 w-4 text-gray-400" />
                  <span class="font-medium">{{ settlement.payee?.name }}</span>
                </div>
              </div>
              <div class="text-right">
                <p class="font-semibold text-gray-900">{{ formatCurrency(settlement.amount) }}</p>
                <p class="text-sm text-gray-500">
                  {{ format(new Date(settlement.date), 'MMM d, yyyy') }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Settle Modal -->
    <div
      v-if="showSettleModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="showSettleModal = false"
    >
      <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Record Settlement</h3>

        <form @submit.prevent="handleSettle">
          <div v-if="settleError" class="mb-4 bg-red-50 text-red-700 px-4 py-3 rounded-lg">
            {{ settleError }}
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Pay to</label>
              <select
                v-model="settleData.payee_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
              >
                <option v-for="friend in friendsStore.friends" :key="friend.id" :value="friend.id">
                  {{ friend.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
              <input
                v-model="settleData.amount"
                type="number"
                step="0.01"
                min="0.01"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <input
                v-model="settleData.notes"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                placeholder="Optional notes..."
              />
            </div>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button
              type="button"
              @click="showSettleModal = false"
              class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="settleLoading"
              class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50"
            >
              {{ settleLoading ? 'Recording...' : 'Record Payment' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
