import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref([])
  const unreadCount = ref(0)
  const loading = ref(false)

  // Fetch all notifications
  const fetchNotifications = async (filter = 'all') => {
    try {
      loading.value = true
      const response = await api.get(`/notifications?filter=${filter}`)
      notifications.value = response.notifications
      unreadCount.value = response.unread_count
      return response
    } catch (error) {
      console.error('Failed to fetch notifications:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  // Get unread count
  const getUnreadCount = async () => {
    try {
      const response = await api.get('/notifications/unread/count')
      unreadCount.value = response.data.unread_count
      return response.data.unread_count
    } catch (error) {
      console.error('Failed to get unread count:', error)
    }
  }

  // Mark single notification as read
  const markAsRead = async (notificationId) => {
    try {
      await api.put(`/notifications/${notificationId}/read`)
      const notification = notifications.value.find(n => n.id === notificationId)
      if (notification) {
        notification.read_at = new Date().toISOString()
      }
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (error) {
      console.error('Failed to mark notification as read:', error)
      throw error
    }
  }

  // Mark all notifications as read
  const markAllAsRead = async () => {
    try {
      await api.put('/notifications/read-all')
      notifications.value.forEach(n => {
        n.read_at = new Date().toISOString()
      })
      unreadCount.value = 0
    } catch (error) {
      console.error('Failed to mark all as read:', error)
      throw error
    }
  }

  // Delete a notification
  const deleteNotification = async (notificationId) => {
    try {
      await api.delete(`/notifications/${notificationId}`)
      const index = notifications.value.findIndex(n => n.id === notificationId)
      if (index > -1) {
        const wasUnread = !notifications.value[index].read_at
        notifications.value.splice(index, 1)
        if (wasUnread) {
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
      }
    } catch (error) {
      console.error('Failed to delete notification:', error)
      throw error
    }
  }

  // Delete all notifications
  const deleteAllNotifications = async () => {
    try {
      await api.delete('/notifications')
      notifications.value = []
      unreadCount.value = 0
    } catch (error) {
      console.error('Failed to delete all notifications:', error)
      throw error
    }
  }

  // Add new notification locally (for toast display)
  const addLocalNotification = (notification) => {
    notifications.value.unshift({
      id: Date.now(),
      ...notification,
      read_at: null,
      created_at: new Date().toISOString(),
    })
    unreadCount.value++
  }

  // Computed properties
  const unreadNotifications = computed(() => 
    notifications.value.filter(n => !n.read_at)
  )

  const readNotifications = computed(() => 
    notifications.value.filter(n => n.read_at)
  )

  return {
    notifications,
    unreadCount,
    loading,
    unreadNotifications,
    readNotifications,
    fetchNotifications,
    getUnreadCount,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    deleteAllNotifications,
    addLocalNotification,
  }
})
