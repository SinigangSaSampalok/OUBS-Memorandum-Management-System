<template>
  <section class="manager-screen">
    <header class="manager-hero">
      <p class="hero-kicker">OUBS PORTAL</p>
      <h1>Summary</h1>
      <p class="hero-subtitle">Review summary documents by council.</p>
    </header>

    <article class="manager-card">
      <div class="manager-toolbar">
        <div class="tabs" role="tablist" aria-label="Council type">
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
          :disabled="isDocumentsLoading"
          @click="refreshActiveTab"
        >
          {{ isDocumentsLoading ? 'Refreshing...' : 'Refresh' }}
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
      <div v-if="isDocumentsLoading" class="loading-text">Loading documents...</div>

      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Document Name</th>
              <th>Document No.</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredDocuments.length === 0">
              <td colspan="3" class="empty-cell">
                {{ documents.length === 0 ? `No documents found for ${activeTabLabel}.` : 'No documents match your search or filter.' }}
              </td>
            </tr>

            <tr v-for="doc in filteredDocuments" :key="doc.id" @click="viewSummary(doc)" style="cursor: pointer;">
              <td class="doc-cell">
                <div class="doc-title" :title="doc.title || ''">{{ doc.title || '-' }}</div>
              </td>
              <td class="mono">{{ doc.document_number || '-' }}</td>
              <td>
                <span
                  class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border"
                  :class="getDocumentStatusClass(doc.status)"
                >
                  {{ doc.status || '-' }}
                </span>
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
    style="background: rgba(0, 0, 0, 0.6);"
  >
    <div class="app-modal-dialog app-modal-fullscreen flex flex-col" style="background: linear-gradient(140deg, #1b5e20 0%, #15471a 42%, #0d3715 100%);">
      <div class="app-modal-header" style="background: rgba(10, 10, 10, 0.3); border-bottom: 1px solid rgba(212, 160, 23, 0.4); padding: 1rem 1.2rem;">
        <div class="text-sm font-semibold truncate" style="color: #fff8e7;">
          {{ viewer.title || 'Summary of Actions' }}
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="h-9 min-w-20 rounded-lg text-sm font-semibold transition-all"
            :disabled="viewer.isLoading || viewer.actions.length === 0"
            @click="downloadSummaryPDF"
            style="background: linear-gradient(to bottom right, #156a3b, #0d3d20); color: #fff8e7; border: 1px solid rgba(212, 160, 23, 0.5);"
          >
            <svg v-if="!viewer.isDownloading" class="inline-block h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M12 3v12"></path>
              <path d="m7 10 5 5 5-5"></path>
              <path d="M4 21h16"></path>
            </svg>
            <span v-if="viewer.isDownloading">Downloading...</span>
            <span v-else>Download PDF</span>
          </button>
          <button
            class="h-9 min-w-20 rounded-lg text-sm font-semibold transition-all"
            @click="closeSummary"
            style="background: linear-gradient(to bottom right, #6f1717, #4a0f0f); color: #fff8e7; border: 1px solid rgba(212, 160, 23, 0.5);"
          >
            Close
          </button>
        </div>
      </div>
      <div class="flex-1 overflow-auto p-6" style="background: rgba(0, 0, 0, 0.2);">
        <div v-if="viewer.isLoading" class="flex items-center justify-center h-full" style="color: #f6ead0;">
          Loading summary...
        </div>
        <div v-else-if="viewer.actions && viewer.actions.length > 0">
          <div class="rounded-lg shadow-lg" style="background: linear-gradient(180deg, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.2)); padding: 1.5rem;">
            <h3 class="text-lg font-bold mb-4" style="color: #f3ce74;">Document Information</h3>
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <p class="text-sm font-semibold" style="color: #f6ead0;">Document Title</p>
                <p class="text-base font-semibold" style="color: #fff8e7;">{{ viewer.document?.title || '-' }}</p>
              </div>
              <div>
                <p class="text-sm font-semibold" style="color: #f6ead0;">Document No.</p>
                <p class="text-base font-semibold" style="color: #fff8e7;">{{ viewer.document?.document_number || '-' }}</p>
              </div>
            </div>

            <h3 class="text-lg font-bold mb-4" style="color: #f3ce74;">Summary of Actions</h3>
            <div class="summary-table-wrap">
              <table class="summary-table">
                <thead>
                  <tr>
                    <th>User Name</th>
                    <th>Position</th>
                    <th>Action</th>
                    <th>Remarks</th>
                    <th>Date Signed</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="action in viewer.actions" :key="action.id">
                    <td class="text-left">{{ action.full_name || '-' }}</td>
                    <td class="text-left">{{ action.position || '-' }}</td>
                    <td class="text-center">
                      <span
                        class="inline-flex px-3 py-1 rounded-full text-xs font-semibold"
                        :class="action.action === 'approve' 
                          ? 'bg-emerald-100 text-emerald-800' 
                          : 'bg-red-100 text-red-800'"
                      >
                        {{ action.action === 'approve' ? 'Approved' : 'Disapproved' }}
                      </span>
                    </td>
                    <td class="text-left">
                      <div class="max-w-xs truncate" :title="action.remarks || ''">
                        {{ action.remarks || '-' }}
                      </div>
                    </td>
                    <td class="mono text-left">
                      {{ formatDate(action.date_signed) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div v-else class="flex items-center justify-center h-full" style="color: #f6ead0;">
          No actions recorded for this document.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { documentService, summaryService } from '@/services/api';
import { useAppModal } from '@/composables/useAppModal';

const props = defineProps({
  id: String,
});

const tabs = [
  { label: 'BOARD OF REGENTS', value: 'bor' },
  { label: 'ACADEMIC COUNCIL', value: 'uac' },
  { label: 'ADMINISTRATIVE COUNCIL', value: 'uadmin' },
];

const activeTab = ref('bor');
const documents = ref([]);
const isDocumentsLoading = ref(false);
const errorMessage = ref('');
const searchTerm = ref('');
const selectedMonth = ref('');
const { showError } = useAppModal();

const viewer = reactive({
  open: false,
  isLoading: false,
  isDownloading: false,
  document: null,
  actions: [],
  title: '',
});

const activeTabLabel = computed(() => tabs.find((tab) => tab.value === activeTab.value)?.label || '');

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

const getDocumentStatusClass = (status) => {
  if (status === 'pending') return 'bg-yellow-50 text-yellow-700 border-yellow-200';
  if (status === 'approved' || status === 'completed') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  if (status === 'rejected' || status === 'disapproved') return 'bg-red-50 text-red-700 border-red-200';
  if (status === 'partially_approved') return 'bg-sky-50 text-sky-700 border-sky-200';
  return 'bg-gray-50 text-gray-700 border-gray-200';
};

const formatDate = (date) => {
  if (!date) return '-';
  const normalized = String(date).replace(' ', 'T');
  const dateObj = new Date(`${normalized}Z`);
  if (Number.isNaN(dateObj.getTime())) return String(date);
  return dateObj.toLocaleString();
};

const loadDocuments = async () => {
  errorMessage.value = '';
  isDocumentsLoading.value = true;
  try {
    const response = await documentService.byRecipientType(activeTab.value);
    documents.value = response?.data || [];
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to load summary documents.';
    await showError(errorMessage.value, 'Load Failed');
  } finally {
    isDocumentsLoading.value = false;
  }
};

const viewSummary = async (doc) => {
  if (!doc?.id) return;
  
  viewer.open = true;
  viewer.isLoading = true;
  viewer.document = doc;
  viewer.title = `Summary - ${doc.document_number || doc.title || doc.id}`;
  viewer.actions = [];

  try {
    const response = await summaryService.byDocument(doc.id);
    const data = response?.data || {};
    viewer.actions = data.actions || [];
  } catch (error) {
    const message = error?.message || 'Failed to load summary actions.';
    await showError(message, 'Load Failed');
    viewer.actions = [];
  } finally {
    viewer.isLoading = false;
  }
};

const closeSummary = () => {
  viewer.open = false;
  viewer.isLoading = false;
  viewer.isDownloading = false;
  viewer.document = null;
  viewer.actions = [];
  viewer.title = '';
};

const downloadSummaryPDF = async () => {
  if (!viewer.document?.id) return;
  
  viewer.isDownloading = true;
  try {
    const blob = await summaryService.download(viewer.document.id);
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `summary-${viewer.document.document_number || viewer.document.id}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  } catch (error) {
    const message = error?.message || 'Failed to download summary PDF.';
    await showError(message, 'Download Failed');
  } finally {
    viewer.isDownloading = false;
  }
};

const changeTab = async (type) => {
  if (activeTab.value === type) return;
  activeTab.value = type;
  await loadDocuments();
};

const refreshActiveTab = async () => {
  await loadDocuments();
};

onMounted(async () => {
  await loadDocuments();
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
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 0.6rem;
  background: rgba(0, 0, 0, 0.18);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  border-bottom: 1px solid rgba(255, 255, 255, 0.18);
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
  gap: 0.55rem;
  flex-wrap: wrap;
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
  gap: 0.4rem;
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
  font-size: 0.85rem;
  padding: 0.5rem 0.75rem;
}

.icon {
  width: 14px;
  height: 14px;
}

.summary-table-wrap {
  overflow-x: auto;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 0.6rem;
  background: rgba(0, 0, 0, 0.18);
  margin-top: 1rem;
}

.summary-table {
  width: 100%;
  border-collapse: collapse;
}

.summary-table th,
.summary-table td {
  border-bottom: 1px solid rgba(255, 255, 255, 0.18);
  padding: 0.75rem 0.65rem;
  text-align: center;
  font-size: 0.95rem;
  vertical-align: top;
}

.summary-table th {
  color: #f3ce74;
  background: rgba(0, 0, 0, 0.3);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 800;
  white-space: nowrap;
  font-size: 0.82rem;
}

.summary-table tbody tr:last-child td {
  border-bottom: 0;
}

.summary-table tbody tr {
  background: rgba(0, 0, 0, 0.18);
}

.summary-table tbody tr:hover {
  background: rgba(255, 255, 255, 0.05);
}

.summary-table td {
  color: #fff8e7;
}

.summary-table .mono {
  white-space: nowrap;
  color: #fff7e2;
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

