<template>
  <transition name="app-modal-fade">
    <div
      v-if="modalState.open"
      class="app-modal-overlay z-[100]"
    >
      <div
        class="app-modal-dialog border-white/20 shadow-[0_30px_80px_-30px_rgba(15,23,42,0.75)]"
        :class="isPrivacyNotice ? 'rounded-md max-w-3xl w-[94vw]' : ''"
        :style="privacyDialogStyle"
      >
        <div
          class="px-6 py-5 text-white"
          :class="headerGradientClass"
        >
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-white/20 flex items-center justify-center">
              <svg
                v-if="modalState.type === 'success'"
                class="h-6 w-6"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M20 6 9 17l-5-5" />
              </svg>
              <svg
                v-else-if="modalState.type === 'error'"
                class="h-6 w-6"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <circle cx="12" cy="12" r="10" />
                <path d="M12 8v4" />
                <path d="M12 16h.01" />
              </svg>
              <svg
                v-else
                class="h-6 w-6"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M12 9v4" />
                <path d="M12 17h.01" />
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-bold tracking-tight">{{ modalState.title }}</h3>
          </div>
        </div>

        <div
          class="app-modal-body"
          :class="isPrivacyNotice ? 'px-12 py-8' : ''"
          :style="privacyBodyStyle"
        >
          <div
            v-if="isPrivacyNotice"
            class="privacy-content text-slate-700"
            v-html="modalState.message"
          ></div>
          <p
            v-else
            class="text-sm leading-relaxed text-slate-700 whitespace-pre-line"
          >
            {{ modalState.message }}
          </p>
        </div>

        <div class="app-modal-footer">
          <button
            v-if="modalState.showCancel"
            type="button"
            class="app-btn-secondary app-btn-cancel min-w-20 rounded-lg font-medium"
            @click="closeModal(false)"
          >
            {{ modalState.cancelText }}
          </button>
          <button
            type="button"
            class="app-btn min-w-24 rounded-lg text-white"
            :class="confirmButtonClass"
            @click="closeModal(true)"
          >
            {{ modalState.confirmText }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed } from 'vue';
import { useAppModal } from '@/composables/useAppModal';

const { modalState, closeModal } = useAppModal();

const headerGradientClass = computed(() => {
  if (modalState.type === 'success') {
    return 'bg-gradient-to-r from-emerald-700 via-emerald-600 to-teal-500';
  }
  if (modalState.type === 'error') {
    return 'bg-gradient-to-r from-rose-700 via-red-600 to-orange-500';
  }
  return 'bg-gradient-to-r from-amber-700 via-yellow-600 to-orange-500';
});

const confirmButtonClass = computed(() => {
  if (modalState.type === 'success') {
    return 'bg-emerald-700 hover:bg-emerald-800';
  }
  if (modalState.type === 'error') {
    return 'bg-red-700 hover:bg-red-800';
  }
  return 'bg-amber-700 hover:bg-amber-800';
});

const isPrivacyNotice = computed(() => modalState.title === 'Privacy Notice and Consent');
const privacyDialogStyle = computed(() =>
  isPrivacyNotice.value
    ? {
        maxWidth: '60rem',
        width: '94vw',
        borderRadius: '0.75rem',
      }
    : {}
);
const privacyBodyStyle = computed(() =>
  isPrivacyNotice.value
    ? {
        padding: '1.25rem 1.6rem',
      }
    : {}
);
</script>

<style scoped>
.app-modal-fade-enter-active,
.app-modal-fade-leave-active {
  transition: opacity 0.18s ease;
}

.app-modal-fade-enter-from,
.app-modal-fade-leave-to {
  opacity: 0;
}
</style>

<style scoped>
.privacy-content {
  font-size: 1.05rem;
  line-height: 1.7;
  color: #334155;
}

.privacy-content :deep(.privacy-panel) {
  background: linear-gradient(180deg, #fff7ed 0%, #fffbf5 100%);
  border: 1px solid rgba(234, 88, 12, 0.22);
  border-radius: 1rem;
  padding: 1.15rem 1.2rem;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.55);
}

.privacy-content :deep(.privacy-intro) {
  margin: 0 0 1.2rem 0;
  font-weight: 500;
  color: #374151;
}

.privacy-content :deep(.privacy-section) {
  margin: 0 0 0.75rem 0;
  padding: 0.72rem 0.9rem 0.8rem;
  border-radius: 0.85rem;
  background: #ffffff;
  border: 1px solid rgba(234, 88, 12, 0.18);
  box-shadow: 0 16px 30px -24px rgba(15, 23, 42, 0.5);
}

.privacy-content :deep(.privacy-title) {
  margin: 0 0 0.4rem 0;
  font-size: 1rem;
  font-weight: 700;
  color: #7c2d12;
}

.privacy-content :deep(.privacy-list) {
  margin: 0;
  padding-left: 1.25rem;
  list-style-type: disc !important;
  list-style-position: outside;
}

.privacy-content :deep(.privacy-list li) {
  display: list-item;
  margin: 0.16rem 0;
  color: #475569;
}

.privacy-content :deep(.privacy-list li::marker) {
  color: #ea580c;
}
</style>
