<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import {
  HomeIcon,
  UserGroupIcon,
  UsersIcon,
  CurrencyDollarIcon,
  BanknotesIcon,
  ChartBarIcon,
  ClockIcon,
} from '@heroicons/vue/24/outline'

defineProps<{
  open: boolean
}>()

const route = useRoute()

const navigation = [
  { name: 'Dashboard', href: '/dashboard', icon: HomeIcon },
  { name: 'Groups', href: '/groups', icon: UserGroupIcon },
  { name: 'Friends', href: '/friends', icon: UsersIcon },
  { name: 'Expenses', href: '/expenses', icon: CurrencyDollarIcon },
  { name: 'Settle Up', href: '/settlements', icon: BanknotesIcon },
  { name: 'Analytics', href: '/analytics', icon: ChartBarIcon },
  { name: 'Activity', href: '/activity', icon: ClockIcon },
]

const isActive = (href: string) => {
  return route.path === href || route.path.startsWith(href + '/')
}
</script>

<template>
  <aside
    :class="[
      'fixed top-0 left-0 z-20 h-full w-64 pt-16 bg-white/80 backdrop-blur-xl border-r border-white/20 transition-transform duration-300 lg:translate-x-0 shadow-lg shadow-gray-200/20',
      open ? 'translate-x-0' : '-translate-x-full',
    ]"
  >
    <div class="h-full px-3 py-4 overflow-y-auto">
      <ul class="space-y-1">
        <li v-for="item in navigation" :key="item.name">
          <router-link
            :to="item.href"
            :class="[
              'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200',
              isActive(item.href)
                ? 'bg-gradient-to-r from-violet-500 to-indigo-500 text-white shadow-md shadow-violet-500/25'
                : 'text-gray-700 hover:bg-white/60 hover:shadow-sm',
            ]"
          >
            <component
              :is="item.icon"
              :class="[
                'h-5 w-5 transition-colors',
                isActive(item.href) ? 'text-white' : 'text-gray-400',
              ]"
            />
            {{ item.name }}
          </router-link>
        </li>
      </ul>
    </div>
  </aside>
</template>
