<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { usersApi } from '@/api/users'

const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const currencyPreference = ref('')
const timezone = ref('')
const loading = ref(false)
const success = ref('')
const error = ref('')

const currencies = [
  'USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'INR', 'MXN',
  'BRL', 'KRW', 'SGD', 'HKD', 'NOK', 'SEK', 'DKK', 'NZD', 'ZAR', 'RUB',
]

const timezones = [
  'UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
  'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'Asia/Tokyo', 'Asia/Shanghai',
  'Asia/Singapore', 'Asia/Dubai', 'Australia/Sydney', 'Pacific/Auckland',
]

onMounted(() => {
  if (authStore.user) {
    name.value = authStore.user.name
    email.value = authStore.user.email
    currencyPreference.value = authStore.user.currency_preference
    timezone.value = authStore.user.timezone
  }
})

async function updateProfile() {
  error.value = ''
  success.value = ''
  loading.value = true

  try {
    const response = await usersApi.updateProfile({
      name: name.value,
      email: email.value,
    })
    authStore.user = response.data.user
    success.value = 'Profile updated successfully'
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to update profile'
  } finally {
    loading.value = false
  }
}

async function updateSettings() {
  error.value = ''
  success.value = ''
  loading.value = true

  try {
    const response = await usersApi.updateSettings({
      currency_preference: currencyPreference.value,
      timezone: timezone.value,
    })
    authStore.user = response.data.user
    success.value = 'Settings updated successfully'
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to update settings'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-2xl mx-auto">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
      <p class="text-gray-600">Manage your account settings</p>
    </div>

    <div
      v-if="success"
      class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg"
    >
      {{ success }}
    </div>

    <div
      v-if="error"
      class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"
    >
      {{ error }}
    </div>

    <!-- Profile Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile</h2>

      <form @submit.prevent="updateProfile" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
          <input
            v-model="name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50"
        >
          {{ loading ? 'Saving...' : 'Update Profile' }}
        </button>
      </form>
    </div>

    <!-- Preferences Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Preferences</h2>

      <form @submit.prevent="updateSettings" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Default Currency</label>
          <select
            v-model="currencyPreference"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
          >
            <option v-for="c in currencies" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
          <select
            v-model="timezone"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
          >
            <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
          </select>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50"
        >
          {{ loading ? 'Saving...' : 'Update Preferences' }}
        </button>
      </form>
    </div>
  </div>
</template>
