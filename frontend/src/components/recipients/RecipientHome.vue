<template>
  <section class="dashboard-screen">
    <header class="dashboard-hero">
      <p class="hero-kicker">{{ recipientTypeLabel }}</p>
      <h1>Dashboard Workspace</h1>
      <p class="hero-subtitle">Review memorandums quickly with status and action history in one view.</p>
    </header>

    <div class="top-grid">
      <article class="chart-card">
        <p class="stat-label">Response Progress</p>
        <div class="chart-wrap">
          <div class="chart-ring" :style="ringStyle(responseRate)">
            <span>{{ responseRate }}%</span>
          </div>
          <p class="stat-copy">Signed responses over assigned documents.</p>
        </div>
      </article>
      <article class="chart-card">
        <p class="stat-label">Approval Rate</p>
        <div class="chart-wrap">
          <div class="chart-ring" :style="ringStyle(approvalRate)">
            <span>{{ approvalRate }}%</span>
          </div>
          <p class="stat-copy">Approved responses over total submitted responses.</p>
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
        <p class="stat-copy">Open assigned memorandums and submit responses.</p>
      </article>
      <article class="stat-card workflow">
        <h3>Workflow Tracking</h3>
        <p class="stat-copy">Monitor document status and office-level progress.</p>
      </article>
      <article class="stat-card workflow">
        <h3>Transparency</h3>
        <p class="stat-copy">View approvals, disapprovals, comments, and dates.</p>
      </article>
    </div>

    <section class="signature-panel">
      <article class="signature-card">
        <div class="signature-card-header">
          <div>
            <h3>Your E-Signature</h3>
            <p class="stat-copy">This signature is used for your official response submissions.</p>
          </div>
          <button class="replace-btn" @click="startReplaceSignature">
            Replace E-Signature
          </button>
        </div>

        <div class="signature-preview-box">
          <img v-if="signatureImage" :src="signatureImage" alt="E-Signature Preview" />
          <div v-else class="signature-placeholder">
            <p>No e-signature uploaded yet.</p>
            <router-link class="setup-link" :to="{ name: 'RecipientSignatureSetup' }">
              Set your e-signature now
            </router-link>
          </div>
        </div>

        <div v-if="isReplacingSignature" class="signature-form">
          <p class="signature-note">Replacing your signature requires your account password for confirmation.</p>
          <div class="form-row">
            <label class="field-label">New E-Signature</label>
            <input type="file" accept="image/*" ref="signatureFileRef" @change="onSignatureFileChange" />
            <p v-if="signatureFileName" class="file-hint">Selected: {{ signatureFileName }}</p>
          </div>
          <div class="form-row">
            <label class="field-label">Current Password</label>
            <input type="password" v-model="signaturePassword" autocomplete="current-password" />
          </div>
          <div class="form-actions">
            <button type="button" class="cancel-btn" @click="cancelReplaceSignature" :disabled="isSavingSignature">Cancel</button>
            <button type="button" class="save-btn" @click="saveReplacementSignature" :disabled="isSavingSignature">
              {{ isSavingSignature ? 'Saving...' : 'Save New Signature' }}
            </button>
          </div>
          <div v-if="signaturePreview" class="form-row preview-row">
            <p class="field-label">New Signature Preview</p>
            <img :src="signaturePreview" alt="New E-Signature Preview" class="preview-img" />
          </div>
        </div>
      </article>
    </section>

  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useAppModal } from '@/composables/useAppModal';
import { dashboardService, userService } from '@/services/api';

const router = useRouter();
const authStore = useAuthStore();
const { showConfirm, showError, showSuccess } = useAppModal();
const isLoadingStats = ref(false);
const stats = ref({
  assigned_documents: 0,
  signed_documents: 0,
  pending_responses: 0,
  approved_responses: 0,
  disapproved_responses: 0,
});

const safePercent = (value, total) => {
  if (!total || total <= 0) return 0;
  return Math.max(0, Math.min(100, Math.round((value / total) * 100)));
};

const responseRate = computed(() => safePercent(stats.value.signed_documents ?? 0, stats.value.assigned_documents ?? 0));
const approvalRate = computed(() => safePercent(
  stats.value.approved_responses ?? 0,
  (stats.value.approved_responses ?? 0) + (stats.value.disapproved_responses ?? 0)
));

// Map user_type to readable labels
const recipientTypeLabel = computed(() => {
  const userType = authStore.user?.user_type;
  const labels = {
    'bor': 'Board of Regents',
    'uac': 'University Academic Council',
    'uadmin': 'University Administrative Council'
  };
  return labels[userType] || 'Recipient';
});

const statCards = computed(() => ([
  {
    label: 'Assigned Documents',
    value: stats.value.assigned_documents ?? 0,
    hint: 'Documents currently assigned to your recipient group.',
  },
  {
    label: 'Signed Responses',
    value: stats.value.signed_documents ?? 0,
    hint: 'Documents where you already submitted a signed response.',
  },
  {
    label: 'Pending Responses',
    value: stats.value.pending_responses ?? 0,
    hint: 'Assigned documents you still need to respond to.',
  },
  {
    label: 'Approved',
    value: stats.value.approved_responses ?? 0,
  },
  {
    label: 'Disapproved',
    value: stats.value.disapproved_responses ?? 0,
  },
]));

const ringStyle = (percent) => ({
  background: `conic-gradient(#f0c04f ${percent}%, rgba(255,255,255,0.16) ${percent}% 100%)`,
});

const loadStats = async () => {
  isLoadingStats.value = true;
  try {
    const response = await dashboardService.stats();
    stats.value = {
      ...stats.value,
      ...(response?.data || {}),
    };
  } catch (error) {
    // Keep dashboard usable even if stats API fails.
  } finally {
    isLoadingStats.value = false;
  }
};

const signatureImage = computed(() => authStore.user?.signature_image || '');
const isReplacingSignature = ref(false);
const signatureFileRef = ref(null);
const signaturePreview = ref('');
const signatureFileName = ref('');
const signaturePassword = ref('');
const isSavingSignature = ref(false);

const clearSignatureInputs = () => {
  signaturePreview.value = '';
  signatureFileName.value = '';
  signaturePassword.value = '';
  if (signatureFileRef.value) {
    signatureFileRef.value.value = '';
  }
};

const onSignatureFileChange = async (event) => {
  const file = event.target?.files?.[0] ?? null;
  if (!file) {
    clearSignatureInputs();
    return;
  }

  signatureFileName.value = file.name;
  const reader = new FileReader();
  reader.onload = () => {
    signaturePreview.value = reader.result || '';
  };
  reader.readAsDataURL(file);
};

const startReplaceSignature = () => {
  isReplacingSignature.value = true;
};

const cancelReplaceSignature = () => {
  clearSignatureInputs();
  isReplacingSignature.value = false;
};

const saveReplacementSignature = async () => {
  if (!signaturePreview.value) {
    await showError('Please choose a new signature image before saving.', 'Signature Required');
    return;
  }

  if (!signaturePassword.value.trim()) {
    await showError('Please enter your account password to confirm the change.', 'Password Required');
    return;
  }

  isSavingSignature.value = true;
  try {
    const response = await userService.updateSignature({
      signature_image: signaturePreview.value,
      password: signaturePassword.value,
    });

    if (response?.data?.data) {
      authStore.updateUser(response.data.data);
    }

    await showSuccess('Your e-signature has been updated successfully.', 'E-Signature Updated');
    clearSignatureInputs();
    isReplacingSignature.value = false;
  } catch (error) {
    const message = error?.message || 'Failed to update e-signature. Please try again.';
    await showError(message, 'Update Failed');
  } finally {
    isSavingSignature.value = false;
  }
};

const logout = async () => {
  const confirmed = await showConfirm({
    title: 'Logout',
    message: 'Are you sure you want to log out?',
    confirmText: 'Logout',
    cancelText: 'Stay',
  });
  if (!confirmed) return;

  authStore.logout();
  router.push('/');
};

onMounted(loadStats);
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

.signature-panel {
  margin-top: 0.75rem;
}

.signature-card {
  border: 1px solid rgba(212, 160, 23, 0.55);
  border-radius: 0.75rem;
  padding: 1rem;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.1), rgba(12, 12, 12, 0.2));
  color: #fff7e2;
}

.signature-card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.signature-card-header h3 {
  margin: 0;
  font-size: 1.1rem;
}

.replace-btn,
.cancel-btn,
.save-btn {
  border: 1px solid #f0c04f;
  border-radius: 0.6rem;
  padding: 0.75rem 1rem;
  font-weight: 700;
  color: #fff;
  background: rgba(255, 255, 255, 0.08);
  cursor: pointer;
}

.replace-btn:hover,
.save-btn:hover,
.cancel-btn:hover {
  filter: brightness(1.05);
}

.signature-preview-box {
  margin-top: 1rem;
  min-height: 120px;
  display: grid;
  place-items: center;
  border: 1px dashed rgba(240, 192, 79, 0.55);
  border-radius: 0.75rem;
  padding: 1rem;
  background: rgba(255,255,255,0.06);
}

.signature-preview-box img,
.preview-img {
  max-width: 100%;
  max-height: 180px;
  object-fit: contain;
  display: block;
  margin: 0 auto;
}

.signature-placeholder {
  text-align: center;
  color: #f4e8c6;
}

.signature-placeholder p {
  margin: 0 0 0.75rem;
}

.setup-link {
  display: inline-block;
  color: #f0c04f;
  text-decoration: underline;
}

.signature-form {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(240, 192, 79, 0.18);
}

.form-row {
  margin-top: 1rem;
  display: grid;
  gap: 0.35rem;
}

.field-label {
  font-size: 0.85rem;
  font-weight: 700;
  color: #f8edcc;
}

input[type="password"],
input[type="file"] {
  width: 100%;
  border: 1px solid rgba(240, 192, 79, 0.35);
  border-radius: 0.55rem;
  padding: 0.75rem;
  background: rgba(255,255,255,0.06);
  color: #fff;
}

.form-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 1rem;
}

.signature-note {
  margin-bottom: 0.75rem;
  color: #f4e8c6;
  font-size: 0.92rem;
}

.preview-row {
  margin-top: 1rem;
}

.file-hint {
  color: #f0d885;
  font-size: 0.9rem;
}
</style>

