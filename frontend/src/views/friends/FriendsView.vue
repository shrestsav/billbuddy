<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useFriendsStore } from '@/stores/friends'
import { UsersIcon, PlusIcon, CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const friendsStore = useFriendsStore()

const showInviteModal = ref(false)
const inviteEmail = ref('')
const inviteLoading = ref(false)
const inviteError = ref('')

onMounted(() => {
  friendsStore.fetchFriends()
  friendsStore.fetchPending()
})

async function handleInvite() {
  inviteError.value = ''
  inviteLoading.value = true

  try {
    await friendsStore.inviteFriend(inviteEmail.value)
    inviteEmail.value = ''
    showInviteModal.value = false
  } catch (e: any) {
    inviteError.value = e.response?.data?.message || 'Failed to send invite'
  } finally {
    inviteLoading.value = false
  }
}

async function acceptRequest(id: number) {
  await friendsStore.acceptRequest(id)
}

async function rejectRequest(id: number) {
  await friendsStore.rejectRequest(id)
}

async function removeFriend(id: number) {
  if (confirm('Are you sure you want to remove this friend?')) {
    await friendsStore.removeFriend(id)
  }
}
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Friends</h1>
        <p class="text-gray-600">Manage your friends and split expenses</p>
      </div>
      <button
        @click="showInviteModal = true"
        class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700"
      >
        <PlusIcon class="h-5 w-5" />
        Add Friend
      </button>
    </div>

    <!-- Pending Requests -->
    <div v-if="friendsStore.pendingReceived.length > 0" class="mb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-3">Friend Requests</h2>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 divide-y">
        <div
          v-for="request in friendsStore.pendingReceived"
          :key="request.id"
          class="flex items-center justify-between p-4"
        >
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
              <span class="font-medium text-gray-600">
                {{ request.user?.name.charAt(0) }}
              </span>
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ request.user?.name }}</p>
              <p class="text-sm text-gray-500">{{ request.user?.email }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="acceptRequest(request.id)"
              class="p-2 text-green-600 hover:bg-green-50 rounded-lg"
              title="Accept"
            >
              <CheckIcon class="h-5 w-5" />
            </button>
            <button
              @click="rejectRequest(request.id)"
              class="p-2 text-red-600 hover:bg-red-50 rounded-lg"
              title="Reject"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pending Sent -->
    <div v-if="friendsStore.pendingSent.length > 0" class="mb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-3">Pending Invites</h2>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 divide-y">
        <div
          v-for="request in friendsStore.pendingSent"
          :key="request.id"
          class="flex items-center justify-between p-4"
        >
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
              <span class="font-medium text-gray-600">
                {{ request.friend?.name.charAt(0) }}
              </span>
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ request.friend?.name }}</p>
              <p class="text-sm text-gray-500">{{ request.friend?.email }}</p>
            </div>
          </div>
          <span class="text-sm text-gray-500">Pending</span>
        </div>
      </div>
    </div>

    <!-- Friends List -->
    <div>
      <h2 class="text-lg font-semibold text-gray-900 mb-3">Your Friends</h2>

      <div v-if="friendsStore.loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
      </div>

      <div v-else-if="friendsStore.friends.length === 0" class="text-center py-12">
        <UsersIcon class="h-16 w-16 mx-auto text-gray-300 mb-4" />
        <h3 class="text-lg font-medium text-gray-900 mb-2">No friends yet</h3>
        <p class="text-gray-500 mb-4">Add friends to start splitting expenses together</p>
        <button
          @click="showInviteModal = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700"
        >
          <PlusIcon class="h-5 w-5" />
          Add your first friend
        </button>
      </div>

      <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 divide-y">
        <div
          v-for="friend in friendsStore.friends"
          :key="friend.id"
          class="flex items-center justify-between p-4"
        >
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
              <span class="font-medium text-gray-600">{{ friend.name.charAt(0) }}</span>
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ friend.name }}</p>
              <p class="text-sm text-gray-500">{{ friend.email }}</p>
            </div>
          </div>
          <button
            @click="removeFriend(friend.id)"
            class="text-sm text-red-600 hover:underline"
          >
            Remove
          </button>
        </div>
      </div>
    </div>

    <!-- Invite Modal -->
    <div
      v-if="showInviteModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="showInviteModal = false"
    >
      <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Add a Friend</h3>

        <form @submit.prevent="handleInvite">
          <div v-if="inviteError" class="mb-4 bg-red-50 text-red-700 px-4 py-3 rounded-lg">
            {{ inviteError }}
          </div>

          <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
              Email address
            </label>
            <input
              id="email"
              v-model="inviteEmail"
              type="email"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
              placeholder="friend@example.com"
            />
          </div>

          <div class="flex justify-end gap-3">
            <button
              type="button"
              @click="showInviteModal = false"
              class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="inviteLoading"
              class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50"
            >
              {{ inviteLoading ? 'Sending...' : 'Send Invite' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
