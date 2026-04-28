<template>
  <section class="dashboard-screen">
    <header class="dashboard-hero">
      <p class="hero-kicker">OUBS PORTAL</p>
      <h1>Dashboard Control Center</h1>
      <p class="hero-subtitle">Manage records, review progress, and keep decisions transparent.</p>
    </header>

    <div class="top-grid">
      <article class="chart-card">
        <p class="stat-label">Completion Rate</p>
        <div class="chart-wrap">
          <div class="chart-ring" :style="ringStyle(completionRate)">
            <span>{{ completionRate }}%</span>
          </div>
          <p class="stat-copy">Completed documents over total documents.</p>
        </div>
      </article>
      <article class="chart-card">
        <p class="stat-label">Signed Reply Rate</p>
        <div class="chart-wrap">
          <div class="chart-ring" :style="ringStyle(signedRate)">
            <span>{{ signedRate }}%</span>
          </div>
          <p class="stat-copy">Signed replies against uploaded documents.</p>
        </div>
      </article>
    </div>

    <div class="stats-grid">
      <article v-for="item in statCards" :key="item.label" class="stat-card compact">
        <p class="stat-label">{{ item.label }}</p>
        <h2>{{ isLoadingStats ? '...' : item.value }}</h2>
      </article>
    </div>

    <div class="workflow-grid">
      <article class="stat-card workflow">
        <h3>Document Management</h3>
        <p class="stat-copy">Upload, organize, and control memorandum files.</p>
      </article>
      <article class="stat-card workflow">
        <h3>Workflow Tracking</h3>
        <p class="stat-copy">Track approvals, disapprovals, and pending actions.</p>
      </article>
      <article class="stat-card workflow">
        <h3>Transparency</h3>
        <p class="stat-copy">See a clear audit trail of signatures and dates.</p>
      </article>
    </div>

  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAppModal } from '@/composables/useAppModal'
import { dashboardService } from '@/services/api'

const router = useRouter()
const authStore = useAuthStore()
const { showConfirm } = useAppModal()
const isLoadingStats = ref(false)
const stats = ref({
  uploaded_documents: 0,
  total_documents: 0,
  signed_replies: 0,
  pending_documents: 0,
  completed_documents: 0,
  closed_documents: 0,
})

const safePercent = (value, total) => {
  if (!total || total <= 0) return 0
  return Math.max(0, Math.min(100, Math.round((value / total) * 100)))
}

const completionRate = computed(() => safePercent(stats.value.completed_documents ?? 0, stats.value.total_documents ?? 0))
const signedRate = computed(() => safePercent(stats.value.signed_replies ?? 0, stats.value.uploaded_documents ?? 0))

const statCards = computed(() => ([
  {
    label: 'Uploaded Documents',
    value: stats.value.uploaded_documents ?? 0,
    hint: 'Total files uploaded and available in the system.',
  },
  {
    label: 'Signed Replies',
    value: stats.value.signed_replies ?? 0,
    hint: 'All submitted signed replies from recipients.',
  },
  {
    label: 'Pending Documents',
    value: stats.value.pending_documents ?? 0,
    hint: 'Documents still waiting for completion.',
  },
  {
    label: 'Completed Documents',
    value: stats.value.completed_documents ?? 0,
    hint: 'Documents with all required replies completed.',
  },
  {
    label: 'Closed Documents',
    value: stats.value.closed_documents ?? 0,
    hint: 'Documents already closed by deadline.',
  },
  {
    label: 'Total Documents',
    value: stats.value.total_documents ?? 0,
    hint: 'All tracked documents across recipient groups.',
  },
]))

const ringStyle = (percent) => ({
  background: `conic-gradient(#f0c04f ${percent}%, rgba(255,255,255,0.16) ${percent}% 100%)`,
})

const loadStats = async () => {
  isLoadingStats.value = true
  try {
    const response = await dashboardService.stats()
    stats.value = {
      ...stats.value,
      ...(response?.data || {}),
    }
  } catch (error) {
    // Keep dashboard usable even if stats API fails.
  } finally {
    isLoadingStats.value = false
  }
}

const logout = async () => {
  const confirmed = await showConfirm({
    title: 'Logout',
    message: 'Are you sure you want to log out from OUBS?',
    confirmText: 'Logout',
    cancelText: 'Stay',
  })
  if (!confirmed) return

  authStore.logout()
  router.push('/')
}

onMounted(loadStats)
</script>

<style scoped>
.dashboard-screen {
  width: 100%;
  min-height: 100%;
  padding: clamp(0.8rem, 1.8vw, 1.3rem);
  border-radius: 0;
  border: 0;
  background:
    radial-gradient(circle at 20% 0%, rgba(212, 160, 23, 0.2) 0%, transparent 40%),
    linear-gradient(140deg, #1b5e20 0%, #15471a 42%, #0d3715 100%);
  box-shadow: none;
}

.dashboard-hero {
  border: 1px solid rgba(212, 160, 23, 0.6);
  border-radius: 0.75rem;
  padding: clamp(0.8rem, 1.5vw, 1.1rem);
  background: rgba(10, 10, 10, 0.24);
  color: #fff8e7;
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

.top-grid {
  margin-top: 0.75rem;
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
}

.chart-card {
  border: 1px solid rgba(212, 160, 23, 0.55);
  border-radius: 0.75rem;
  padding: 0.8rem;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.1), rgba(12, 12, 12, 0.2));
  color: #fff7e2;
}

.chart-wrap {
  margin-top: 0.3rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.chart-ring {
  width: 84px;
  height: 84px;
  border-radius: 9999px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.chart-ring span {
  width: 66px;
  height: 66px;
  border-radius: 9999px;
  display: grid;
  place-items: center;
  background: rgba(13, 33, 14, 0.88);
  color: #fff4d3;
  font-size: 0.9rem;
  font-weight: 700;
}

.stats-grid {
  margin-top: 0.75rem;
  display: grid;
  gap: 0.7rem;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
}

.workflow-grid {
  margin-top: 0.75rem;
  display: grid;
  gap: 0.7rem;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
}

.stat-card {
  border: 1px solid rgba(212, 160, 23, 0.55);
  border-radius: 0.75rem;
  padding: 0.8rem;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.1), rgba(12, 12, 12, 0.2));
  color: #fff7e2;
}

.stat-label {
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #f3ce74;
}

.stat-card h2 {
  margin-top: 0.25rem;
  font-size: clamp(1.15rem, 1.6vw, 1.5rem);
  font-weight: 800;
}

.stat-card h3 {
  font-size: 1.05rem;
  font-weight: 700;
}

.stat-copy {
  margin-top: 0.3rem;
  color: #f5ebd5;
  font-size: 0.9rem;
}

.compact {
  min-height: 94px;
}

.workflow {
  min-height: 110px;
}

.action-bar {
  display: flex;
  justify-content: center;
  padding-top: 0.8rem;
}

.logout-btn {
  border: 1px solid #f0c04f;
  border-radius: 0.6rem;
  min-width: 170px;
  padding: 0.75rem 1.2rem;
  color: #fff8e5;
  font-size: 1rem;
  font-weight: 700;
  background: linear-gradient(to bottom right, #6f1717, #4a0f0f);
}

.logout-btn:hover {
  filter: brightness(1.07);
}
</style>

