<template>
  <section class="manager-screen">
    <header class="manager-hero">
      <p class="hero-kicker">OUBS PORTAL</p>
      <h1>Document Review</h1>
      <p class="hero-subtitle">Allow or block document access for recipients.</p>
    </header>

    <article class="manager-card">
      <div class="manager-toolbar">
        <div class="tabs" role="tablist">
          <select
            v-model="statusFilter"
            class="tab-select"
            :disabled="isLoading"
            @change="fetchDocuments"
          >
            <option value="pending">Pending</option>
            <option value="allowed">Allowed</option>
            <option value="not_allowed">Not Allowed</option>
            <option value="all">All</option>
          </select>
        </div>

        <button
          type="button"
          class="refresh-btn"
          :disabled="isLoading"
          @click="fetchDocuments"
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
                  <th>Title</th>
                  <th>Doc No.</th>
                  <th>Recipient</th>
                  <th>Review</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredDocuments.length === 0">
                  <td colspan="5" class="empty-cell">{{ documents.length === 0 ? 'No documents found.' : 'No documents match your search or filter.' }}</td>
                </tr>
                <tr v-for="doc in filteredDocuments" :key="doc.id">
                  <td class="doc-cell">
                    <span class="doc-title" :title="doc.title || ''">{{ doc.title || '-' }}</span>
                    <div v-if="doc.uploaded_by_name" class="doc-meta">
                      Uploaded by: {{ doc.uploaded_by_name }}
                    </div>
                  </td>
                  <td style="white-space: nowrap; text-align: center;">{{ doc.document_number }}</td>
                  <td style="white-space: nowrap; text-align: center;">{{ recipientLabel(doc.recipient_type) }}</td>
                  <td style="text-align: center;">
                    <span class="status-pill" :class="statusPillClass(doc.review_status)">
                      {{ normalizeReviewStatus(doc.review_status) }}
                    </span>
                  </td>
                  <td class="actions-cell" style="text-align: center;">
                    <div class="flex items-center gap-2">
                      <button
                        class="action-btn"
                        :disabled="actionLoadingId === doc.id"
                        @click="openLetter(doc)"
                      >
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path d="M4 4h16v16H4z"></path>
                          <path d="M4 9l8 5 8-5"></path>
                        </svg>
                        Letter
                      </button>
                      <button
                        class="action-btn"
                        :disabled="!doc.file_path || actionLoadingId === doc.id"
                        @click="viewDocument(doc)"
                      >
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"></path>
                          <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        Document
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

  <!-- Letter Modal -->
  <div v-if="letter.open" class="modal-overlay">
    <div class="modal-dialog modal-dialog-lg">
      <div class="modal-header">
        <div class="min-w-0">
          <div class="modal-title">{{ letter.title }}</div>
          <div v-if="letter.doc?.document_number" class="modal-subtitle">
            Doc No.: {{ letter.doc.document_number }}
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button
            class="modal-btn-close"
            :disabled="letter.isLoading || letter.isSubmitting"
            @click="closeLetter"
          >
            Close
          </button>
        </div>
      </div>

      <div class="modal-body modal-body-split">
        <!-- PDF Viewer -->
        <div class="modal-preview">
          <iframe
            v-if="letter.url"
            :src="letter.url"
            class="w-full h-full"
            title="Letter Viewer"
          ></iframe>
          <div v-else class="modal-loading">Loading letter...</div>
        </div>

        <!-- Decision Panel -->
        <div class="modal-sidebar">
          <div class="flex items-center justify-between gap-2 mb-4">
            <div class="modal-section-label">Decision</div>
            <span class="status-pill" :class="statusPillClass(letter.doc?.review_status)">
              {{ normalizeReviewStatus(letter.doc?.review_status) }}
            </span>
          </div>

          <div v-if="letter.isLocked" class="modal-locked-note">
            This review is finalized and cannot be changed.
          </div>

          <div class="space-y-3 mt-3">
            <label class="block text-xs font-semibold" style="color: #f3ce74; text-transform: uppercase; letter-spacing: 0.07em;">Allow access?</label>
            <div class="space-y-2">
              <label class="flex items-center gap-2 text-sm" style="color: #fff8e7; cursor: pointer;">
                <input type="radio" value="allowed" v-model="letter.decision" :disabled="letter.isLocked || letter.isSubmitting" />
                Allowed
              </label>
              <label class="flex items-center gap-2 text-sm" style="color: #fff8e7; cursor: pointer;">
                <input type="radio" value="not_allowed" v-model="letter.decision" :disabled="letter.isLocked || letter.isSubmitting" />
                Not Allowed
              </label>
            </div>

            <div>
              <label class="block text-xs font-semibold mb-1" style="color: #f3ce74; text-transform: uppercase; letter-spacing: 0.07em;">Remarks (optional)</label>
              <textarea
                v-model="letter.remarks"
                class="modal-textarea"
                :disabled="letter.isLocked || letter.isSubmitting"
                placeholder="Add remarks here..."
              ></textarea>
            </div>

            <button
              class="modal-btn-submit"
              :disabled="letter.isLocked || letter.isSubmitting || !letter.decision"
              @click="submitLetterDecision"
            >
              {{ letter.isSubmitting ? 'Submitting...' : 'Submit Decision' }}
            </button>

            <div class="text-xs" style="color: #f6ead0; opacity: 0.8;">
              Once submitted, the decision will be final.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Document Viewer Modal -->
  <div v-if="viewer.open" class="modal-overlay">
    <div class="modal-dialog">
      <div class="modal-header">
        <div class="modal-title">{{ viewer.title }}</div>
        <button class="modal-btn-close" @click="closeViewer">Close</button>
      </div>
      <div class="modal-body">
        <iframe
          v-if="viewer.url && viewer.isPreviewable"
          :src="viewer.url"
          class="w-full h-full"
          title="Document Viewer"
        ></iframe>
        <div v-else-if="viewer.url && !viewer.isPreviewable" class="modal-loading">
          <p class="text-sm">Preview is available for PDF files only.</p>
        </div>
        <div v-else class="modal-loading">Loading document...</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { documentReviewService, documentService } from '@/services/api';
import { useAppModal } from '@/composables/useAppModal';

const router = useRouter();
const authStore = useAuthStore();
const { showError, showSuccess } = useAppModal();

const documents = ref([]);
const isLoading = ref(false);
const errorMessage = ref('');
const actionLoadingId = ref(null);
const statusFilter = ref('pending');
const searchTerm = ref('');
const selectedMonth = ref('');

const viewer = reactive({
  open: false,
  url: '',
  title: '',
  isPreviewable: false,
  currentDoc: null,
});

const letter = reactive({
  open: false,
  url: '',
  title: '',
  doc: null,
  decision: '',
  remarks: '',
  isLocked: false,
  isLoading: false,
  isSubmitting: false,
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

const ensureReviewerAccess = () => {
  const isReviewer = authStore.user?.user_type === 'bor' && Number(authStore.user?.is_document_reviewer ?? 0) === 1;
  if (!isReviewer) {
    router.replace('/recipient/home');
    return false;
  }
  return true;
};

const fetchDocuments = async () => {
  if (!ensureReviewerAccess()) return;
  errorMessage.value = '';
  isLoading.value = true;
  try {
    const response = await documentReviewService.list({ status: statusFilter.value });
    documents.value = response?.data || [];
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to load documents.';
    await showError(errorMessage.value, 'Load Failed');
  } finally {
    isLoading.value = false;
  }
};

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

const recipientLabel = (type) => {
  const map = { bor: 'BOR', uac: 'UAC', uadmin: 'UAdmin' };
  return map[type] || '-';
};

const buildPdfViewerUrl = (blobUrl, zoom = '100') => `${blobUrl}#zoom=${encodeURIComponent(zoom)}`;

const refreshLetterPdf = async (doc) => {
  const blob = await documentReviewService.letter(doc.id);
  const typedBlob = new Blob([blob], { type: 'application/pdf' });
  const url = URL.createObjectURL(typedBlob);
  if (letter.url) {
    const oldUrl = letter.url.split('#')[0];
    URL.revokeObjectURL(oldUrl);
  }
  letter.url = buildPdfViewerUrl(url, 'page-width');
};

const openLetter = async (doc) => {
  if (!ensureReviewerAccess()) return;
  if (!doc?.id) return;

  errorMessage.value = '';
  actionLoadingId.value = doc.id;
  letter.open = true;
  letter.isLoading = true;
  letter.doc = doc;
  letter.title = 'Letter to Commissioner';
  letter.decision = ['allowed', 'not_allowed'].includes(`${doc.review_status || ''}`.toLowerCase())
    ? `${doc.review_status}`.toLowerCase()
    : '';
  letter.remarks = doc.review_note || '';
  letter.isLocked = ['allowed', 'not_allowed'].includes(`${doc.review_status || ''}`.toLowerCase());

  try {
    await refreshLetterPdf(doc);
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to open letter.';
    await showError(errorMessage.value, 'Open Failed');
    closeLetter();
  } finally {
    letter.isLoading = false;
    actionLoadingId.value = null;
  }
};

const submitLetterDecision = async () => {
  const doc = letter.doc;
  if (!doc?.id) return;
  if (letter.isLocked) return;
  if (!letter.decision) {
    await showError('Please select Allowed or Not Allowed.', 'Missing Decision');
    return;
  }

  letter.isSubmitting = true;
  try {
    await documentReviewService.update(doc.id, {
      review_status: letter.decision,
      review_note: letter.remarks,
    });
    doc.review_status = letter.decision;
    doc.review_note = letter.remarks;
    letter.isLocked = true;
    await refreshLetterPdf(doc);
    await showSuccess('Decision submitted successfully. This cannot be changed.', 'Review Finalized');
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to submit decision.';
    await showError(errorMessage.value, 'Submit Failed');
  } finally {
    letter.isSubmitting = false;
  }
};

const closeLetter = () => {
  letter.open = false;
  if (letter.url) URL.revokeObjectURL(letter.url.split('#')[0]);
  letter.url = '';
  letter.title = '';
  letter.doc = null;
  letter.decision = '';
  letter.remarks = '';
  letter.isLocked = false;
  letter.isLoading = false;
  letter.isSubmitting = false;
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
    await showError(errorMessage.value, 'Open Failed');
  } finally {
    actionLoadingId.value = null;
  }
};

const closeViewer = () => {
  viewer.open = false;
  if (viewer.url) URL.revokeObjectURL(viewer.url.split('#')[0]);
  viewer.url = '';
  viewer.title = '';
  viewer.isPreviewable = false;
  viewer.currentDoc = null;
};

onMounted(fetchDocuments);
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

.tabs {
  display: flex;
  gap: 0.55rem;
  flex-wrap: wrap;
}

.tab-select {
  border: 1px solid rgba(240, 192, 79, 0.55);
  border-radius: 0.6rem;
  padding: 0.55rem 0.9rem;
  background: rgba(0, 0, 0, 0.18);
  color: #fff8e7;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.72rem;
  cursor: pointer;
}

.tab-select:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.tab-select option {
  background-color: #1b5e20;
  color: #fff8e7;
  text-transform: none;
  font-weight: 400;
  letter-spacing: 0;
  font-size: 0.875rem;
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

/* Title — allow growth, truncate at max */
th:nth-child(1), td:nth-child(1) {
  text-align: left;
  min-width: 12rem;
  max-width: 22rem;
}

/* Doc No. — compact, no wrap */
th:nth-child(2), td:nth-child(2) {
  text-align: center;
  white-space: nowrap;
  min-width: 8rem;
}

/* Recipient — short label, no wrap */
th:nth-child(3), td:nth-child(3) {
  text-align: center;
  white-space: nowrap;
  min-width: 7rem;
}

/* Review status — pill, no wrap */
th:nth-child(4), td:nth-child(4) {
  text-align: center;
  white-space: nowrap;
  min-width: 7rem;
}

/* Actions — buttons, no wrap */
th:nth-child(5), td:nth-child(5) {
  text-align: center;
  white-space: nowrap;
  min-width: 11rem;
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

.doc-meta {
  font-size: 0.78rem;
  margin-top: 0.2rem;
  color: #f6ead0;
  opacity: 0.8;
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

.pill-pending {
  border-color: rgba(240, 192, 79, 0.55);
  background: rgba(240, 192, 79, 0.12);
  color: #f6ead0;
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

/* ── Modals ───────────────────────────────────────────────── */
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

.modal-dialog-lg {
  align-self: center;
  justify-self: center;
  width: 100%;
  max-width: 72rem;
  height: 88vh;
  border-radius: 0.75rem;
  border: 1px solid rgba(212, 160, 23, 0.55);
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
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
}

.modal-title {
  font-size: 0.95rem;
  font-weight: 800;
  color: #fff8e7;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.modal-subtitle {
  font-size: 0.78rem;
  margin-top: 0.2rem;
  color: #f6ead0;
  opacity: 0.8;
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

.modal-btn-close:hover:not(:disabled) {
  filter: brightness(1.1);
}

.modal-btn-close:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.modal-body {
  flex: 1;
  overflow: auto;
  background: rgba(0, 0, 0, 0.2);
}

.modal-body-split {
  display: grid;
  grid-template-columns: 1fr;
  overflow: hidden;
}

@media (min-width: 1024px) {
  .modal-body-split {
    grid-template-columns: 2fr 1fr;
  }
}

.modal-preview {
  background: rgba(0, 0, 0, 0.35);
  overflow: hidden;
}

.modal-sidebar {
  border-top: 1px solid rgba(212, 160, 23, 0.3);
  padding: 1.2rem;
  overflow-y: auto;
  background: rgba(10, 10, 10, 0.32);
}

@media (min-width: 1024px) {
  .modal-sidebar {
    border-top: none;
    border-left: 1px solid rgba(212, 160, 23, 0.3);
  }
}

.modal-section-label {
  font-size: 0.85rem;
  font-weight: 800;
  color: #fff8e7;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.modal-locked-note {
  font-size: 0.85rem;
  color: #f6ead0;
  opacity: 0.8;
  padding: 0.6rem 0.8rem;
  border-radius: 0.5rem;
  border: 1px solid rgba(240, 192, 79, 0.3);
  background: rgba(240, 192, 79, 0.07);
  margin-top: 0.5rem;
}

.modal-textarea {
  width: 100%;
  min-height: 7rem;
  border-radius: 0.6rem;
  padding: 0.6rem 0.75rem;
  font-size: 0.875rem;
  border: 1px solid rgba(212, 160, 23, 0.4);
  background: rgba(0, 0, 0, 0.3);
  color: #fff8e7;
  resize: vertical;
}

.modal-textarea:focus {
  outline: none;
  border-color: rgba(240, 192, 79, 0.7);
  box-shadow: 0 0 0 2px rgba(240, 192, 79, 0.2);
}

.modal-textarea:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.modal-btn-submit {
  width: 100%;
  height: 2.5rem;
  border-radius: 0.6rem;
  font-size: 0.875rem;
  font-weight: 700;
  border: 1px solid rgba(212, 160, 23, 0.6);
  background: linear-gradient(to bottom right, #156a3b, #0d3d20);
  color: #f0c04f;
  transition: filter 120ms ease;
}

.modal-btn-submit:hover:not(:disabled) {
  filter: brightness(1.1);
}

.modal-btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

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