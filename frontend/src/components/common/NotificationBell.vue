<template>
  <div class="notification-bell-wrapper">
    <svg
      class="notification-bell-icon"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="1.5"
        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0018 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
      />
    </svg>

    <!-- Unread Badge -->
    <span
      v-if="notificationStore.unreadCount > 0"
      class="notification-badge"
    >
      {{ notificationStore.unreadCount > 99 ? '99+' : notificationStore.unreadCount }}
    </span>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useNotificationStore } from '@/stores/notification'
import { useNotifications } from '@/composables/useNotifications'

const notificationStore = useNotificationStore()
const { startPolling, stopPolling } = useNotifications()

onMounted(() => {
  // Start polling for notifications
  startPolling(10000) // Poll every 10 seconds

  // Initial fetch
  notificationStore.fetchNotifications()
})

onUnmounted(() => {
  stopPolling()
})
</script>

<style scoped>
.notification-bell-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

.notification-bell-icon {
  width: 1.5rem;
  height: 1.5rem;
  color: currentColor;
}

.notification-badge {
  position: absolute;
  top: -0.25rem;
  right: -0.35rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.25rem;
  height: 1.25rem;
  padding: 0 0.35rem;
  font-size: 0.65rem;
  font-weight: 700;
  line-height: 1;
  text-align: center;
  color: #fff;
  background: #ef4444;
  border: 2px solid #1f2937;
  border-radius: 9999px;
}
</style>
