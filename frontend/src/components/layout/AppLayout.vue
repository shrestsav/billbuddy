<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import Navbar from './Navbar.vue'
import Sidebar from './Sidebar.vue'

const authStore = useAuthStore()
const sidebarOpen = ref(false)

onMounted(() => {
  if (authStore.isAuthenticated && !authStore.user) {
    authStore.fetchUser()
  }
})

function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value
}
</script>

<template>
  <div class="min-h-screen">
    <Navbar @toggle-sidebar="toggleSidebar" />
    <Sidebar :open="sidebarOpen" />

    <!-- Overlay for mobile -->
    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 z-10 bg-black/30 backdrop-blur-sm lg:hidden transition-opacity"
    />

    <!-- Main content -->
    <main class="lg:ml-64 pt-16 min-h-screen">
      <div class="p-4 lg:p-8">
        <slot />
      </div>
    </main>
  </div>
</template>
