<template>
  <OubsLayout v-if="isOubsUser">
    <div class="flex flex-1 flex-row min-h-screen">
      <aside
        class="thin-sidebar flex-shrink-0 flex flex-col transition-all duration-300"
        style="background: linear-gradient(to bottom, #3E2723, #5C3A1E)"
      >
        <nav class="sidebar-nav">
          <div class="sidebar-top">
            <div class="sidebar-brand" title="OUBS">
              <svg class="brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L3 7v7c0 5 4 8 9 8s9-3 9-8V7l-9-5z"></path>
                <path d="M9 12h6"></path>
                <path d="M9 16h6"></path>
              </svg>
            </div>
          </div>

          <div class="sidebar-links">
            <RouterLink
              to="/oubs/home"
              class="icon-link"
              :class="$route.path === '/oubs/home' ? 'active' : ''"
              @click="closeSidebarOnMobile"
            >
              <svg class="icon-link-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
              </svg>
              <span class="tooltip">Home</span>
            </RouterLink>

            <RouterLink
              to="/notifications"
              class="icon-link"
              :class="$route.path === '/notifications' ? 'active' : ''"
              title="Notifications"
              @click="closeSidebarOnMobile"
            >
              <NotificationBell />
              <span class="tooltip">Notifications</span>
            </RouterLink>

            <RouterLink
              to="/oubs/documents"
              class="icon-link"
              :class="$route.path === '/oubs/documents' ? 'active' : ''"
              @click="closeSidebarOnMobile"
            >
              <svg class="icon-link-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="12" y1="11" x2="12" y2="17"></line>
                <line x1="9" y1="14" x2="15" y2="14"></line>
              </svg>
              <span class="tooltip">Documents</span>
            </RouterLink>

            <RouterLink
              to="/oubs/upload"
              class="icon-link"
              :class="$route.path === '/oubs/upload' ? 'active' : ''"
              @click="closeSidebarOnMobile"
            >
              <svg class="icon-link-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
              </svg>
              <span class="tooltip">Upload</span>
            </RouterLink>

            <RouterLink
              to="/oubs/summary"
              class="icon-link"
              :class="$route.path === '/oubs/summary' ? 'active' : ''"
              @click="closeSidebarOnMobile"
            >
              <svg class="icon-link-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
              </svg>
              <span class="tooltip">Summary</span>
            </RouterLink>

            <RouterLink
              to="/oubs/users"
              class="icon-link"
              :class="$route.path === '/oubs/users' ? 'active' : ''"
              @click="closeSidebarOnMobile"
            >
              <svg class="icon-link-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
              <span class="tooltip">User List</span>
            </RouterLink>

            <RouterLink
              to="/oubs/logs"
              class="icon-link"
              :class="$route.path === '/oubs/logs' ? 'active' : ''"
              @click="closeSidebarOnMobile"
            >
              <svg class="icon-link-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <line x1="9" y1="9" x2="15" y2="9"></line>
                <line x1="9" y1="13" x2="15" y2="13"></line>
                <line x1="9" y1="17" x2="13" y2="17"></line>
              </svg>
              <span class="tooltip">Logs</span>
            </RouterLink>
          </div>

          <div class="sidebar-footer">
            <button type="button" class="icon-link logout-button" @click="logout" title="Logout">
              <svg class="icon-link-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
              </svg>
              <span class="tooltip">Logout</span>
            </button>
          </div>
        </nav>
      </aside>

      <main class="flex-1 overflow-x-hidden overflow-y-auto">
        <section class="dashboard-screen">
          <div class="dashboard-content">
            <header class="dashboard-hero">
              <p class="hero-kicker">Notification Center</p>
              <h1>Stay up to date with system alerts</h1>
              <p class="hero-subtitle">Manage unread notifications, review actions, and jump to relevant pages.</p>
            </header>

            <div class="panel-row">
              <div class="filter-group">
                <button
                  v-for="filter in filters"
                  :key="filter.value"
                  @click="selectedFilter = filter.value"
                  :class="[
                    'filter-pill',
                    selectedFilter === filter.value ? 'active' : ''
                  ]"
                >
                  {{ filter.label }}
                </button>
              </div>

              <button
                v-if="notificationStore.notifications.length > 0"
                @click="deleteAll"
                class="clear-btn"
              >
                Clear All
              </button>
            </div>

            <div class="notifications-panel">
              <div v-if="loading" class="empty-state">
                <div class="spinner" />
                <p>Loading notifications...</p>
              </div>

              <div v-else-if="filteredNotifications.length === 0" class="empty-state no-notifications">
                <div class="empty-icon">🔔</div>
                <p class="empty-title">No notifications yet</p>
                <p class="empty-copy">Notifications will appear here when new activity occurs.</p>
              </div>

              <div v-else class="notification-list">
                <div
                  v-for="notification in filteredNotifications"
                  :key="notification.id"
                  class="notification-card"
                  :class="notification.read_at ? 'read' : 'unread'"
                  @click="handleNotificationClick(notification)"
                >
                  <div class="notification-icon" :class="getNotificationColor(notification.type)">
                    {{ getNotificationEmoji(notification.type) }}
                  </div>

                  <div class="notification-body">
                    <p class="notification-title">{{ notification.title }}</p>
                    <p class="notification-message">{{ notification.message }}</p>
                    <p class="notification-date">{{ formatDate(notification.created_at) }}</p>
                  </div>

                  <button
                    @click.stop="deleteNotification(notification.id)"
                    class="icon-btn delete"
                    title="Delete"
                  >
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </OubsLayout>

  <RecipientLayout v-else>
    <div class="flex flex-1 flex-row min-h-screen">
      <aside
        class="thin-sidebar flex-shrink-0 flex flex-col transition-all duration-300"
        style="background: linear-gradient(to bottom, #3E2723, #5C3A1E)"
      >
        <nav class="sidebar-nav">
          <div class="sidebar-top">
            <div class="sidebar-brand" title="Recipient">
              <svg class="brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L3 7v7c0 5 4 8 9 8s9-3 9-8V7l-9-5z"></path>
                <path d="M9 12h6"></path>
                <path d="M9 16h6"></path>
              </svg>
            </div>
          </div>

          <div class="sidebar-links">
            <RouterLink
              to="/recipient/home"
              class="icon-link group"
              :class="$route.path === '/recipient/home' ? 'active' : ''"
              @click="closeSidebarOnMobile"
            >
              <svg class="icon-link-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
              </svg>
              <span class="tooltip">Home</span>
            </RouterLink>

            <RouterLink
              to="/notifications"
              class="icon-link"
              :class="$route.path === '/notifications' ? 'active' : ''"
              title="Notifications"
              @click="closeSidebarOnMobile"
            >
              <NotificationBell />
              <span class="tooltip">Notifications</span>
            </RouterLink>

            <RouterLink
              to="/recipient/documents"
              class="icon-link group"
              :class="$route.path === '/recipient/documents' ? 'active' : ''"
              @click="closeSidebarOnMobile"
            >
              <svg class="icon-link-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="9" y1="11" x2="15" y2="11"></line>
                <line x1="9" y1="15" x2="15" y2="15"></line>
              </svg>
              <span class="tooltip">Documents</span>
            </RouterLink>

            <RouterLink
              to="/recipient/my-replies"
              class="icon-link group"
              :class="$route.path === '/recipient/my-replies' ? 'active' : ''"
              @click="closeSidebarOnMobile"
            >
              <svg class="icon-link-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
              </svg>
              <span class="tooltip">Responses</span>
            </RouterLink>
          </div>

          <div class="sidebar-footer">
            <button type="button" class="icon-link logout-button" @click="logout" title="Logout">
              <svg class="icon-link-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
              </svg>
              <span class="tooltip">Logout</span>
            </button>
          </div>
        </nav>
      </aside>

      <main class="flex-1 overflow-x-hidden overflow-y-auto">
        <section class="dashboard-screen">
          <div class="dashboard-content">
            <header class="dashboard-hero">
              
              <h1>Notifications</h1>
              <p class="hero-subtitle">Manage unread notifications, review actions, and jump to relevant pages.</p>
            </header>

            <div class="panel-row">
              <div class="filter-group">
                <button
                  v-for="filter in filters"
                  :key="filter.value"
                  @click="selectedFilter = filter.value"
                  :class="[
                    'filter-pill',
                    selectedFilter === filter.value ? 'active' : ''
                  ]"
                >
                  {{ filter.label }}
                </button>
              </div>

              <button
                v-if="notificationStore.notifications.length > 0"
                @click="deleteAll"
                class="clear-btn"
              >
                Clear All
              </button>
            </div>

            <div class="notifications-panel">
              <div v-if="loading" class="empty-state">
                <div class="spinner" />
                <p>Loading notifications...</p>
              </div>

              <div v-else-if="filteredNotifications.length === 0" class="empty-state no-notifications">
                <div class="empty-icon">🔔</div>
                <p class="empty-title">No notifications yet</p>
                <p class="empty-copy">Notifications will appear here when new activity occurs.</p>
              </div>

              <div v-else class="notification-list">
                <div
                  v-for="notification in filteredNotifications"
                  :key="notification.id"
                  class="notification-card"
                  :class="notification.read_at ? 'read' : 'unread'"
                  @click="handleNotificationClick(notification)"
                >
                  <div class="notification-icon" :class="getNotificationColor(notification.type)">
                    {{ getNotificationEmoji(notification.type) }}
                  </div>

                  <div class="notification-body">
                    <p class="notification-title">{{ notification.title }}</p>
                    <p class="notification-message">{{ notification.message }}</p>
                    <p class="notification-date">{{ formatDate(notification.created_at) }}</p>
                  </div>

                  <button
                    @click.stop="deleteNotification(notification.id)"
                    class="icon-btn delete"
                    title="Delete"
                  >
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </RecipientLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useNotificationStore } from '@/stores/notification'
import { useRouter } from 'vue-router'
import { useAppModal } from '@/composables/useAppModal'
import { useAuthStore } from '@/stores/auth'
import RecipientLayout from '@/components/layout/RecipientLayout.vue'
import OubsLayout from '@/components/layout/OubsLayout.vue'
import NotificationBell from '@/components/common/NotificationBell.vue'

const notificationStore = useNotificationStore()
const router = useRouter()
const { showConfirm } = useAppModal()
const authStore = useAuthStore()
const loading = ref(true)
const selectedFilter = ref('all')

const isOubsUser = computed(() => !['bor', 'uac', 'uadmin'].includes(authStore.user?.user_type))

const filters = [
  { label: 'All', value: 'all' },
  { label: 'Unread', value: 'unread' },
  { label: 'Read', value: 'read' },
]

const closeSidebarOnMobile = () => {
  // Sidebar remains fixed on all screen sizes.
}

const logout = async () => {
  const confirmed = await showConfirm({
    title: 'Logout',
    message: 'Are you sure you want to log out?',
    confirmText: 'Logout',
    cancelText: 'Stay signed in',
  })

  if (!confirmed) return
  try {
    authStore.logout()
    router.push('/')
  } catch (error) {
    console.error('Logout failed:', error)
  }
}

const filteredNotifications = computed(() => {
  if (selectedFilter.value === 'unread') {
    return notificationStore.unreadNotifications
  } else if (selectedFilter.value === 'read') {
    return notificationStore.readNotifications
  }
  return notificationStore.notifications
})

const getNotificationColor = (type) => {
  const colors = {
    welcome: 'blue-500',
    document_new: 'green-500',
    document_upload_success: 'blue-500',
    document_approved: 'green-500',
    document_disapproved: 'red-500',
    reset_request: 'yellow-500',
    document_closed: 'red-500',
    document_review: 'purple-500',
    document_deadline: 'orange-500',
    document_completed: 'emerald-500',
    reply_success: 'teal-500',
  }
  return colors[type] || 'gray-500'
}

const getNotificationEmoji = (type) => {
  const emojis = {
    welcome: '\u{1F44B}',
    document_new: '\u{1F4C4}',
    document_upload_success: '\u{1F4E4}',
    document_approved: '\u{2705}',
    document_disapproved: '\u{274C}',
    reset_request: '\u{1F511}',
    document_closed: '\u{2715}',
    document_review: '\u{1F50D}',
    document_deadline: '\u{23F0}',
    document_completed: '\u{2705}',
    reply_success: '\u{2705}',
  }
  return emojis[type] || '\u{1F4E2}'
}

const formatDate = (date) => {
  if (!date) return 'Unknown'

  let normalizedDate = date
  if (typeof date === 'string') {
    const plainDateRegex = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/
    if (plainDateRegex.test(date)) {
      normalizedDate = date.replace(' ', 'T') + 'Z'
    }
  }

  const d = new Date(normalizedDate)
  if (isNaN(d.getTime())) {
    return 'Unknown'
  }

  const now = new Date()
  const diff = now.getTime() - d.getTime()

  if (diff < 60000) return 'Just now'
  if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago'
  if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago'
  if (diff < 604800000) return Math.floor(diff / 86400000) + 'd ago'

  return d.toLocaleDateString()
}

const markAsRead = async (id) => {
  try {
    await notificationStore.markAsRead(id)
  } catch (error) {
    console.error('Failed to mark as read:', error)
  }
}

const deleteNotification = async (id) => {
  try {
    await notificationStore.deleteNotification(id)
  } catch (error) {
    console.error('Failed to delete notification:', error)
  }
}

const deleteAll = async () => {
  const confirmed = await showConfirm({
    title: 'Delete All Notifications',
    message: 'Are you sure you want to delete all notifications? This action cannot be undone.',
    confirmText: 'Delete All',
    cancelText: 'Keep Notifications',
  })

  if (!confirmed) return

  try {
    await notificationStore.deleteAllNotifications()
  } catch (error) {
    console.error('Failed to delete all notifications:', error)
  }
}

const handleNotificationClick = async (notification) => {
  if (notification.action_url) {
    if (!notification.read_at) {
      await notificationStore.markAsRead(notification.id)
    }

    if (notification.action_url === '/privacy-notice') {
      showPrivacyNotice()
    } else {
      router.push(notification.action_url)
    }
  }
}

const showPrivacyNotice = () => {
  const privacyContent = `
<div class="privacy-panel">
  <p class="privacy-intro">
    By continuing, you acknowledge and agree to the following:
  </p>

  <div class="privacy-section">
    <h4 class="privacy-title">E-Signature Consent</h4>
    <ul class="privacy-list">
      <li>Your uploaded e-signature will be collected and stored in this system.</li>
      <li>Your e-signature will be used as your official signature when you submit responses.</li>
      <li>You are responsible for protecting your account credentials and preventing unauthorized use.</li>
    </ul>
  </div>

  <div class="privacy-section">
    <h4 class="privacy-title">Document Access and Copies</h4>
    <ul class="privacy-list">
      <li>Documents assigned to you may be viewed and downloaded for official university work only.</li>
      <li>Downloaded copies may contain sensitive or confidential information and must be handled securely.</li>
      <li>Sharing, forwarding, or reproducing documents outside authorized recipients is prohibited.</li>
    </ul>
  </div>

  <div class="privacy-section">
    <h4 class="privacy-title">Records and Accountability</h4>
    <ul class="privacy-list">
      <li>Your actions in the system (viewing, downloading, signing, and submitting responses) may be logged for audit and compliance purposes.</li>
    </ul>
  </div>
</div>
`
  showConfirm({
    title: 'Privacy Notice and Consent',
    message: privacyContent,
    confirmText: 'Got It',
    cancelText: 'Close',
  })
}

onMounted(async () => {
  try {
    await notificationStore.fetchNotifications()
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.dashboard-screen {
  width: 100%;
  min-height: 100vh;
  flex: 1;
  display: flex;
  flex-direction: column;
  border-radius: 0;
  border: 0;
  background:
    radial-gradient(circle at 20% 0%, rgba(212, 160, 23, 0.16) 0%, transparent 40%),
    linear-gradient(140deg, #1b5e20 0%, #15471a 42%, #0d3715 100%);
  box-shadow: none;
}

.dashboard-content {
  width: 100%;
  min-height: 0;
  flex: 1;
  padding: clamp(0.8rem, 1.8vw, 1.3rem);
  display: flex;
  flex-direction: column;
}

.dashboard-hero {
  border: 1px solid rgba(212, 160, 23, 0.6);
  border-radius: 0.75rem;
  padding: clamp(0.8rem, 1.5vw, 1.1rem);
  background: rgba(10, 10, 10, 0.24);
  color: #fff8e7;
  flex-shrink: 0;
}

.hero-kicker {
  font-size: 0.78rem;
  letter-spacing: 0.18em;
  font-weight: 700;
  color: #f1d488;
  text-transform: uppercase;
}

.dashboard-hero h1 {
  margin-top: 0.35rem;
  font-size: clamp(1.8rem, 4.1vw, 3.2rem);
  line-height: 1.05;
  font-weight: 800;
}

.hero-subtitle {
  margin-top: 0.45rem;
  color: #f6ead0;
  font-size: clamp(0.98rem, 1.5vw, 1.18rem);
}

.panel-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
  justify-content: space-between;
  margin-top: 1rem;
  flex-shrink: 0;
}

.filter-group {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
}

.filter-pill {
  border-radius: 9999px;
  padding: 0.75rem 1.1rem;
  font-size: 0.95rem;
  border: 1px solid rgba(255,255,255,0.15);
  background: rgba(255,255,255,0.08);
  color: #f5f0e6;
  cursor: pointer;
  transition: all 180ms ease;
}

.filter-pill.active,
.filter-pill:hover {
  background: rgba(212, 160, 23, 0.95);
  color: #1f1f1f;
  border-color: transparent;
}

.clear-btn {
  border-radius: 9999px;
  padding: 0.75rem 1.2rem;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.12);
  color: #ffe4b3;
  border: 1px solid rgba(255,255,255,0.18);
  transition: all 180ms ease;
}

.clear-btn:hover {
  background: rgba(255, 255, 255, 0.22);
}

.notifications-panel {
  margin-top: 1.25rem;
  min-height: 16rem;
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.empty-state {
  display: grid;
  place-items: center;
  gap: 0.75rem;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255,255,255,0.18);
  border-radius: 1rem;
  padding: 3rem 2rem;
  color: #f7f1df;
  text-align: center;
}

.empty-state.no-notifications {
  min-height: 18rem;
}

.empty-icon {
  width: 3rem;
  height: 3rem;
  display: grid;
  place-items: center;
  font-size: 2rem;
  background: rgba(255,255,255,0.14);
  border-radius: 9999px;
}

.empty-title {
  font-size: 1.15rem;
  font-weight: 700;
}

.empty-copy {
  color: rgba(255,255,255,0.8);
  max-width: 32rem;
}

.notification-list {
  display: grid;
  gap: 0.85rem;
}

.notification-card {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 1rem;
  align-items: center;
  padding: 1rem 1.1rem;
  border-radius: 1rem;
  background: rgba(255,255,255,0.12);
  border: 1px solid rgba(255,255,255,0.16);
  transition: transform 180ms ease, border-color 180ms ease, box-shadow 180ms ease;
  cursor: pointer;
}

.notification-card:hover {
  transform: translateY(-1px);
  border-color: rgba(212, 160, 23, 0.7);
  box-shadow: 0 18px 50px rgba(0, 0, 0, 0.08);
}

.notification-icon {
  width: 3rem;
  height: 3rem;
  min-width: 3rem;
  border-radius: 1rem;
  display: grid;
  place-items: center;
  color: #ffffff;
  font-weight: 700;
}

.notification-content {
  min-width: 0;
}

.notification-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.notification-header h3 {
  font-size: 1rem;
  font-weight: 700;
  color: #fff;
}

.notification-message {
  margin-top: 0.35rem;
  color: rgba(255,255,255,0.78);
  font-size: 0.95rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.unread-badge {
  width: 0.8rem;
  height: 0.8rem;
  border-radius: 9999px;
  margin-top: 0.25rem;
}

.notification-time {
  margin-top: 0.6rem;
  color: rgba(255,255,255,0.64);
  font-size: 0.84rem;
}

.notification-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.icon-btn {
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 0.9rem;
  border: 1px solid rgba(255,255,255,0.16);
  background: rgba(255,255,255,0.08);
  color: #fff;
  display: grid;
  place-items: center;
  transition: all 180ms ease;
}

.icon-btn:hover {
  background: rgba(255,255,255,0.18);
}

.icon-btn.delete {
  color: #ffbcbb;
}

.icon {
  width: 1rem;
  height: 1rem;
}

.privacy-panel {
  padding: 1rem;
  color: #333;
}

.privacy-title {
  margin-bottom: 0.5rem;
  font-weight: 700;
}

.privacy-list {
  padding-left: 1.2rem;
}

.privacy-list li {
  margin-bottom: 0.35rem;
}

/* Sidebar Styles */
.thin-sidebar {
  width: 4.5rem;
  min-width: 4.5rem;
  max-width: 4.5rem;
  padding: 0.75rem 0;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  height: 100%;
  gap: 1rem;
}

.sidebar-top {
  display: flex;
  justify-content: center;
}

.sidebar-brand {
  width: 2.75rem;
  height: 2.75rem;
  display: grid;
  place-items: center;
  border-radius: 1rem;
  background: rgba(255,255,255,0.08);
  color: #f5f0e6;
  border: 1px solid rgba(255,255,255,0.16);
}

.brand-icon {
  width: 1.45rem;
  height: 1.45rem;
}

.sidebar-links {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding-top: 1rem;
}

.icon-link {
  position: relative;
  width: 3rem;
  height: 3rem;
  display: grid;
  place-items: center;
  border-radius: 1rem;
  border: 1px solid transparent;
  background: rgba(255,255,255,0.08);
  color: #fff;
  transition: background 160ms ease, border-color 160ms ease, transform 160ms ease;
  cursor: pointer;
  text-decoration: none;
}

.icon-link:hover,
.icon-link.active {
  background: rgba(255,255,255,0.16);
  border-color: rgba(212,160,23,0.6);
  transform: translateY(-1px);
}

.icon-link-svg {
  width: 1.45rem;
  height: 1.45rem;
}

.tooltip {
  position: absolute;
  left: 100%;
  top: 50%;
  transform: translate(0, -50%);
  white-space: nowrap;
  padding: 0.35rem 0.65rem;
  border-radius: 9999px;
  background: rgba(31,41,55,0.92);
  color: #fff;
  font-size: 0.8rem;
  opacity: 0;
  pointer-events: none;
  transition: opacity 160ms ease, transform 160ms ease;
  margin-left: 0.8rem;
}

.icon-link:hover .tooltip {
  opacity: 1;
  transform: translate(0.2rem, -50%);
}

.sidebar-footer {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding-bottom: 1rem;
}

.logout-button {
  background: rgba(255,255,255,0.08);
  border: 1px solid transparent;
}

.logout-button:hover {
  border-color: rgba(239, 68, 68, 0.5);
}

@media (max-width: 1023px) {
  .tooltip {
    display: none;
  }
}
</style>
