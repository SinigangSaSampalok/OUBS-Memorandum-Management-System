<template>
  <section class="logs-screen">
    <header class="logs-header">
      <p class="logs-kicker">OUBS LOGS</p>
      <h1>Sign-In Logs</h1>
      <p class="logs-subtitle">Shows date and time of OUBS account sign-ins.</p>
    </header>

    <article class="logs-card">
      <div class="logs-toolbar">
        <p>Total Records: <strong>{{ totalLogsCount }}</strong></p>
        <button type="button" class="refresh-btn" :disabled="isLoading" @click="loadLogs">
          {{ isLoading ? 'Loading...' : 'Refresh' }}
        </button>
      </div>

      <p v-if="errorMessage" class="error-text">{{ errorMessage }}</p>

      <div class="tables-grid">
        <section class="table-section">
          <h2>OUBS Logs</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Username</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!isLoading && groupedLogs.oubs.length === 0">
                  <td colspan="5" class="empty-cell">No OUBS logs yet.</td>
                </tr>
                <tr v-for="(item, idx) in groupedLogs.oubs" :key="`oubs-${item.id}`">
                  <td>{{ idx + 1 }}</td>
                  <td>{{ item.full_name || '-' }}</td>
                  <td>{{ formatDate(item.logged_in_at) }}</td>
                  <td>{{ formatTime(item.logged_in_at) }}</td>
                  <td>{{ item.username }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="table-section">
          <h2>BOR Logs</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Username</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!isLoading && groupedLogs.bor.length === 0">
                  <td colspan="5" class="empty-cell">No BOR logs yet.</td>
                </tr>
                <tr v-for="(item, idx) in groupedLogs.bor" :key="`bor-${item.id}`">
                  <td>{{ idx + 1 }}</td>
                  <td>{{ item.full_name || '-' }}</td>
                  <td>{{ formatDate(item.logged_in_at) }}</td>
                  <td>{{ formatTime(item.logged_in_at) }}</td>
                  <td>{{ item.username }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="table-section">
          <h2>UAC Logs</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Username</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!isLoading && groupedLogs.uac.length === 0">
                  <td colspan="5" class="empty-cell">No UAC logs yet.</td>
                </tr>
                <tr v-for="(item, idx) in groupedLogs.uac" :key="`uac-${item.id}`">
                  <td>{{ idx + 1 }}</td>
                  <td>{{ item.full_name || '-' }}</td>
                  <td>{{ formatDate(item.logged_in_at) }}</td>
                  <td>{{ formatTime(item.logged_in_at) }}</td>
                  <td>{{ item.username }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="table-section">
          <h2>UADMIN Logs</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Username</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!isLoading && groupedLogs.uadmin.length === 0">
                  <td colspan="5" class="empty-cell">No UADMIN logs yet.</td>
                </tr>
                <tr v-for="(item, idx) in groupedLogs.uadmin" :key="`uadmin-${item.id}`">
                  <td>{{ idx + 1 }}</td>
                  <td>{{ item.full_name || '-' }}</td>
                  <td>{{ formatDate(item.logged_in_at) }}</td>
                  <td>{{ formatTime(item.logged_in_at) }}</td>
                  <td>{{ item.username }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { authService } from '@/services/api'

const logs = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const groupedLogs = computed(() => ({
  oubs: logs.value.filter((item) => item.user_type === 'oubs'),
  bor: logs.value.filter((item) => item.user_type === 'bor'),
  uac: logs.value.filter((item) => item.user_type === 'uac'),
  uadmin: logs.value.filter((item) => item.user_type === 'uadmin'),
}))
const totalLogsCount = computed(
  () => groupedLogs.value.oubs.length + groupedLogs.value.bor.length + groupedLogs.value.uac.length + groupedLogs.value.uadmin.length
)

const formatDate = (value) => {
  if (!value) return '-'
  const date = new Date(value.replace(' ', 'T'))
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString()
}

const formatTime = (value) => {
  if (!value) return '-'
  const date = new Date(value.replace(' ', 'T'))
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleTimeString()
}

const loadLogs = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await authService.loginLogs()
    logs.value = response?.data || []
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to load logs.'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadLogs)
</script>

<style scoped>
.logs-screen {
  width: 100%;
  min-height: 100%;
  padding: 1rem;
  background:
    radial-gradient(circle at 20% 0%, rgba(212, 160, 23, 0.2) 0%, transparent 40%),
    linear-gradient(140deg, #1b5e20 0%, #15471a 42%, #0d3715 100%);
  color: #fff8e7;
}

.logs-header {
  border: 1px solid rgba(212, 160, 23, 0.6);
  border-radius: 0.75rem;
  padding: 1rem;
  background: rgba(10, 10, 10, 0.24);
}

.logs-kicker {
  font-size: 0.78rem;
  letter-spacing: 0.18em;
  font-weight: 700;
  color: #f1d488;
  text-transform: uppercase;
}

.logs-header h1 {
  margin-top: 0.35rem;
  font-size: clamp(1.4rem, 3vw, 2.2rem);
  font-weight: 800;
}

.logs-subtitle {
  margin-top: 0.35rem;
  color: #f6ead0;
}

.logs-card {
  margin-top: 0.9rem;
  border: 1px solid rgba(212, 160, 23, 0.55);
  border-radius: 0.75rem;
  padding: 0.9rem;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.1), rgba(12, 12, 12, 0.2));
}

.logs-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
  flex-wrap: wrap;
}

.refresh-btn {
  border: 1px solid #f0c04f;
  border-radius: 0.5rem;
  padding: 0.45rem 0.9rem;
  color: #fff8e5;
  background: linear-gradient(to bottom right, #6f1717, #4a0f0f);
  font-weight: 700;
}

.refresh-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.error-text {
  margin-top: 0.65rem;
  color: #ffd2d2;
}

.table-wrap {
  margin-top: 0.55rem;
  overflow-x: auto;
}

.tables-grid {
  margin-top: 0.75rem;
  display: grid;
  gap: 0.85rem;
}

.table-section {
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 0.6rem;
  padding: 0.7rem;
  background: rgba(0, 0, 0, 0.18);
}

.table-section h2 {
  font-size: 0.95rem;
  color: #f3ce74;
  font-weight: 700;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  border-bottom: 1px solid rgba(255, 255, 255, 0.18);
  padding: 0.65rem 0.5rem;
  text-align: left;
  font-size: 0.9rem;
}

th {
  color: #f3ce74;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.empty-cell {
  text-align: center;
  color: #f5ebd5;
}
</style>
