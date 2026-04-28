<template>
  <OubsLayout>
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
        <RouterView />
      </main>
    </div>
  </OubsLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAppModal } from '@/composables/useAppModal'
import OubsLayout from '@/components/layout/OubsLayout.vue'
import NotificationBell from '@/components/common/NotificationBell.vue'

const authStore = useAuthStore()
const router = useRouter()
const { showConfirm } = useAppModal()

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

onMounted(() => {
  // No sidebar toggling needed: display is fixed.
})
</script>
<style scoped>
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

.sidebar-header {
  display: none;
}

.sidebar-header h1 {
  margin: 0;
  padding: 0;
  font-size: 1rem;
  font-weight: 700;
  text-align: center;
  line-height: 1.4;
  color: #D4A017;
  text-transform: uppercase;
  letter-spacing: 0.05em;
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
  .nav-label {
    display: none !important;
  }

  .tooltip {
    display: none;
  }
}
</style>