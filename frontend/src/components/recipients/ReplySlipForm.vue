<template>
  <section class="manager-screen">
    <header class="manager-hero">
      <p class="hero-kicker">OUBS PORTAL</p>
      <h1>{{ formTitle }}</h1>
      <p class="hero-subtitle">Fill out and submit your {{ formTitle.toLowerCase() }} for this document.</p>
    </header>

    <article class="manager-card">
      <div class="manager-toolbar">
        <div></div>
        <button
          type="button"
          class="refresh-btn"
          @click="goBackToDocuments"
        >
          ← Back to Documents
        </button>
      </div>

      <div class="manager-card-body pt-2 pb-4">

        <!-- Status banners -->
        <div v-if="isCheckingReply" class="banner-info">
          Loading {{ formTitle.toLowerCase() }} status...
        </div>
        <div v-else-if="hasSubmitted" class="banner-warning">
          You already submitted a {{ formTitle.toLowerCase() }} for this document.
        </div>
        <div v-else-if="isViewOnly" class="banner-warning">
          This document is view-only. Responses are disabled.
        </div>
        <div v-else-if="isClosed" class="banner-error">
          This document is already closed and no longer accepts replies.
        </div>

        <div v-if="errorMessage" class="text-sm mb-4" style="color: #ffd2d2;">
          {{ errorMessage }}
        </div>

        <!-- Form -->
        <form class="form-section" @submit.prevent="submitReplySlip">

          <!-- Form card -->
          <div class="form-card">

            <!-- Header -->
            <div class="form-card-header">
              <h3 class="form-card-title">{{ formHeaderTitle }}</h3>
            </div>

            <!-- Document info -->
            <div class="form-grid">
              <div class="form-info-row">
                <span class="form-info-label">Document No.:</span>
                <span class="form-info-value">{{ form.memoSecNo || '-' }}</span>
              </div>
              <div class="form-info-row">
                <span class="form-info-label">Document Name:</span>
                <span class="form-info-value">{{ form.documentTitle || '-' }}</span>
              </div>
            </div>

            <p class="form-instruction">Please check the appropriate box below:</p>

            <!-- Radio choices -->
            <div class="radio-group">
              <label class="radio-label">
                <input
                  v-model="form.action"
                  type="radio"
                  class="radio-input"
                  value="approve"
                  :disabled="isSubmitting || hasSubmitted || isClosed || isViewOnly"
                />
                <span class="radio-text">APPROVED</span>
              </label>
              <label class="radio-label">
                <input
                  v-model="form.action"
                  type="radio"
                  class="radio-input"
                  value="disapprove"
                  :disabled="isSubmitting || hasSubmitted || isClosed || isViewOnly"
                />
                <span class="radio-text">DISAPPROVED</span>
              </label>
            </div>

            <!-- Remarks -->
            <div class="form-field">
              <label class="field-label" for="remarks">
                {{ remarksLabel }} (optional)
              </label>
              <textarea
                id="remarks"
                v-model.trim="form.remarks"
                rows="4"
                class="form-textarea"
                :placeholder="remarksPlaceholder"
                :disabled="isSubmitting || hasSubmitted || isClosed || isViewOnly"
              ></textarea>
            </div>

            <!-- Signatory info -->
            <div class="signatory-grid">
              <div>
                <div class="signatory-label">By:</div>
                <div class="signatory-name">{{ form.fullName || '-' }}</div>
                <div class="signatory-position">{{ form.position || '-' }}</div>
              </div>
              <div>
                <div class="signatory-label">Date:</div>
                <div class="signatory-name">{{ form.dateDisplay }}</div>
              </div>
            </div>
          </div>

          <!-- Submit -->
          <div class="form-actions">
            <button
              type="submit"
              class="submit-btn"
              :disabled="isSubmitting || hasSubmitted || isCheckingReply || isClosed || isViewOnly"
            >
              <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
              </svg>
              {{ isSubmitting ? 'Submitting...' : submitButtonLabel }}
            </button>
          </div>
        </form>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { documentService, replySlipService } from '@/services/api';
import { useAppModal } from '@/composables/useAppModal';

const props = defineProps({
  id: String,
});

const router = useRouter();
const authStore = useAuthStore();
const { showError, showSuccess } = useAppModal();

const isSubmitting = ref(false);
const isCheckingReply = ref(false);
const hasSubmitted = ref(false);
const errorMessage = ref('');
const isClosed = ref(false);
const isViewOnly = ref(false);

const form = reactive({
  memoSecNo: '',
  documentTitle: '',
  action: 'approve',
  remarks: '',
  fullName: authStore.user?.full_name || '',
  position: authStore.user?.position || '',
  dateDisplay: '',
});

const documentId = computed(() => Number(props.id));
const isBorUser = computed(() => authStore.user?.user_type === 'bor');
const formTitle = computed(() => (isBorUser.value ? 'Reply Slip' : 'Response'));
const formHeaderTitle = computed(() => (isBorUser.value ? 'REPLY SLIP' : 'RESPONSE'));
const remarksLabel = computed(() => (isBorUser.value ? 'Remarks' : 'Comments'));
const remarksPlaceholder = computed(() => (isBorUser.value ? 'Enter your remarks (optional)' : 'Enter your comments (optional)'));
const submitButtonLabel = computed(() => (isBorUser.value ? 'Submit Reply Slip' : 'Submit Response'));

const goBackToDocuments = () => {
  router.push({ name: 'RecipientDocuments' });
};

const setDateDisplay = () => {
  form.dateDisplay = new Date().toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const loadDocumentDetails = async () => {
  if (!documentId.value) return;
  try {
    const response = await documentService.getById(documentId.value);
    const data = response?.data || response;
    form.memoSecNo = data?.document_number || '';
    form.documentTitle = data?.title || '';
    const status = `${data?.status || ''}`.toLowerCase();
    isViewOnly.value = Number(data?.allow_replies ?? 1) === 0 || data?.is_view_only === true;
    if (!isViewOnly.value && (data?.is_reply_open === false || status === 'closed')) {
      isClosed.value = true;
      await showError('This document is already closed and no longer accepts replies.', 'Document Closed');
    }
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to load document details.';
  }
};

const checkExistingReply = async () => {
  if (!documentId.value || !authStore.user?.id) return;
  isCheckingReply.value = true;
  try {
    const response = await replySlipService.byDocument(documentId.value);
    const replies = response?.data || [];
    hasSubmitted.value = replies.some((reply) => Number(reply.user_id) === Number(authStore.user.id));
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to check existing reply.';
  } finally {
    isCheckingReply.value = false;
  }
};

const submitReplySlip = async () => {
  if (!documentId.value) {
    errorMessage.value = 'Invalid document ID.';
    return;
  }
  if (isViewOnly.value) {
    errorMessage.value = 'This document is view-only and does not accept replies.';
    await showError(errorMessage.value, 'View Only');
    return;
  }

  errorMessage.value = '';
  isSubmitting.value = true;
  try {
    await replySlipService.create({
      document_id: documentId.value,
      action: form.action,
      remarks: form.remarks,
    });
    hasSubmitted.value = true;
    await showSuccess(`${formTitle.value} submitted successfully.`, 'Submission Complete');
    router.push({ name: 'MyReplies' });
  } catch (error) {
    errorMessage.value = error?.message || `Failed to submit ${formTitle.value.toLowerCase()}.`;
    await showError(errorMessage.value, 'Submission Failed');
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(async () => {
  setDateDisplay();
  await loadDocumentDetails();
  if (isViewOnly.value) return;
  if (!String(authStore.user?.signature_image || '').trim()) {
    await showError('Please set your e-signature first.', 'E-Signature Required');
    router.push({ name: 'RecipientSignatureSetup' });
    return;
  }
  await checkExistingReply();
});
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
  transition: filter 120ms ease;
}

.refresh-btn:hover {
  filter: brightness(1.1);
}

.manager-card-body {
  margin-top: 0.75rem;
}

/* ── Status banners ───────────────────────────────────────── */
.banner-info {
  font-size: 0.875rem;
  padding: 0.65rem 0.9rem;
  border-radius: 0.6rem;
  margin-bottom: 1rem;
  border: 1px solid rgba(212, 160, 23, 0.4);
  background: rgba(212, 160, 23, 0.08);
  color: #f6ead0;
}

.banner-warning {
  font-size: 0.875rem;
  padding: 0.65rem 0.9rem;
  border-radius: 0.6rem;
  margin-bottom: 1rem;
  border: 1px solid rgba(212, 160, 23, 0.5);
  background: rgba(62, 39, 35, 0.4);
  color: #f0c04f;
}

.banner-error {
  font-size: 0.875rem;
  padding: 0.65rem 0.9rem;
  border-radius: 0.6rem;
  margin-bottom: 1rem;
  border: 1px solid rgba(220, 38, 38, 0.5);
  background: rgba(220, 38, 38, 0.15);
  color: #fca5a5;
}

/* ── Form layout ──────────────────────────────────────────── */
.form-section {
  max-width: 44rem;
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

/* ── Form inner card ──────────────────────────────────────── */
.form-card {
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 0.6rem;
  background: rgba(0, 0, 0, 0.18);
  padding: 1.2rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-card-header {
  text-align: center;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.form-card-title {
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: #fff8e7;
  text-transform: uppercase;
}

/* ── Document info grid ───────────────────────────────────── */
.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.5rem;
}

@media (min-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.form-info-row {
  font-size: 0.9rem;
  color: #fff8e7;
}

.form-info-label {
  font-weight: 700;
  margin-right: 0.3rem;
}

.form-info-value {
  color: #f6ead0;
  border-bottom: 1px solid rgba(212, 160, 23, 0.5);
  padding-left: 0.25rem;
  display: inline-block;
  min-width: 8rem;
}

.form-instruction {
  font-size: 0.875rem;
  color: #fff8e7;
}

/* ── Radio group ──────────────────────────────────────────── */
.radio-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.radio-input {
  width: 1rem;
  height: 1rem;
  accent-color: #f0c04f;
  cursor: pointer;
}

.radio-input:disabled {
  cursor: not-allowed;
}

.radio-text {
  font-size: 0.875rem;
  font-weight: 700;
  color: #fff8e7;
  letter-spacing: 0.04em;
}

/* ── Textarea field ───────────────────────────────────────── */
.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.field-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: #f3ce74;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.form-textarea {
  width: 100%;
  border-radius: 0.6rem;
  padding: 0.6rem 0.75rem;
  font-size: 0.875rem;
  border: 1px solid rgba(212, 160, 23, 0.4);
  background: rgba(0, 0, 0, 0.3);
  color: #fff8e7;
  resize: vertical;
}

.form-textarea:focus {
  outline: none;
  border-color: rgba(240, 192, 79, 0.7);
  box-shadow: 0 0 0 2px rgba(240, 192, 79, 0.2);
}

.form-textarea:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

/* ── Signatory block ──────────────────────────────────────── */
.signatory-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  padding-top: 0.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.12);
}

.signatory-label {
  font-size: 0.72rem;
  font-weight: 700;
  color: #f3ce74;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 0.2rem;
}

.signatory-name {
  font-size: 0.9rem;
  font-weight: 700;
  color: #fff8e7;
}

.signatory-position {
  font-size: 0.85rem;
  color: #f6ead0;
  margin-top: 0.1rem;
}

/* ── Actions ──────────────────────────────────────────────── */
.form-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.submit-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  height: 2.4rem;
  padding: 0 1.2rem;
  border-radius: 0.5rem;
  border: 1px solid rgba(212, 160, 23, 0.6);
  background: linear-gradient(to bottom right, #156a3b, #0d3d20);
  color: #f0c04f;
  font-size: 0.875rem;
  font-weight: 700;
  transition: filter 120ms ease;
}

.submit-btn:hover:not(:disabled) {
  filter: brightness(1.1);
}

.submit-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.btn-icon {
  width: 0.9rem;
  height: 0.9rem;
  flex-shrink: 0;
}
</style>