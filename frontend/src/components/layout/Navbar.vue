<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import { Bars3Icon, UserCircleIcon, ArrowRightOnRectangleIcon, Cog6ToothIcon } from '@heroicons/vue/24/outline'

defineEmits<{
  toggleSidebar: []
}>()

const router = useRouter()
const authStore = useAuthStore()

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <nav class="bg-white border-b border-gray-200 fixed w-full z-30 top-0">
    <div class="px-4 py-3 lg:px-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <button
            @click="$emit('toggleSidebar')"
            class="lg:hidden p-2 rounded-md text-gray-500 hover:bg-gray-100"
          >
            <Bars3Icon class="h-6 w-6" />
          </button>
          <router-link to="/dashboard" class="flex items-center ml-2 lg:ml-0">
            <span class="text-xl font-bold text-teal-600">BillBuddy</span>
          </router-link>
        </div>

        <div class="flex items-center gap-4">
          <Menu as="div" class="relative">
            <MenuButton class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100">
              <img
                v-if="authStore.user?.avatar"
                :src="authStore.user.avatar"
                class="h-8 w-8 rounded-full"
              />
              <UserCircleIcon v-else class="h-8 w-8 text-gray-400" />
              <span class="hidden md:block text-sm font-medium text-gray-700">
                {{ authStore.user?.name }}
              </span>
            </MenuButton>

            <MenuItems
              class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 focus:outline-none"
            >
              <MenuItem v-slot="{ active }">
                <router-link
                  to="/settings"
                  :class="[
                    active ? 'bg-gray-100' : '',
                    'flex items-center gap-2 px-4 py-2 text-sm text-gray-700',
                  ]"
                >
                  <Cog6ToothIcon class="h-5 w-5" />
                  Settings
                </router-link>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <button
                  @click="handleLogout"
                  :class="[
                    active ? 'bg-gray-100' : '',
                    'flex items-center gap-2 px-4 py-2 text-sm text-gray-700 w-full',
                  ]"
                >
                  <ArrowRightOnRectangleIcon class="h-5 w-5" />
                  Logout
                </button>
              </MenuItem>
            </MenuItems>
          </Menu>
        </div>
      </div>
    </div>
  </nav>
</template>
