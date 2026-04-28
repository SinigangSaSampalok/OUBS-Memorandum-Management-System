<template>
  <section class="manager-screen">
    <header class="manager-hero">
      <p class="hero-kicker">OUBS PORTAL</p>
      <h1>Set Your E-Signature</h1>
      <p class="hero-subtitle">
        Upload your e-signature once. It will be saved to your account and used automatically when you submit replies.
      </p>
    </header>

    <article class="manager-card">
      <div class="manager-card-body pt-2 pb-4">
        <div v-if="errorMessage" class="text-sm mb-4" style="color: #ffd2d2;">
          {{ errorMessage }}
        </div>

        <div class="form-section">
          <!-- Signature Upload -->
          <div class="form-row">
            <div class="form-field">
              <div class="flex items-center justify-between gap-3 mb-2">
                <label class="field-label">E-Signature</label>
                <button
                  type="button"
                  class="clear-btn"
                  :disabled="isSaving"
                  @click="clearSignature"
                >
                  Clear
                </button>
              </div>
              <div class="file-input-wrap">
                <input
                  ref="signatureFileRef"
                  type="file"
                  accept="image/png,image/jpeg,image/jpg,image/webp"
                  class="file-input"
                  :disabled="isSaving"
                  @change="onSignatureFileChange"
                />
                <p v-if="signatureFileName" class="file-hint mt-2">
                  Selected: {{ signatureFileName }}
                </p>
              </div>
              <p class="field-hint">Upload PNG/JPG/WEBP, max 5MB.</p>
            </div>
          </div>

          <!-- Signature Preview -->
          <div v-if="signatureImage" class="form-row">
            <div class="form-field">
              <label class="field-label">Preview</label>
              <div class="sig-preview-wrap">
                <img :src="signatureImage" alt="E-Signature Preview" class="sig-preview-img" />
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="form-actions">
            <button
              type="button"
              class="submit-btn"
              :disabled="isSaving || !signatureImage"
              @click="saveSignature"
            >
              <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
              </svg>
              {{ isSaving ? 'Saving...' : 'Save E-Signature' }}
            </button>
            <p v-if="!signatureImage" class="text-xs" style="color: #ffd2d2;">
              Please upload your e-signature image.
            </p>
          </div>
        </div>
      </div>
    </article>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { userService } from '@/services/api';
import { useAppModal } from '@/composables/useAppModal';

const router = useRouter();
const authStore = useAuthStore();
const { showError, showSuccess } = useAppModal();

const signatureFileRef = ref(null);
const signatureFileName = ref('');
const signatureImage = ref('');
const errorMessage = ref('');
const isSaving = ref(false);

const SIGNATURE_MAX_WIDTH = 820;
const SIGNATURE_MAX_HEIGHT = 200;

const clearSignature = () => {
  signatureImage.value = '';
  signatureFileName.value = '';
  if (signatureFileRef.value) {
    signatureFileRef.value.value = '';
  }
};

const onSignatureFileChange = async (event) => {
  const file = event.target.files?.[0];
  if (!file) {
    clearSignature();
    return;
  }

  if (!file.type.startsWith('image/')) {
    clearSignature();
    errorMessage.value = 'Please upload an image file for e-signature.';
    await showError(errorMessage.value, 'Invalid Signature File');
    return;
  }

  if (file.size > 5 * 1024 * 1024) {
    clearSignature();
    errorMessage.value = 'Signature image must be 5MB or less.';
    await showError(errorMessage.value, 'File Too Large');
    return;
  }

  let imageUrl = '';
  try {
    imageUrl = URL.createObjectURL(file);
    const img = new Image();

    await new Promise((resolve, reject) => {
      img.onload = resolve;
      img.onerror = reject;
      img.src = imageUrl;
    });

    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    if (!context) throw new Error('Failed to process signature image.');

    const sourceWidth = img.naturalWidth || img.width || SIGNATURE_MAX_WIDTH;
    const sourceHeight = img.naturalHeight || img.height || SIGNATURE_MAX_HEIGHT;
    const scale = Math.min(
      SIGNATURE_MAX_WIDTH / Math.max(1, sourceWidth),
      SIGNATURE_MAX_HEIGHT / Math.max(1, sourceHeight),
      1
    );

    const targetWidth = Math.max(1, Math.floor(sourceWidth * scale));
    const targetHeight = Math.max(1, Math.floor(sourceHeight * scale));

    canvas.width = targetWidth;
    canvas.height = targetHeight;
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.drawImage(img, 0, 0, targetWidth, targetHeight);

    signatureImage.value = canvas.toDataURL('image/png');
    signatureFileName.value = file.name;
    errorMessage.value = '';
  } catch (error) {
    clearSignature();
    errorMessage.value = 'Failed to process signature image.';
    await showError(errorMessage.value, 'File Processing Failed');
  } finally {
    if (imageUrl) URL.revokeObjectURL(imageUrl);
  }
};

const saveSignature = async () => {
  if (!signatureImage.value) {
    errorMessage.value = 'E-signature image is required.';
    await showError(errorMessage.value, 'Missing Signature');
    return;
  }

  errorMessage.value = '';
  isSaving.value = true;
  try {
    const response = await userService.updateSignature({
      signature_image: signatureImage.value,
    });
    const updatedUser = response?.data || {};
    authStore.updateUser({
      ...(authStore.user || {}),
      ...updatedUser,
      signature_image: updatedUser.signature_image || signatureImage.value,
    });

    await showSuccess('E-signature saved successfully.', 'Setup Complete');
    router.push({ name: 'RecipientHome' });
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to save e-signature.';
    await showError(errorMessage.value, 'Save Failed');
  } finally {
    isSaving.value = false;
  }
};
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

.manager-card-body {
  margin-top: 0.75rem;
}

/* ── Form section ─────────────────────────────────────────── */
.form-section {
  max-width: 42rem;
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
}

.form-row {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

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

.field-hint {
  font-size: 0.78rem;
  color: #f6ead0;
  opacity: 0.8;
  margin-top: 0.3rem;
}

.file-hint {
  font-size: 0.78rem;
  color: #f6ead0;
}

/* ── File input area ──────────────────────────────────────── */
.file-input-wrap {
  border: 1px solid rgba(212, 160, 23, 0.4);
  border-radius: 0.6rem;
  background: rgba(0, 0, 0, 0.3);
  padding: 0.75rem 1rem;
}

.file-input {
  display: block;
  width: 100%;
  font-size: 0.875rem;
  color: #fff8e7;
}

.file-input:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

/* ── Signature preview ────────────────────────────────────── */
.sig-preview-wrap {
  border: 1px solid rgba(212, 160, 23, 0.4);
  border-radius: 0.6rem;
  background: rgba(255, 255, 255, 0.95);
  padding: 0.75rem;
  display: inline-block;
}

.sig-preview-img {
  max-height: 6rem;
  max-width: 100%;
  display: block;
  object-fit: contain;
}

/* ── Actions ──────────────────────────────────────────────── */
.form-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

/* ── Buttons ──────────────────────────────────────────────── */
.submit-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  height: 2.4rem;
  padding: 0 1.1rem;
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

.clear-btn {
  display: inline-flex;
  align-items: center;
  height: 1.75rem;
  padding: 0 0.65rem;
  border-radius: 0.4rem;
  border: 1px solid rgba(212, 160, 23, 0.6);
  background: rgba(0, 0, 0, 0.18);
  color: #f0c04f;
  font-size: 0.72rem;
  font-weight: 700;
  transition: filter 120ms ease;
}

.clear-btn:hover:not(:disabled) {
  filter: brightness(1.1);
}

.clear-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.btn-icon {
  width: 0.9rem;
  height: 0.9rem;
  flex-shrink: 0;
}
</style>