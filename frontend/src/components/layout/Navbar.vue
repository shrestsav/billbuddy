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
  <nav class="bg-white/80 backdrop-blur-xl border-b border-white/20 fixed w-full z-30 top-0 shadow-sm">
    <div class="px-4 py-3 lg:px-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <button
            @click="$emit('toggleSidebar')"
            class="lg:hidden p-2 rounded-xl text-gray-500 hover:bg-white/50 transition-colors"
          >
            <Bars3Icon class="h-6 w-6" />
          </button>
          <router-link to="/dashboard" class="flex items-center ml-2 lg:ml-0 group">
            <span class="text-xl font-bold bg-gradient-to-r from-violet-600 to-indigo-600 bg-clip-text text-transparent group-hover:from-violet-500 group-hover:to-indigo-500 transition-all">BillBuddy</span>
          </router-link>
        </div>

        <div class="flex items-center gap-4">
          <Menu as="div" class="relative">
            <MenuButton class="flex items-center gap-2 p-2 rounded-xl hover:bg-white/50 transition-colors">
              <img
                v-if="authStore.user?.avatar"
                :src="authStore.user.avatar"
                class="h-8 w-8 rounded-full ring-2 ring-white shadow-sm"
              />
              <UserCircleIcon v-else class="h-8 w-8 text-gray-400" />
              <span class="hidden md:block text-sm font-medium text-gray-700">
                {{ authStore.user?.name }}
              </span>
            </MenuButton>

            <MenuItems
              class="absolute right-0 mt-2 w-48 bg-white/90 backdrop-blur-xl rounded-xl shadow-lg shadow-gray-200/50 border border-white/20 py-1 focus:outline-none"
            >
              <MenuItem v-slot="{ active }">
                <router-link
                  to="/settings"
                  :class="[
                    active ? 'bg-violet-50 text-violet-700' : '',
                    'flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 transition-colors',
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
                    active ? 'bg-violet-50 text-violet-700' : '',
                    'flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 w-full transition-colors',
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
