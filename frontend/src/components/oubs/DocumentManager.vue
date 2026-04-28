<template>
  <section class="manager-screen">
    <header class="manager-hero">
      <p class="hero-kicker">OUBS PORTAL</p>
      <h1>Document Manager</h1>
      <p class="hero-subtitle">Review, download, and manage uploaded memorandums.</p>
    </header>

    <article class="manager-card">
      <div class="manager-toolbar">
        <div class="tabs" role="tablist" aria-label="Recipient type">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            class="tab-btn"
            :class="activeTab === tab.value && 'tab-btn-active'"
            @click="changeTab(tab.value)"
          >
            {{ tab.label }}
          </button>
        </div>

        <button
          type="button"
          class="refresh-btn"
          :disabled="isLoading"
          @click="refreshActiveTab"
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

      <p v-if="errorMessage" class="error-text">{{ errorMessage }}</p>
      <div v-if="isLoading" class="loading-text">Loading documents...</div>

      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Document Name</th>
              <th>Document No.</th>
              <th>Deadline</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredDocuments.length === 0">
              <td colspan="4" class="empty-cell">
                {{ documents.length === 0 ? 'No documents found. Upload a PDF to get started.' : 'No documents match your search or filter.' }}
              </td>
            </tr>

            <tr v-for="doc in filteredDocuments" :key="doc.id">
              <td class="doc-cell">
                <div class="doc-title" :title="doc.title || ''">{{ doc.title || '-' }}</div>
                <div class="doc-meta">
                  <span v-if="isViewOnly(doc)" class="badge">View only</span>
                  <span
                    v-if="doc.recipient_type === 'bor'"
                    class="pill"
                    :class="statusPillClass(doc.review_status)"
                  >
                    {{ normalizeReviewStatus(doc.review_status) }}
                  </span>
                </div>
              </td>
              <td class="mono">{{ doc.document_number }}</td>
              <td class="mono">{{ formatDeadline(doc.reply_deadline_at) }}</td>
              <td class="actions-cell">
                <div class="actions">
                  <button
                    v-if="doc.recipient_type === 'bor'"
                    type="button"
                    class="btn btn-secondary btn-small"
                    :disabled="actionLoadingId === doc.id"
                    @click="viewLetter(doc)"
                  >
                    Letter
                  </button>
                  <button
                    type="button"
                    class="btn btn-secondary btn-small"
                    :disabled="!doc.file_path || actionLoadingId === doc.id"
                    @click="viewDocument(doc)"
                  >
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    View
                  </button>
                  <button
                    type="button"
                    class="btn btn-secondary btn-small"
                    :disabled="!doc.file_path || actionLoadingId === doc.id"
                    @click="downloadDocument(doc)"
                  >
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M12 3v12"></path>
                      <path d="m7 10 5 5 5-5"></path>
                      <path d="M4 21h16"></path>
                    </svg>
                    Download
                  </button>
                  <button
                    type="button"
                    class="btn btn-danger btn-small"
                    :disabled="actionLoadingId === doc.id"
                    @click="deleteDocument(doc)"
                  >
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M3 6h18"></path>
                      <path d="M8 6V4h8v2"></path>
                      <path d="M8 10v8"></path>
                      <path d="M12 10v8"></path>
                      <path d="M16 10v8"></path>
                    </svg>
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </article>
  </section>

  <div
    v-if="viewer.open"
    class="app-modal-overlay app-modal-overlay-full"
  >
    <div class="app-modal-dialog app-modal-fullscreen flex flex-col">
      <div class="app-modal-header">
        <div class="text-sm font-semibold text-gray-800 truncate">
          {{ viewer.title || 'Document Viewer' }}
        </div>
        <button
          class="app-btn-secondary app-btn-solid-red h-9 min-w-20 rounded-lg"
          @click="closeViewer"
        >
          Close
        </button>
      </div>
      <div class="flex-1 bg-gray-100">
        <iframe
          v-if="viewer.url && viewer.isPreviewable"
          :src="viewer.url"
          class="w-full h-full"
          title="Document Viewer"
        ></iframe>
        <div v-else-if="viewer.url && !viewer.isPreviewable" class="h-full flex flex-col items-center justify-center text-gray-600 gap-3 px-6 text-center">
          <div class="text-sm">
            Preview is available for PDF files only.
          </div>
        </div>
        <div v-else class="h-full flex items-center justify-center text-gray-500">
          Loading document...
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue';
import { documentReviewService, documentService } from '@/services/api';
import { useAppModal } from '@/composables/useAppModal';

const tabs = [
  { label: 'BOARD OF REGENTS', value: 'bor' },
  { label: 'ACADEMIC COUNCIL', value: 'uac' },
  { label: 'ADMINISTRATIVE COUNCIL', value: 'uadmin' },
];

const documents = ref([]);
const activeTab = ref('bor');
const isLoading = ref(false);
const errorMessage = ref('');
const actionLoadingId = ref(null);
const searchTerm = ref('');
const selectedMonth = ref('');
const { showError, showSuccess, showConfirm } = useAppModal();
const viewer = reactive({
  open: false,
  url: '',
  title: '',
  isPreviewable: false,
  currentDoc: null,
});

const buildPdfViewerUrl = (blobUrl) => `${blobUrl}#zoom=100`;

const filteredDocuments = computed(() => {
  return documents.value.filter(doc => {
    // Search filter
    const search = searchTerm.value.toLowerCase();
    if (search) {
      const title = (doc.title || '').toLowerCase();
      const docNumber = (doc.document_number || '').toLowerCase();
      if (!title.includes(search) && !docNumber.includes(search)) {
        return false;
      }
    }

    // Month filter
    if (selectedMonth.value) {
      const docDate = doc.created_at || doc.updated_at;
      if (docDate) {
        const docMonth = docDate.substring(0, 7); // YYYY-MM format
        if (docMonth !== selectedMonth.value) {
          return false;
        }
      }
    }

    return true;
  });
});

const normalizeReviewStatus = (status) => {
  const normalized = `${status || 'pending'}`.toLowerCase();
  if (normalized === 'not_allowed') return 'not allowed';
  return normalized;
};

const statusPillClass = (status) => {
  const normalized = `${status || 'pending'}`.toLowerCase();
  if (normalized === 'allowed') return 'pill-allowed';
  if (normalized === 'not_allowed') return 'pill-not-allowed';
  return 'pill-pending';
};

const isViewOnly = (doc) => Number(doc?.allow_replies ?? 1) === 0 || doc?.is_view_only === true;
const formatDeadline = (deadline) => {
  if (!deadline) return '-';
  const normalized = String(deadline).replace(' ', 'T');
  const date = new Date(`${normalized}Z`);
  if (Number.isNaN(date.getTime())) return String(deadline);
  return date.toLocaleDateString();
};

const fetchDocuments = async () => {
  errorMessage.value = '';
  isLoading.value = true;
  try {
    const response = await documentService.byRecipientType(activeTab.value);
    documents.value = response?.data || [];
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to load documents.';
    await showError(errorMessage.value, 'Load Failed');
  } finally {
    isLoading.value = false;
  }
};

const changeTab = async (type) => {
  if (activeTab.value === type) return;
  activeTab.value = type;
  await fetchDocuments();
};

const viewLetter = async (doc) => {
  if (!doc?.id) return;
  actionLoadingId.value = doc.id;
  try {
    const blob = await documentReviewService.letter(doc.id);
    const typedBlob = new Blob([blob], { type: 'application/pdf' });
    const url = URL.createObjectURL(typedBlob);
    viewer.open = true;
    viewer.url = buildPdfViewerUrl(url);
    viewer.title = `Letter - ${doc.document_number || doc.title || doc.id}`;
    viewer.isPreviewable = true;
    viewer.currentDoc = doc;
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to open letter.';
    await showError(errorMessage.value, 'Open Failed');
  } finally {
    actionLoadingId.value = null;
  }
};

const refreshActiveTab = async () => {
  await fetchDocuments();
};

const viewDocument = async (doc) => {
  if (!doc?.id) return;
  actionLoadingId.value = doc.id;
  try {
    const blob = await documentService.download(doc.id, { view: 1 });
    const typedBlob = doc.file_type === 'application/pdf'
      ? new Blob([blob], { type: 'application/pdf' })
      : blob;
    const url = URL.createObjectURL(typedBlob);
    viewer.open = true;
    viewer.url = doc.file_type === 'application/pdf' ? buildPdfViewerUrl(url) : url;
    viewer.title = doc.file_name || doc.title || 'Document Viewer';
    viewer.isPreviewable = doc.file_type === 'application/pdf';
    viewer.currentDoc = doc;
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to open document.';
    await showError(errorMessage.value, 'Preview Failed');
  } finally {
    actionLoadingId.value = null;
  }
};

const closeViewer = () => {
  if (viewer.url) {
    URL.revokeObjectURL(viewer.url.split('#')[0]);
  }
  viewer.open = false;
  viewer.url = '';
  viewer.title = '';
  viewer.isPreviewable = false;
  viewer.currentDoc = null;
};

const downloadDocument = async (doc) => {
  if (!doc?.id) return;
  actionLoadingId.value = doc.id;
  try {
    const blob = await documentService.download(doc.id);
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = doc.file_name || `document-${doc.id}`;
    link.click();
    URL.revokeObjectURL(url);
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to download document.';
    await showError(errorMessage.value, 'Download Failed');
  } finally {
    actionLoadingId.value = null;
  }
};

const deleteDocument = async (doc) => {
  if (!doc?.id) return;
  const confirmed = await showConfirm({
    title: 'Delete Document',
    message: `Delete "${doc.title}"? This action cannot be undone.`,
    confirmText: 'Delete',
    cancelText: 'Keep',
  });
  if (!confirmed) return;

  actionLoadingId.value = doc.id;
  try {
    await documentService.delete(doc.id);
    documents.value = documents.value.filter((item) => item.id !== doc.id);
    await showSuccess('Document deleted successfully.', 'Deleted');
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to delete document.';
    await showError(errorMessage.value, 'Delete Failed');
  } finally {
    actionLoadingId.value = null;
  }
};

onMounted(fetchDocuments);
</script>

<style scoped>
.manager-screen {
  width: 100%;
  min-height: 100%;
  padding: clamp(0.8rem, 1.8vw, 1.3rem);
  background:
    radial-gradient(circle at 20% 0%, rgba(212, 160, 23, 0.2) 0%, transparent 40%),
    linear-gradient(140deg, #1b5e20 0%, #15471a 42%, #0d3715 100%);
  color: #fff8e7;
}

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

.tabs {
  display: flex;
  gap: 0.55rem;
  flex-wrap: wrap;
}

.tab-btn {
  border: 1px solid rgba(240, 192, 79, 0.55);
  border-radius: 0.6rem;
  padding: 0.55rem 0.9rem;
  background: rgba(0, 0, 0, 0.18);
  color: #fff8e7;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.72rem;
  transition: filter 120ms ease, transform 120ms ease, background 120ms ease;
}

.tab-btn:hover {
  filter: brightness(1.06);
}

.tab-btn-active {
  background: rgba(240, 192, 79, 0.2);
  border-color: rgba(240, 192, 79, 0.9);
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

.loading-text {
  margin-top: 0.65rem;
  color: #f6ead0;
}

.table-wrap {
  margin-top: 0.75rem;
  overflow-x: auto;
  border: 1px solid rgba(240, 192, 79, 0.3);
  border-radius: 0.6rem;
  background: linear-gradient(180deg, rgba(27, 94, 32, 0.4) 0%, rgba(13, 55, 21, 0.3) 100%);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  border-bottom: 1px solid rgba(240, 192, 79, 0.2);
  padding: 0.75rem 0.65rem;
  text-align: center;
  font-size: 0.95rem;
  vertical-align: top;
}

th {
  color: #f3ce74;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 800;
  white-space: nowrap;
  font-size: 0.82rem;
}

tbody tr:last-child td {
  border-bottom: 0;
}

.empty-cell {
  padding: 1.1rem 0.85rem;
  color: #f6ead0;
  text-align: center;
}

.doc-cell {
  text-align: left;
  min-width: 260px;
}

.doc-title {
  font-weight: 800;
  color: #fff8e7;
}

.doc-meta {
  margin-top: 0.45rem;
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.badge {
  display: inline-flex;
  align-items: center;
  border-radius: 9999px;
  padding: 0.2rem 0.55rem;
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  border: 1px solid rgba(255, 255, 255, 0.18);
  background: rgba(0, 0, 0, 0.18);
  color: #f6ead0;
}

.pill {
  display: inline-flex;
  align-items: center;
  border-radius: 9999px;
  padding: 0.2rem 0.55rem;
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  border: 1px solid rgba(255, 255, 255, 0.18);
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

.pill-pending {
  border-color: rgba(240, 192, 79, 0.55);
  background: rgba(240, 192, 79, 0.12);
  color: #f6ead0;
}

.mono {
  white-space: nowrap;
  color: #fff7e2;
}

.actions-cell {
  white-space: nowrap;
}

.actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  flex-wrap: nowrap;
}

.btn {
  border: 1px solid #f0c04f;
  border-radius: 0.6rem;
  padding: 0.55rem 0.85rem;
  font-size: 0.9rem;
  font-weight: 800;
  color: #fff8e5;
  background: rgba(0, 0, 0, 0.22);
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  transition: filter 120ms ease, opacity 120ms ease;
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn-secondary:hover:not(:disabled) {
  filter: brightness(1.06);
}

.btn-danger {
  border-color: rgba(255, 156, 156, 0.7);
  background: linear-gradient(to bottom right, #6f1717, #4a0f0f);
}

.btn-danger:hover:not(:disabled) {
  filter: brightness(1.08);
}

.btn-small {
  font-size: 0.78rem;
  padding: 0.4rem 0.6rem;
}

.icon {
  width: 12px;
  height: 12px;
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

