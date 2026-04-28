<template>
  <section class="upload-screen">
    <header class="upload-hero">
      <p class="hero-kicker">OUBS PORTAL</p>
      <h1>Upload Document</h1>
      <p class="hero-subtitle">Submit memorandums securely to the assigned council.</p>
    </header>

    <article class="upload-card">
      <form class="upload-form" @submit.prevent="submitDocument">
        <div class="form-grid">
          <div class="field">
            <label class="field-label" for="documentName">Document Name</label>
            <input
              id="documentName"
              v-model.trim="form.documentName"
              type="text"
              class="field-input"
              placeholder="e.g., Academic Calendar 2026"
              required
            />
          </div>
          <div class="field">
            <label class="field-label" for="documentNo">Document No.</label>
            <input
              id="documentNo"
              v-model.trim="form.documentNo"
              type="text"
              class="field-input"
              placeholder="e.g., Memo Sec No. / Referendum No. / etc."
              required
            />
          </div>
        </div>

        <div class="form-grid">
          <div class="field">
            <label class="field-label" for="recipientType">Recipient</label>
            <select
              id="recipientType"
              v-model="form.recipientType"
              class="field-input"
              required
            >
              <option disabled value="">Select recipient</option>
              <option value="bor">Board of Regents</option>
              <option value="uadmin">University Administrative Council</option>
              <option value="uac">University Academic Council</option>
            </select>
          </div>
          <div class="field">
            <label class="field-label" for="availabilityDays">Availability (days)</label>
            <input
              id="availabilityDays"
              v-model.number="form.availabilityDays"
              type="number"
              min="1"
              class="field-input"
              placeholder="e.g., 10"
              required
            />
            <p class="field-help">Same deadline for replies and downloads.</p>
          </div>
        </div>

        <fieldset class="panel">
          <legend class="panel-title">Recipient response</legend>
          <div class="radio-grid">
            <label class="radio-item">
              <input
                v-model="form.responseMode"
                type="radio"
                value="respond"
                required
              />
              Allow response (approve/disapprove)
            </label>
            <label class="radio-item">
              <input
                v-model="form.responseMode"
                type="radio"
                value="view"
                required
              />
              View only (no response)
            </label>
          </div>
        </fieldset>

        <div class="panel panel-dashed">
          <label class="field-label" for="documentFile">Attach PDF</label>
          <input
            id="documentFile"
            type="file"
            class="field-input file-input"
            @change="onFileChange"
            ref="fileInputRef"
            accept="application/pdf"
            required
          />
          <p class="field-help field-help-emerald">PDF only. Max size depends on server limits.</p>
        </div>

        <p v-if="errorMessage" class="alert alert-error">{{ errorMessage }}</p>
        <p v-if="successMessage" class="alert alert-success">{{ successMessage }}</p>

        <div class="actions">
          <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
            {{ isSubmitting ? 'Uploading...' : 'Upload Document' }}
          </button>
          <button type="button" class="btn btn-secondary" :disabled="isSubmitting" @click="resetForm">
            Clear
          </button>
        </div>
      </form>
    </article>
  </section>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { documentService } from '@/services/api';
import { useAppModal } from '@/composables/useAppModal';

const form = reactive({
  documentName: '',
  documentNo: '',
  recipientType: '',
  responseMode: 'respond',
  file: null,
  availabilityDays: null,
});

const isSubmitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const fileInputRef = ref(null);
const { showError, showSuccess } = useAppModal();

const getFriendlyError = (error) => {
  if (!error) {
    return 'An unexpected error occurred while uploading the document.';
  }

  if (typeof error === 'string') {
    return error;
  }

  if (error?.data?.errors) {
    const errors = Object.values(error.data.errors).flat();
    return errors.join(' ') || 'Please correct the highlighted fields and try again.';
  }

  if (error?.data?.error) {
    return String(error.data.error);
  }

  if (error?.message) {
    return String(error.message);
  }

  return 'An unexpected error occurred while uploading the document.';
};

const onFileChange = (event) => {
  const files = event.target.files;
  form.file = files && files.length ? files[0] : null;
  if (form.file) {
    const fileName = (form.file.name || '').toLowerCase();
    const isPdfMime = form.file.type === 'application/pdf';
    const isPdfExtension = fileName.endsWith('.pdf');
    if (!isPdfMime && !isPdfExtension) {
      errorMessage.value = 'Only PDF files are allowed.';
      showError(errorMessage.value, 'Invalid File');
      form.file = null;
      if (fileInputRef.value) {
        fileInputRef.value.value = '';
      }
      return;
    }
  }
};

const resetForm = (clearMessages = true) => {
  form.documentName = '';
  form.documentNo = '';
  form.recipientType = '';
  form.responseMode = 'respond';
  form.file = null;
  form.availabilityDays = null;
  if (clearMessages) {
    errorMessage.value = '';
    successMessage.value = '';
  }

  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
};

const submitDocument = async () => {
  errorMessage.value = '';
  successMessage.value = '';

  if (!form.documentName) {
    errorMessage.value = 'Document name is required.';
    await showError(errorMessage.value, 'Missing Information');
    return;
  }

  if (!form.documentNo) {
    errorMessage.value = 'Document number is required.';
    await showError(errorMessage.value, 'Missing Information');
    return;
  }

  if (!form.recipientType) {
    errorMessage.value = 'Please select a recipient type.';
    await showError(errorMessage.value, 'Missing Information');
    return;
  }

  if (!form.file) {
    errorMessage.value = 'Please attach a PDF file to upload.';
    await showError(errorMessage.value, 'Missing File');
    return;
  }

  if (!form.availabilityDays || form.availabilityDays < 1) {
    errorMessage.value = 'Availability days must be a positive number.';
    await showError(errorMessage.value, 'Invalid Deadline');
    return;
  }

  isSubmitting.value = true;

  try {
    const createResponse = await documentService.create({
      document_number: form.documentNo,
      title: form.documentName,
      recipient_type: form.recipientType,
      allow_replies: form.responseMode === 'respond' ? 1 : 0,
    });

    if (createResponse?.status && createResponse.status !== 'success') {
      throw new Error(createResponse.message || createResponse.error || 'Failed to create document record.');
    }

    const documentId = createResponse?.id;
    if (!documentId) {
      throw new Error(createResponse?.message || createResponse?.error || 'Failed to create document record.');
    }

    const formData = new FormData();
    formData.append('document_id', documentId);
    formData.append('document', form.file);
    formData.append('reply_days', String(form.availabilityDays));
    formData.append('download_days', String(form.availabilityDays));

    const uploadResponse = await documentService.upload(formData);
    const uploadData = uploadResponse?.data || uploadResponse;

    if (uploadData?.status && uploadData.status !== 'success') {
      throw new Error(uploadData?.message || uploadData?.error || 'Upload failed.');
    }

    if (uploadData?.error) {
      throw new Error(uploadData.error);
    }

    successMessage.value = 'Document uploaded successfully.';
    await showSuccess(successMessage.value, 'Upload Complete');
    resetForm(false);
  } catch (error) {
    errorMessage.value = getFriendlyError(error);
    await showError(errorMessage.value, 'Upload Failed');
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.upload-screen {
  width: 100%;
  min-height: 100%;
  padding: clamp(0.8rem, 1.8vw, 1.3rem);
  background:
    radial-gradient(circle at 20% 0%, rgba(212, 160, 23, 0.2) 0%, transparent 40%),
    linear-gradient(140deg, #1b5e20 0%, #15471a 42%, #0d3715 100%);
  color: #fff8e7;
}

.upload-hero {
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

.upload-hero h1 {
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

.upload-card {
  margin-top: 0.9rem;
  border: 1px solid rgba(212, 160, 23, 0.55);
  border-radius: 0.75rem;
  padding: 0.95rem;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.1), rgba(12, 12, 12, 0.2));
}

.upload-form {
  display: grid;
  gap: 0.85rem;
}

.form-grid {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: 1fr;
}

@media (min-width: 768px) {
  .form-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.field {
  min-width: 0;
}

.field-label {
  display: block;
  margin-bottom: 0.45rem;
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #f3ce74;
}

.field-help {
  margin-top: 0.35rem;
  color: #f6ead0;
  font-size: 0.86rem;
}

.field-help-emerald {
  color: #d7f3dd;
}

.field-input {
  width: 100%;
  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 0.6rem;
  padding: 0.75rem 0.9rem;
  background: rgba(0, 0, 0, 0.18);
  color: #fff8e7;
  outline: none;
  font-size: 1rem;
  transition: border-color 120ms ease, box-shadow 120ms ease, background 120ms ease;
}

.field-input::placeholder {
  color: rgba(246, 234, 208, 0.72);
}

.field-input:focus {
  border-color: rgba(240, 192, 79, 0.95);
  box-shadow: 0 0 0 3px rgba(240, 192, 79, 0.2);
  background: rgba(0, 0, 0, 0.25);
}

.file-input {
  padding: 0.6rem 0.9rem;
}

.panel {
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 0.6rem;
  padding: 0.85rem;
  background: rgba(0, 0, 0, 0.18);
}

.panel-dashed {
  border-style: dashed;
}

.panel-title {
  padding: 0 0.35rem;
  font-size: 0.95rem;
  color: #f3ce74;
  font-weight: 700;
}

.radio-grid {
  margin-top: 0.55rem;
  display: grid;
  gap: 0.6rem;
}

@media (min-width: 768px) {
  .radio-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.radio-item {
  display: flex;
  align-items: flex-start;
  gap: 0.55rem;
  color: #fff7e2;
  font-size: 0.95rem;
  line-height: 1.2;
}

.radio-item input {
  margin-top: 0.2rem;
}

.alert {
  border-radius: 0.6rem;
  padding: 0.75rem 0.9rem;
  font-size: 0.95rem;
  font-weight: 600;
}

.alert-error {
  border: 1px solid rgba(255, 156, 156, 0.5);
  background: rgba(120, 0, 0, 0.24);
  color: #ffd2d2;
}

.alert-success {
  border: 1px solid rgba(140, 255, 196, 0.45);
  background: rgba(0, 90, 36, 0.22);
  color: #d7ffe7;
}

.actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  padding-top: 0.25rem;
}

.btn {
  border: 1px solid #f0c04f;
  border-radius: 0.6rem;
  min-width: 170px;
  padding: 0.75rem 1.2rem;
  font-size: 1rem;
  font-weight: 700;
  color: #fff8e5;
  transition: filter 120ms ease, transform 120ms ease, opacity 120ms ease;
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(to bottom right, #6f1717, #4a0f0f);
}

.btn-primary:hover:not(:disabled) {
  filter: brightness(1.07);
}

.btn-secondary {
  background: rgba(0, 0, 0, 0.22);
}

.btn-secondary:hover:not(:disabled) {
  filter: brightness(1.06);
}
</style>

