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
      'fixed top-0 left-0 z-20 h-full w-64 pt-16 bg-white border-r border-gray-200 transition-transform lg:translate-x-0',
      open ? 'translate-x-0' : '-translate-x-full',
    ]"
  >
    <div class="h-full px-3 py-4 overflow-y-auto">
      <ul class="space-y-1">
        <li v-for="item in navigation" :key="item.name">
          <router-link
            :to="item.href"
            :class="[
              'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
              isActive(item.href)
                ? 'bg-teal-50 text-teal-700'
                : 'text-gray-700 hover:bg-gray-100',
            ]"
          >
            <component
              :is="item.icon"
              :class="[
                'h-5 w-5',
                isActive(item.href) ? 'text-teal-600' : 'text-gray-400',
              ]"
            />
            {{ item.name }}
          </router-link>
        </li>
      </ul>
    </div>
  </aside>
</template>
