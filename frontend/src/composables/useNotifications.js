import { ref } from 'vue'
import { useNotificationStore } from '@/stores/notification'

export const useNotifications = () => {
  const notificationStore = useNotificationStore()
  const toastNotifications = ref([])
  const previousNotificationIds = ref(new Set())
  let pollingInterval = null
  let isFirstLoad = true

  /**
   * Start polling for new notifications
   */
  const startPolling = (interval = 10000) => {
    // Initial fetch
    fetchNotifications()

    // Poll every X milliseconds
    pollingInterval = setInterval(() => {
      fetchNotifications()
    }, interval)
  }

  /**
   * Stop polling
   */
  const stopPolling = () => {
    if (pollingInterval) {
      clearInterval(pollingInterval)
      pollingInterval = null
    }
  }

  /**
   * Fetch notifications
   */
  const fetchNotifications = async () => {
    try {
      const result = await notificationStore.fetchNotifications()
      
      // On first load, don't show toasts for existing notifications
      if (isFirstLoad) {
        previousNotificationIds.value = new Set(
          notificationStore.notifications.map(n => n.id)
        )
        isFirstLoad = false
        return
      }

      // Check for new notifications and show toasts only for NEW ones
      if (result.notifications) {
        result.notifications.forEach(notification => {
          if (!previousNotificationIds.value.has(notification.id)) {
            previousNotificationIds.value.add(notification.id)
            showToast(notification)
          }
        })
      }
    } catch (error) {
      console.error('Error fetching notifications:', error)
    }
  }

  /**
   * Show a toast notification
   */
  const showToast = (notification) => {
    const id = Date.now()
    const toast = {
      id,
      ...notification,
    }
    toastNotifications.value.push(toast)
    return id
  }

  /**
   * Close a toast notification
   */
  const closeToast = (id) => {
    const index = toastNotifications.value.findIndex(t => t.id === id)
    if (index > -1) {
      toastNotifications.value.splice(index, 1)
    }
  }

  /**
   * Handle notification click
   */
  const handleNotificationClick = (notification, callback) => {
    callback?.(notification)
  }

  /**
   * Create a toast from a notification
   */
  const createToastFromNotification = (notification) => {
    showToast({
      ...notification,
      onClose: () => {
        // Can be overridden
      },
    })
  }

  return {
    toastNotifications,
    startPolling,
    stopPolling,
    fetchNotifications,
    showToast,
    closeToast,
    handleNotificationClick,
    createToastFromNotification,
  }
}
