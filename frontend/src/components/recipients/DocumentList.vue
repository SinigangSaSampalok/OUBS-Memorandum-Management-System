<template>
  <section class="manager-screen">
    <header class="manager-hero">
      <p class="hero-kicker">OUBS PORTAL</p>
      <h1>Document List</h1>
      <p class="hero-subtitle">Review memorandums assigned to your council.</p>
    </header>

    <article class="manager-card">
      <div class="manager-toolbar">
        <div></div>
        <button
          type="button"
          class="refresh-btn"
          :disabled="isLoading"
          @click="refreshData"
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

        <div v-if="isLoading" class="py-6" style="color: #f6ead0;">Loading documents...</div>

        <div v-if="!isLoading" class="pb-6 mt-2">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Document Name</th>
                  <th>Document No.</th>
                  <th>Deadline</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredDocuments.length === 0">
                  <td colspan="5" class="empty-cell" style="text-align: center;">{{ documents.length === 0 ? 'No documents found.' : 'No documents match your search or filter.' }}</td>
                </tr>
                <tr v-for="doc in filteredDocuments" :key="doc.id">
                  <td class="doc-cell">
                    <span class="doc-title" :title="doc.title || ''">{{ doc.title || '-' }}</span>
                  </td>
                  <td style="white-space: nowrap; text-align: center;">{{ doc.document_number }}</td>
                  <td style="white-space: nowrap; text-align: center;">{{ formatDeadlineDate(doc.reply_deadline_at) }}</td>
                  <td style="text-align: center;">
                    <span
                      class="status-pill"
                      :class="hasRepliedToDocument(doc.id)
                        ? 'pill-replied'
                        : (isViewOnly(doc) ? 'pill-view-only' : getStatusClass(doc.status))"
                    >
                      {{ hasRepliedToDocument(doc.id)
                        ? submittedStatusLabel()
                        : (isViewOnly(doc) ? 'view only' : (doc.status || 'pending')) }}
                    </span>
                  </td>
                  <td class="actions-cell" style="text-align: center;">
                    <div class="flex items-center gap-2">
                      <button
                        class="action-btn"
                        :disabled="!doc.file_path || actionLoadingId === doc.id || (!isViewOnly(doc) && (doc.is_reply_open === false || `${doc.status || ''}`.toLowerCase() === 'closed'))"
                        @click="viewDocument(doc)"
                      >
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"></path>
                          <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        View
                      </button>
                      <button
                        class="action-btn"
                        :disabled="!doc.file_path || actionLoadingId === doc.id || doc.is_downloadable === false"
                        @click="downloadDocument(doc)"
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

  <!-- Document Viewer Modal -->
  <div v-if="viewer.open" class="modal-overlay">
    <div class="modal-dialog">
      <div class="modal-header">
        <div class="modal-title">{{ viewer.title || 'Document Viewer' }}</div>
        <div class="flex items-center gap-2">
          <button
            v-if="viewer.currentDoc"
            class="modal-btn-primary"
            :disabled="isViewOnly(viewer.currentDoc) || hasRepliedToDocument(viewer.currentDoc.id) || viewer.currentDoc.is_reply_open === false || `${viewer.currentDoc.status || ''}`.toLowerCase() === 'closed'"
            @click="openReplySlipFromViewer"
          >
            {{ viewerActionLabel(viewer.currentDoc) }}
          </button>
          <button class="modal-btn-close" @click="closeViewer">Close</button>
        </div>
      </div>
      <div class="modal-body">
        <iframe
          v-if="viewer.url && viewer.isPreviewable"
          :src="viewer.url"
          class="w-full h-full"
          title="Document Viewer"
        ></iframe>
        <div v-else-if="viewer.url && !viewer.isPreviewable" class="modal-no-preview">
          <p class="text-sm">Preview is available for PDF files only.</p>
        </div>
        <div v-else class="modal-loading">Loading document...</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { documentService, replySlipService } from '@/services/api';
import { useAppModal } from '@/composables/useAppModal';

const documents = ref([]);
const isLoading = ref(false);
const errorMessage = ref('');
const actionLoadingId = ref(null);
const searchTerm = ref('');
const selectedMonth = ref('');
const { showError } = useAppModal();
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const repliedDocumentIds = ref(new Set());

const viewer = reactive({
  open: false,
  url: '',
  title: '',
  isPreviewable: false,
  currentDoc: null,
});

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
      const docDate = doc.reply_deadline_at || doc.created_at || doc.updated_at;
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

const fetchDocuments = async () => {
  errorMessage.value = '';
  isLoading.value = true;
  try {
    const response = await documentService.getAll();
    documents.value = response?.data || [];
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to load documents.';
    await showError(errorMessage.value, 'Load Failed');
  } finally {
    isLoading.value = false;
  }
};

const fetchMyReplies = async () => {
  try {
    const response = await replySlipService.myReplies();
    const replies = response?.data || [];
    repliedDocumentIds.value = new Set(
      replies
        .map((reply) => Number(reply.document_id))
        .filter((id) => Number.isFinite(id))
    );
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to load reply status.';
    await showError(errorMessage.value, 'Load Failed');
  }
};

const refreshData = async () => {
  await Promise.all([fetchDocuments(), fetchMyReplies()]);
};

const hasRepliedToDocument = (documentId) => repliedDocumentIds.value.has(Number(documentId));
const isBorUser = () => authStore.user?.user_type === 'bor';
const submissionLabel = () => (isBorUser() ? 'Reply Slip' : 'Response');
const submittedStatusLabel = () => (isBorUser() ? 'replied' : 'responded');
const isViewOnly = (doc) => Number(doc?.allow_replies ?? 1) === 0 || doc?.is_view_only === true;
const viewerActionLabel = (doc) => {
  if (!doc) return submissionLabel();
  if (isViewOnly(doc)) return 'View only';
  return hasRepliedToDocument(doc.id) ? `Already ${submittedStatusLabel()}` : submissionLabel();
};
const buildPdfViewerUrl = (blobUrl) => `${blobUrl}#zoom=100`;

const formatDeadlineDate = (deadline) => {
  if (!deadline) return '-';
  const normalized = String(deadline).replace(' ', 'T');
  const date = new Date(`${normalized}Z`);
  if (Number.isNaN(date.getTime())) return String(deadline);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const getStatusClass = (status) => {
  const normalized = `${status || 'pending'}`.toLowerCase();
  if (normalized === 'pending') return 'pill-pending';
  if (normalized === 'approved' || normalized === 'completed') return 'pill-approved';
  if (normalized === 'disapproved' || normalized === 'rejected') return 'pill-rejected';
  return 'pill-default';
};

const viewDocument = async (doc) => {
  if (!doc?.id) return;
  if (!isViewOnly(doc) && (doc.is_reply_open === false || `${doc.status || ''}`.toLowerCase() === 'closed')) {
    await showError('This document is already closed and no longer viewable.', 'Document Closed');
    return;
  }
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
  if (viewer.url) URL.revokeObjectURL(viewer.url.split('#')[0]);
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

const openReplySlipFromViewer = () => {
  if (!viewer.currentDoc?.id) return;
  if (isViewOnly(viewer.currentDoc)) {
    showError('This document is view-only and does not accept replies.', 'View Only');
    return;
  }
  if (hasRepliedToDocument(viewer.currentDoc.id)) return;
  if (viewer.currentDoc.is_reply_open === false || `${viewer.currentDoc.status || ''}`.toLowerCase() === 'closed') {
    showError('This document is already closed and no longer accepts replies.', 'Document Closed');
    return;
  }
  const documentId = String(viewer.currentDoc.id);
  closeViewer();
  router.push({ name: 'ReplySlip', params: { id: documentId } });
};

onMounted(async () => {
  await refreshData();
  
  // If a document ID is provided in the route, auto-open it
  if (route.params.id) {
    const docId = Number(route.params.id);
    const doc = documents.value.find(d => d.id === docId);
    if (doc) {
      // Small delay to ensure UI is ready
      setTimeout(() => {
        viewDocument(doc);
      }, 100);
    }
  }
});
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
  padding: 0.6rem 0.75rem;
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

/* Document Name — allow growth, truncate at max */
th:nth-child(1), td:nth-child(1) {
  text-align: left;
  min-width: 12rem;
  max-width: 22rem;
}

/* Document No. — compact, no wrap */
th:nth-child(2), td:nth-child(2) {
  text-align: center;
  white-space: nowrap;
  min-width: 8rem;
}

/* Deadline — no wrap */
th:nth-child(3), td:nth-child(3) {
  text-align: center;
  white-space: nowrap;
  min-width: 8rem;
}

/* Status — pill fits on one line */
th:nth-child(4), td:nth-child(4) {
  text-align: center;
  white-space: nowrap;
  min-width: 7rem;
}

/* Actions — buttons, no wrap */
th:nth-child(5), td:nth-child(5) {
  text-align: center;
  white-space: nowrap;
  min-width: 10rem;
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
  /* max-width inherited from nth-child above */
}

.doc-title {
  font-weight: 800;
  color: #fff8e7;
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.doc-filename {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #f6ead0;
  font-size: 0.88rem;
}

.no-file {
  color: rgba(246, 234, 208, 0.5);
  font-size: 0.88rem;
}

.actions-cell {
  white-space: nowrap;
}

/* Action buttons */
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

/* Status pills */
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

.pill-pending {
  border-color: rgba(240, 192, 79, 0.55);
  background: rgba(240, 192, 79, 0.12);
  color: #f6ead0;
}

.pill-approved {
  border-color: rgba(140, 255, 196, 0.45);
  background: rgba(0, 90, 36, 0.22);
  color: #d7ffe7;
}

.pill-rejected {
  border-color: rgba(255, 156, 156, 0.5);
  background: rgba(120, 0, 0, 0.24);
  color: #ffd2d2;
}

.pill-replied {
  border-color: rgba(140, 255, 196, 0.45);
  background: rgba(0, 90, 36, 0.22);
  color: #d7ffe7;
}

.pill-view-only {
  border-color: rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.07);
  color: #f6ead0;
}

.pill-default {
  border-color: rgba(255, 255, 255, 0.18);
  background: rgba(255, 255, 255, 0.06);
  color: #f6ead0;
}

/* Modal */
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
  font-weight: 700;
  color: #fff8e7;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.modal-btn-primary {
  height: 2.25rem;
  min-width: 8rem;
  padding: 0 0.9rem;
  border-radius: 0.5rem;
  border: 1px solid rgba(212, 160, 23, 0.5);
  background: linear-gradient(to bottom right, #156a3b, #0d3d20);
  color: #fff8e7;
  font-size: 0.875rem;
  font-weight: 700;
  transition: filter 120ms ease;
}

.modal-btn-primary:disabled {
  opacity: 0.55;
  cursor: not-allowed;
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
}

.modal-btn-close:hover {
  filter: brightness(1.1);
}

.modal-body {
  flex: 1;
  overflow: auto;
  background: rgba(0, 0, 0, 0.2);
}

.modal-no-preview,
.modal-loading {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #f6ead0;
  font-size: 0.95rem;
  text-align: center;
  padding: 1.5rem;
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