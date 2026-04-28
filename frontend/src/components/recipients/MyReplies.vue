<template>
  <section class="manager-screen">
    <header class="manager-hero">
      <p class="hero-kicker">OUBS PORTAL</p>
      <h1>{{ pageTitle }}</h1>
      <p class="hero-subtitle">{{ pageSubtitle }}</p>
    </header>

    <article class="manager-card">
      <div class="manager-toolbar">
        <div></div>
        <button
          type="button"
          class="refresh-btn"
          :disabled="isLoading"
          @click="fetchReplies"
        >
          {{ isLoading ? 'Refreshing...' : 'Refresh' }}
        </button>
      </div>

      <div class="filter-section">
        <div class="filter-group">
          <label class="filter-label">Search</label>
          <div class="search-input-wrapper">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input
              v-model="searchTerm"
              type="text"
              class="search-input"
              placeholder="Search by document name, number..."
            />
          </div>
        </div>
        <div class="filter-group">
          <label class="filter-label">Filter by Month</label>
          <input
            v-model="selectedMonth"
            type="month"
            class="month-input"
          />
        </div>
      </div>

      <div class="manager-card-body pt-2 pb-4">
        <div v-if="errorMessage" class="text-sm mb-4" style="color: #ffd2d2;">
          {{ errorMessage }}
        </div>

        <div v-if="isLoading" class="py-6" style="color: #f6ead0;">
          Loading {{ listLabel }}...
        </div>

        <div v-if="!isLoading" class="pb-6 mt-2">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Document No.</th>
                  <th>Document Name</th>
                  <th>Action</th>
                  <th>{{ commentColumnLabel }}</th>
                  <th>Date Signed</th>
                  <th v-if="isBorUser" class="narrow-header">PDF</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredReplies.length === 0">
                  <td :colspan="isBorUser ? 6 : 5" class="empty-cell" style="text-align: center;">
                    {{ replies.length === 0 ? `No ${listLabel} submitted yet.` : 'No entries match your search or filter.' }}
                  </td>
                </tr>
                <tr v-for="reply in filteredReplies" :key="reply.id">
                  <td style="white-space: nowrap; text-align: center;">{{ reply.document_number || '-' }}</td>
                  <td class="doc-cell" style="text-align: left;">
                    <span class="doc-title" :title="reply.title || ''">{{ reply.title || '-' }}</span>
                  </td>
                  <td>
                    <span
                      class="status-pill"
                      :class="reply.action === 'approve' ? 'pill-allowed' : 'pill-not-allowed'"
                    >
                      {{ reply.action === 'approve' ? 'Approved' : 'Disapproved' }}
                    </span>
                  </td>
                  <td class="doc-cell">
                    <span class="doc-remarks">{{ reply.remarks || '-' }}</span>
                  </td>
                  <td style="white-space: nowrap; text-align: center;">{{ formatDate(reply.date_signed || reply.created_at) }}</td>
                  <td v-if="isBorUser" class="actions-cell" style="text-align: center;">
                    <div class="flex items-center gap-2">
                      <button
                        class="action-btn"
                        :disabled="actionLoadingId === reply.id"
                        @click="viewReplyPdf(reply)"
                      >
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"></path>
                          <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        View
                      </button>
                      <button
                        class="action-btn"
                        :disabled="actionLoadingId === reply.id"
                        @click="downloadReplyPdf(reply)"
                      >
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path d="M12 3v12"></path>
                          <path d="m7 10 5 5 5-5"></path>
                          <path d="M4 21h16"></path>
                        </svg>
                        Download
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </article>
  </section>

  <!-- Reply Slip Viewer Modal -->
  <div v-if="viewer.open" class="modal-overlay">
    <div class="modal-dialog">
      <div class="modal-header">
        <div class="modal-title">{{ viewer.title || 'Reply Slip Viewer' }}</div>
        <button class="modal-btn-close" @click="closeViewer">Close</button>
      </div>
      <div class="modal-body">
        <iframe
          v-if="viewer.url"
          :src="viewer.url"
          class="w-full h-full"
          title="Reply Slip Viewer"
        ></iframe>
        <div v-else class="modal-loading">Loading reply document...</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { replySlipService } from '@/services/api';
import { useAppModal } from '@/composables/useAppModal';

const replies = ref([]);
const isLoading = ref(false);
const actionLoadingId = ref(null);
const errorMessage = ref('');
const searchTerm = ref('');
const selectedMonth = ref('');
const { showError } = useAppModal();
const authStore = useAuthStore();

const viewer = reactive({
  open: false,
  url: '',
  title: '',
});

const isBorUser = computed(() => authStore.user?.user_type === 'bor');
const pageTitle = computed(() => (isBorUser.value ? 'My Replies' : 'Responses'));
const pageSubtitle = computed(() => (isBorUser.value ? 'View your submitted reply slips.' : 'View your submitted responses.'));
const listLabel = computed(() => (isBorUser.value ? 'replies' : 'responses'));
const commentColumnLabel = computed(() => (isBorUser.value ? 'Remarks' : 'Comments'));

const filteredReplies = computed(() => {
  return replies.value.filter(reply => {
    // Search filter
    const search = searchTerm.value.toLowerCase();
    if (search) {
      const title = (reply.title || '').toLowerCase();
      const docNumber = (reply.document_number || '').toLowerCase();
      if (!title.includes(search) && !docNumber.includes(search)) {
        return false;
      }
    }

    // Month filter
    if (selectedMonth.value) {
      const replyDate = reply.date_signed || reply.created_at || reply.updated_at;
      if (replyDate) {
        const replyMonth = replyDate.substring(0, 7); // YYYY-MM format
        if (replyMonth !== selectedMonth.value) {
          return false;
        }
      }
    }

    return true;
  });
});

const buildPdfViewerUrl = (blobUrl) => `${blobUrl}#zoom=100`;

const formatDate = (value) => {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
};

const isBorReply = (reply) => reply?.recipient_type === 'bor';

const fetchReplies = async () => {
  errorMessage.value = '';
  isLoading.value = true;
  try {
    const response = await replySlipService.myReplies();
    replies.value = response?.data || [];
  } catch (error) {
    errorMessage.value = error?.message || `Failed to load ${listLabel.value}.`;
    await showError(errorMessage.value, 'Load Failed');
  } finally {
    isLoading.value = false;
  }
};

const fetchReplyPdfBlob = async (reply) => {
  if (!reply?.id) throw new Error('Invalid reply slip ID.');
  if (!isBorReply(reply)) throw new Error('PDF copy is only available for BOR replies.');
  actionLoadingId.value = reply.id;
  try {
    const blob = await replySlipService.download(reply.id);
    return blob;
  } finally {
    actionLoadingId.value = null;
  }
};

const viewReplyPdf = async (reply) => {
  try {
    const blob = await fetchReplyPdfBlob(reply);
    const url = URL.createObjectURL(blob);
    if (viewer.url) URL.revokeObjectURL(viewer.url.split('#')[0]);
    viewer.open = true;
    viewer.url = buildPdfViewerUrl(url);
    viewer.title = `Reply Slip - ${reply.document_number || reply.id}`;
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to open reply document.';
    await showError(errorMessage.value, 'Open Failed');
  }
};

const downloadReplyPdf = async (reply) => {
  try {
    const blob = await fetchReplyPdfBlob(reply);
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `reply-slip-${reply.document_number || reply.id}.pdf`;
    link.click();
    URL.revokeObjectURL(url);
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to download reply document.';
    await showError(errorMessage.value, 'Download Failed');
  }
};

const closeViewer = () => {
  if (viewer.url) URL.revokeObjectURL(viewer.url.split('#')[0]);
  viewer.open = false;
  viewer.url = '';
  viewer.title = '';
};

onMounted(fetchReplies);
onBeforeUnmount(() => { closeViewer(); });
</script>

<style scoped>
/* ── Page shell ───────────────────────────────────────────── */
.manager-screen {
  width: 100%;
  min-height: 100%;
  padding: clamp(0.8rem, 1.8vw, 1.3rem);
  background:
    radial-gradient(circle at 20% 0%, rgba(212, 160, 23, 0.2) 0%, transparent 40%),
    linear-gradient(140deg, #1b5e20 0%, #15471a 42%, #0d3715 100%);
  color: #fff8e7;
}

/* ── Hero header ──────────────────────────────────────────── */
.manager-hero {
  border: 1px solid rgba(212, 160, 23, 0.6);
  border-radius: 0.75rem;
  padding: clamp(0.8rem, 1.5vw, 1.1rem);
  background: rgba(10, 10, 10, 0.24);
}

.hero-kicker {
  font-size: 0.78rem;
  letter-spacing: 0.18em;
  font-weight: 700;
  color: #f1d488;
  text-transform: uppercase;
}

.manager-hero h1 {
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

/* ── Card ─────────────────────────────────────────────────── */
.manager-card {
  margin-top: 0.9rem;
  border: 1px solid rgba(212, 160, 23, 0.55);
  border-radius: 0.75rem;
  padding: 0.95rem;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.1), rgba(12, 12, 12, 0.2));
}

.manager-toolbar {
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
  font-size: 0.85rem;
}

.refresh-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.manager-card-body {
  margin-top: 0.75rem;
}

/* ── Table ────────────────────────────────────────────────── */
.table-wrap {
  margin-top: 0.75rem;
  overflow-x: auto;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 0.6rem;
  background: rgba(0, 0, 0, 0.18);
}

table {
  width: 100%;
  border-collapse: collapse;
  table-layout: auto;
}

th,
td {
  border-bottom: 1px solid rgba(255, 255, 255, 0.18);
  padding: 0.65rem 0.75rem;
  text-align: center;
  font-size: 0.95rem;
  vertical-align: middle;
}

th {
  color: #f3ce74;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 800;
  white-space: nowrap;
  font-size: 0.82rem;
}

/* Document No. — compact, no wrap */
th:nth-child(1), td:nth-child(1) {
  text-align: center;
  white-space: nowrap;
  min-width: 8rem;
}

/* Document Name — allow growth, truncate overflow */
th:nth-child(2), td:nth-child(2) {
  text-align: left;
  min-width: 10rem;
  max-width: 18rem;
}

/* Action — pill fits on one line */
th:nth-child(3), td:nth-child(3) {
  text-align: center;
  white-space: nowrap;
  min-width: 7rem;
}

/* Remarks / Comments — allow growth, truncate overflow */
th:nth-child(4), td:nth-child(4) {
  text-align: left;
  min-width: 10rem;
  max-width: 18rem;
}

/* Date Signed — no wrap */
th:nth-child(5), td:nth-child(5) {
  text-align: center;
  white-space: nowrap;
  min-width: 9rem;
}

/* PDF actions — no wrap */
th:nth-child(6), td:nth-child(6) {
  text-align: center;
  white-space: nowrap;
  min-width: 9rem;
}

tbody tr:last-child td {
  border-bottom: 0;
}

.empty-cell {
  padding: 1.2rem 0.5rem;
  color: #f6ead0;
  text-align: center;
}

.doc-cell {
  overflow: hidden;
  text-align: left;
  /* max-width is set per-column via nth-child above */
}

.doc-title {
  font-weight: 800;
  color: #fff8e7;
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.doc-remarks {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #f6ead0;
  font-size: 0.9rem;
}

.actions-cell {
  white-space: nowrap;
}

/* ── Action buttons ───────────────────────────────────────── */
.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  height: 2rem;
  padding: 0 0.65rem;
  border-radius: 0.5rem;
  border: 1px solid rgba(240, 192, 79, 0.55);
  background: rgba(0, 0, 0, 0.18);
  color: #fff8e5;
  font-size: 0.75rem;
  font-weight: 700;
  transition: filter 120ms ease;
}

.action-btn:hover:not(:disabled) {
  filter: brightness(1.12);
}

.action-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.btn-icon {
  width: 0.875rem;
  height: 0.875rem;
  flex-shrink: 0;
}

/* ── Status pills ─────────────────────────────────────────── */
.status-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  border: 1px solid transparent;
}

.pill-allowed {
  border-color: rgba(140, 255, 196, 0.45);
  background: rgba(0, 90, 36, 0.22);
  color: #d7ffe7;
}

.pill-not-allowed {
  border-color: rgba(255, 156, 156, 0.5);
  background: rgba(120, 0, 0, 0.24);
  color: #ffd2d2;
}

/* ── Modal ────────────────────────────────────────────────── */
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 50;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: stretch;
  justify-content: center;
}

.modal-dialog {
  display: flex;
  flex-direction: column;
  width: 100%;
  height: 100%;
  background: linear-gradient(140deg, #1b5e20 0%, #15471a 42%, #0d3715 100%);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.9rem 1.2rem;
  background: rgba(10, 10, 10, 0.3);
  border-bottom: 1px solid rgba(212, 160, 23, 0.4);
  flex-shrink: 0;
}

.modal-title {
  font-size: 0.95rem;
  font-weight: 800;
  color: #fff8e7;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.modal-btn-close {
  height: 2.25rem;
  min-width: 5rem;
  padding: 0 0.9rem;
  border-radius: 0.5rem;
  border: 1px solid #f0c04f;
  background: linear-gradient(to bottom right, #6f1717, #4a0f0f);
  color: #fff8e5;
  font-size: 0.875rem;
  font-weight: 700;
  transition: filter 120ms ease;
  flex-shrink: 0;
}

.modal-btn-close:hover {
  filter: brightness(1.1);
}

.modal-body {
  flex: 1;
  overflow: auto;
  background: rgba(0, 0, 0, 0.2);
}

.modal-loading {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #f6ead0;
  font-size: 0.95rem;
}

.filter-section {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 1rem;
  padding: 0.75rem 0;
  margin-bottom: 0.75rem;
  flex-wrap: wrap;
}

@media (max-width: 768px) {
  .filter-section {
    grid-template-columns: 1fr;
  }
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.filter-label {
  font-size: 0.78rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #f3ce74;
  font-weight: 700;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  width: 18px;
  height: 18px;
  color: rgba(212, 160, 23, 0.6);
  pointer-events: none;
}

.search-input {
  width: 100%;
  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 0.6rem;
  padding: 0.65rem 0.9rem 0.65rem 2.5rem;
  background: rgba(0, 0, 0, 0.18);
  color: #fff8e7;
  font-size: 0.95rem;
  outline: none;
  transition: border-color 120ms ease, box-shadow 120ms ease;
}

.search-input::placeholder {
  color: rgba(246, 234, 208, 0.6);
}

.search-input:focus {
  border-color: rgba(240, 192, 79, 0.95);
  box-shadow: 0 0 0 3px rgba(240, 192, 79, 0.2);
}

.month-input {
  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 0.6rem;
  padding: 0.65rem 0.9rem;
  background: rgba(0, 0, 0, 0.18);
  color: #fff8e7;
  font-size: 0.95rem;
  outline: none;
  transition: border-color 120ms ease, box-shadow 120ms ease;
  min-width: 180px;
}

.month-input:focus {
  border-color: rgba(240, 192, 79, 0.95);
  box-shadow: 0 0 0 3px rgba(240, 192, 79, 0.2);
}

.month-input::placeholder {
  color: rgba(246, 234, 208, 0.6);
}
</style>