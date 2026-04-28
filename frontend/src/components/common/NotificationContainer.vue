<template>
  <div class="fixed bottom-5 right-5 space-y-3 z-40 pointer-events-none">
    <NotificationToast
      v-for="toast in toasts"
      :key="toast.id"
      :notification="toast"
      :duration="3000"
      :onClick="() => handleToastClick(toast)"
      :onClose="() => removeToast(toast.id)"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import NotificationToast from './NotificationToast.vue'
import { useNotifications } from '@/composables/useNotifications'
import { useRouter } from 'vue-router'

const router = useRouter()
const { toastNotifications } = useNotifications()

const toasts = computed(() => toastNotifications.value)

const removeToast = (id) => {
  const index = toastNotifications.value.findIndex(t => t.id === id)
  if (index > -1) {
    toastNotifications.value.splice(index, 1)
  }
}

const handleToastClick = (notification) => {
  if (notification.action_url) {
    router.push(notification.action_url)
  }
}
</script>

<style scoped>
.pointer-events-none > * {
  pointer-events: auto;
}
</style>
