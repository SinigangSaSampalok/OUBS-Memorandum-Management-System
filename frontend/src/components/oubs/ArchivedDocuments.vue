<template>
  <section class="manager-screen">
    <header class="manager-hero">
      <p class="hero-kicker">OUBS PORTAL</p>
      <h1>Archived Documents</h1>
      <p class="hero-subtitle">View and restore archived memorandums.</p>
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
      <div v-if="isLoading" class="loading-text">Loading archived documents...</div>

      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Document Name</th>
              <th>Document No.</th>
              <th>Archived Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredDocuments.length === 0">
              <td colspan="4" class="empty-cell">
                {{ documents.length === 0 ? 'No archived documents found.' : 'No documents match your search or filter.' }}
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
              <td class="mono">{{ formatArchivedDate(doc.archived_at) }}</td>
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
                    class="btn btn-primary btn-small"
                    :disabled="actionLoadingId === doc.id"
                    @click="restoreDocument(doc)"
                  >
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path>
                      <path d="M21 3v5h-5"></path>
                      <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"></path>
                      <path d="M8 16H3v5"></path>
                    </svg>
                    Restore
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
      const docDate = doc.archived_at || doc.created_at;
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

const formatArchivedDate = (date) => {
  if (!date) return '-';
  const normalized = String(date).replace(' ', 'T');
  const d = new Date(`${normalized}Z`);
  if (Number.isNaN(d.getTime())) return String(date);
  return d.toLocaleDateString();
};

const fetchDocuments = async () => {
  errorMessage.value = '';
  isLoading.value = true;
  try {
    const response = await documentService.archivedByRecipientType(activeTab.value);
    documents.value = response?.data || [];
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to load archived documents.';
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

const restoreDocument = async (doc) => {
  if (!doc?.id) return;
  const confirmed = await showConfirm({
    title: 'Restore Document',
    message: `Restore "${doc.title}" from archive? The document will be moved back to the active documents.`,
    confirmText: 'Restore',
    cancelText: 'Cancel',
  });
  if (!confirmed) return;

  actionLoadingId.value = doc.id;
  try {
    await documentService.restore(doc.id);
    documents.value = documents.value.filter((item) => item.id !== doc.id);
    await showSuccess('Document restored successfully.', 'Restored');
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to restore document.';
    await showError(errorMessage.value, 'Restore Failed');
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
  padding: 0.55rem 1.1rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #f6ead0;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(212, 160, 23, 0.4);
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.tab-btn:hover {
  background: rgba(212, 160, 23, 0.2);
}

.tab-btn-active {
  background: rgba(212, 160, 23, 0.35);
  border-color: #d4a017;
  color: #fff;
}

.refresh-btn {
  padding: 0.55rem 1.1rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #f6ead0;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(212, 160, 23, 0.4);
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.refresh-btn:hover:not(:disabled) {
  background: rgba(212, 160, 23, 0.2);
}

.refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.filter-section {
  display: flex;
  gap: 1rem;
  margin-top: 0.9rem;
  flex-wrap: wrap;
}

.filter-group {
  flex: 1;
  min-width: 200px;
}

.filter-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: #f1d488;
  margin-bottom: 0.35rem;
}

.search-input-wrapper {
  position: relative;
}

.search-icon {
  position: absolute;
  left: 0.7rem;
  top: 50%;
  transform: translateY(-50%);
  width: 1rem;
  height: 1rem;
  color: #a68a5b;
  pointer-events: none;
}

.search-input,
.month-input {
  width: 100%;
  padding: 0.6rem 0.7rem 0.6rem 2.3rem;
  font-size: 0.9rem;
  color: #fff8e7;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(212, 160, 23, 0.4);
  border-radius: 0.5rem;
  outline: none;
  transition: border-color 0.2s ease;
}

.search-input::placeholder {
  color: #b8a88a;
}

.search-input:focus,
.month-input:focus {
  border-color: #d4a017;
}

.month-input {
  padding-left: 0.7rem;
}

.error-text {
  margin-top: 0.8rem;
  padding: 0.7rem;
  font-size: 0.9rem;
  color: #ffcccc;
  background: rgba(255, 0, 0, 0.15);
  border: 1px solid rgba(255, 0, 0, 0.3);
  border-radius: 0.5rem;
}

.loading-text {
  margin-top: 1.5rem;
  text-align: center;
  color: #f1d488;
  font-size: 1rem;
}

.table-wrap {
  margin-top: 1rem;
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

thead tr {
  background: rgba(212, 160, 23, 0.15);
}

th {
  padding: 0.75rem 0.6rem;
  text-align: left;
  font-weight: 700;
  color: #f1d488;
  border-bottom: 2px solid rgba(212, 160, 23, 0.4);
}

td {
  padding: 0.75rem 0.6rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

tbody tr:hover {
  background: rgba(255, 255, 255, 0.05);
}

.empty-cell {
  text-align: center;
  color: #b8a88a;
  padding: 2rem 0.6rem;
}

.doc-cell {
  max-width: 280px;
}

.doc-title {
  font-weight: 600;
  color: #fff8e7;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.doc-meta {
  display: flex;
  gap: 0.4rem;
  margin-top: 0.25rem;
  flex-wrap: wrap;
}

.badge {
  display: inline-block;
  padding: 0.15rem 0.4rem;
  font-size: 0.7rem;
  font-weight: 600;
  color: #1b5e20;
  background: rgba(212, 160, 23, 0.4);
  border-radius: 0.25rem;
}

.pill {
  display: inline-block;
  padding: 0.15rem 0.5rem;
  font-size: 0.7rem;
  font-weight: 600;
  border-radius: 1rem;
}

.pill-allowed {
  color: #1b5e20;
  background: rgba(76, 175, 80, 0.3);
}

.pill-not-allowed {
  color: #b71c1c;
  background: rgba(244, 67, 54, 0.3);
}

.pill-pending {
  color: #f1d488;
  background: rgba(255, 193, 7, 0.25);
}

.mono {
  font-family: 'Courier New', Courier, monospace;
  color: #e0d5c0;
}

.actions-cell {
  white-space: nowrap;
}

.actions {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  padding: 0.4rem 0.7rem;
  font-size: 0.8rem;
  font-weight: 600;
  border: none;
  border-radius: 0.4rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  color: #1b5e20;
  background: rgba(212, 160, 23, 0.35);
}

.btn-secondary:hover:not(:disabled) {
  background: rgba(212, 160, 23, 0.5);
}

.btn-primary {
  color: #fff;
  background: #1b5e20;
}

.btn-primary:hover:not(:disabled) {
  background: #2e7d32;
}

.btn-danger {
  color: #fff;
  background: #c62828;
}

.btn-danger:hover:not(:disabled) {
  background: #d32f2f;
}

.btn-small {
  padding: 0.3rem 0.5rem;
  font-size: 0.75rem;
}

.icon {
  width: 0.9rem;
  height: 0.9rem;
}

/* Modal styles */
.app-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.app-modal-overlay-full {
  padding: 0;
}

.app-modal-dialog {
  background: #fff;
  border-radius: 0.75rem;
  max-width: 90vw;
  max-height: 90vh;
  overflow: hidden;
}

.app-modal-fullscreen {
  width: 100%;
  height: 100%;
  max-width: 100vw;
  max-height: 100vh;
  border-radius: 0;
}

.app-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  background: #f5f5f5;
  border-bottom: 1px solid #e0e0e0;
}

.app-btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.app-btn-solid-red {
  color: #fff;
  background: #c62828;
}

.app-btn-solid-red:hover {
  background: #d32f2f;
}
</style>