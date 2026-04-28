<template>
  <LandingLayout>
    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center py-8 sm:py-12 px-4 sm:px-6 lg:px-8">
      <div class="content-container oubs-login-card max-w-xl rounded-2xl border border-slate-200 bg-white/95 p-5 sm:p-7 shadow-[0_24px_64px_-36px_rgba(15,23,42,0.55)]">
        <div class="max-w-md w-full mx-auto space-y-6 sm:space-y-8 oubs-login-card-margin">

          <!-- Header -->
          <div>
            <div class="flex justify-center">
              <div class="p-3 rounded-full" style="background: linear-gradient(135deg, #D4A017, #C09010)">
                <svg
                  class="w-12 h-12 text-white"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"
                  />
                </svg>
              </div>
            </div>

            <h2 class="mt-4 sm:mt-6 text-center text-2xl sm:text-3xl font-extrabold" style="color: #3E2723">
              {{ councilInfo.name }} Portal
            </h2>

            <p class="mt-2 text-center text-lg" style="color: #5C3A1E">
              {{ councilInfo.description }}
            </p>
          </div>

          <!-- Login Form -->
          <form class="mt-6 sm:mt-8 space-y-6" @submit.prevent="handleLogin">
            <div class="rounded-xl border border-slate-200 bg-slate-50/60 space-y-4 px-4 sm:px-6 py-4">
              <!-- Full Name with Dropdown -->
              <div>
                <label class="block text-lg font-medium mb-2" style="color: #3E2723">
                  Full Name
                </label>
                <select
                  v-model="form.full_name"
                  required
                  class="app-input cursor-pointer"
                  :disabled="loadingMembers"
                >
                  <option value="">{{ loadingMembers ? 'Loading members...' : 'Select your name' }}</option>
                  <option v-for="member in members" :key="member.id" :value="member.full_name">
                    {{ member.full_name }} - {{ member.position }}
                  </option>
                </select>
                <p v-if="loadingMembers" class="text-base mt-1" style="color: #5C3A1E">
                  Loading member list...
                </p>
                <p v-else-if="members.length === 0" class="text-base mt-1 text-red-500">
                  No members found. Please contact administrator.
                </p>
                <p v-else class="text-base mt-1" style="color: #5C3A1E">
                  {{ members.length }} member(s) available - type to search or click to select
                </p>
              </div>

              <!-- Password -->
              <div>
                <label class="block text-lg font-medium mb-2" style="color: #3E2723">
                  Password
                </label>

                <div class="relative">
                  <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    class="app-input pr-10"
                    :placeholder="councilInfo.passwordHint"
                  />

                  <!-- Show / Hide -->
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700"
                  >
                    <!-- Eye -->
                    <svg
                      v-if="!showPassword"
                      class="w-5 h-5"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5
                           c4.478 0 8.268 2.943 9.542 7
                           -1.274 4.057-5.064 7-9.542 7
                           -4.477 0-8.268-2.943-9.542-7z"
                      />
                    </svg>

                    <!-- Eye Off -->
                    <svg
                      v-else
                      class="w-5 h-5"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 3l18 18"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M10.584 10.586a2 2 0 002.828 2.828"
                      />
                    </svg>
                  </button>
                </div>

                <p v-if="councilType !== 'bor'" class="text-base mt-1" style="color: #5C3A1E">
                  Shared password for all {{ councilInfo.name }} members
                </p>
              </div>

              <div v-if="errorMessage" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-lg text-red-700">
                {{ errorMessage }}
              </div>

              <div
                v-if="showPasswordSetupPrompt"
                class="rounded-md border px-3 py-3 text-lg flex flex-col gap-3"
                style="border-color: #D4A017; background: rgba(212, 160, 23, 0.1); color: #5C3A1E"
              >
                <p>This user havent set their password yet.</p>
                <button
                  type="button"
                  class="app-btn self-start text-white hover:opacity-90"
                  style="background: linear-gradient(to right, #3E2723, #5C3A1E)"
                  @click="openSetupModal"
                >
                  Set Password
                </button>
              </div>

              <div v-if="infoMessage" class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-lg text-green-700">
                {{ infoMessage }}
              </div>
            </div>
            <br>
            <div class="text-right">
              <button
                type="button"
                class="text-lg font-medium underline underline-offset-2 hover:opacity-80"
                style="color: #D4A017"
                :disabled="passwordNotSet"
                :style="passwordNotSet ? { opacity: '0.5', cursor: 'not-allowed' } : { color: '#D4A017' }"
                @click="openForgotModal"
              >
                Forgot password?
              </button>
            </div>

            <!-- Submit -->
            <button
              type="submit"
              :disabled="loading || loadingMembers"
              :class="councilInfo.buttonClass"
              class="app-btn w-full py-3 text-lg text-white font-medium disabled:opacity-50 hover:opacity-90"
              style="background: linear-gradient(to right, #3E2723, #5C3A1E)"
            >
              {{ loading ? 'Authenticating...' : 'Sign in' }}
            </button>

            <!-- Back -->
            <div class="text-center">
              <button
                type="button"
                @click="$router.push('/recipient/select')"
                class="app-btn w-full text-lg text-white hover:opacity-90"
                style="background: #ED2939"
              >
                ← Choose Different Council
              </button>
            </div>
          </form>
          <br>
          <!-- Info -->
          <div class="mt-6 p-4 bg-yellow-50 border rounded-md">
            <p class="text-lg text-yellow-700 justify-center text-center">
              {{ councilInfo.memberInfo }}
            </p>
          </div>
        </div>
      </div>
    </main>

    <div v-if="showSetupModal" class="app-modal-overlay">
      <div class="app-modal-dialog max-w-md">
        <div style="padding: 15px; color: white; background: linear-gradient(to right, #3E2723, #5C3A1E); border-bottom: 1px solid #e2e8f0;">
          <h3 style="font-size: 18px; font-weight: bold; letter-spacing: 0.025em;">Set Password</h3>
          <p style="font-size: 14px; margin-top: 8px; opacity: 0.9;">{{ form.full_name || 'Selected user' }} ({{ councilInfo.name }})</p>
        </div>

        <div style="padding: 10px; background: white;">
          <div style="margin-bottom: 10px;">
            <label class="block text-base font-semibold" style="color: #3E2723; margin-bottom: 10px;">New Password</label>
            <div class="relative">
              <input
                v-model="setupForm.password"
                :type="showSetupPassword ? 'text' : 'password'"
                class="app-input text-base py-3 w-full pr-10"
                placeholder="Minimum 6 characters"
              />
              <button
                type="button"
                @click="showSetupPassword = !showSetupPassword"
                class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700"
              >
                <svg
                  v-if="!showSetupPassword"
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
                <svg
                  v-else
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-4.803m5.604-1.159a3 3 0 11-4.242 4.242 3 3 0 014.242-4.242zm5.223 2.779h.008v.008h-.008v-.008zm0 5.5h.008v.008h-.008v-.008z"
                  />
                </svg>
              </button>
            </div>
          </div>
          <div style="margin-bottom: 10px;">
            <label class="block text-base font-semibold" style="color: #3E2723; margin-bottom: 10px;">Confirm Password</label>
            <div class="relative">
              <input
                v-model="setupForm.confirmPassword"
                :type="showSetupConfirmPassword ? 'text' : 'password'"
                class="app-input text-base py-3 w-full pr-10"
                placeholder="Re-enter password"
              />
              <button
                type="button"
                @click="showSetupConfirmPassword = !showSetupConfirmPassword"
                class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700"
              >
                <svg
                  v-if="!showSetupConfirmPassword"
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
                <svg
                  v-else
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-4.803m5.604-1.159a3 3 0 11-4.242 4.242 3 3 0 014.242-4.242zm5.223 2.779h.008v.008h-.008v-.008zm0 5.5h.008v.008h-.008v-.008z"
                  />
                </svg>
              </button>
            </div>
          </div>
          <p v-if="setupError" style="margin-top: 24px;" class="text-sm text-red-600 font-medium">{{ setupError }}</p>
        </div>

        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; background: #f8fafc; padding: 16px 20px;">
          <button
            type="button"
            class="app-btn-secondary app-btn-cancel min-w-20 rounded-lg font-medium"
            :disabled="setupLoading"
            @click="closeSetupModal"
          >
            Cancel
          </button>
          <button
            type="button"
            class="app-btn min-w-24 rounded-lg text-white"
            :disabled="setupLoading"
            style="background: linear-gradient(to right, #3E2723, #5C3A1E)"
            @click="submitSetPassword"
          >
            {{ setupLoading ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showForgotModal" class="app-modal-overlay">
      <div class="app-modal-dialog max-w-md">
        <div style="padding: 15px; color: white; background: linear-gradient(to right, #3E2723, #5C3A1E); border-bottom: 1px solid #e2e8f0;">
          <h3 style="font-size: 18px; font-weight: bold; letter-spacing: 0.025em;">Request Password Reset</h3>
          <p style="font-size: 14px; margin-top: 8px; opacity: 0.9;">This request will be sent to OUBS for review.</p>
        </div>

        <div style="padding: 10px; background: white;">
          <div style="margin-bottom: 10px;">
            <label class="block text-base font-semibold" style="color: #3E2723; margin-bottom: 10px;">Full Name</label>
            <input
              v-model="form.full_name"
              type="text"
              class="app-input text-base py-3 w-full"
              readonly
            />
          </div>
          <div style="margin-bottom: 10px;">
            <label class="block text-base font-semibold" style="color: #3E2723; margin-bottom: 10px;">Reason (optional)</label>
            <textarea
              v-model="forgotReason"
              rows="4"
              class="app-textarea text-base py-3 w-full"
              placeholder="e.g., I forgot my password"
            ></textarea>
          </div>
          <p v-if="forgotError" style="margin-top: 24px;" class="text-sm text-red-600 font-medium">{{ forgotError }}</p>
        </div>

        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; background: #f8fafc; padding: 16px 20px;">
          <button
            type="button"
            class="app-btn-secondary app-btn-cancel min-w-20 rounded-lg font-medium"
            :disabled="forgotLoading"
            @click="closeForgotModal"
          >
            Cancel
          </button>
          <button
            type="button"
            class="app-btn min-w-24 rounded-lg text-white"
            :disabled="forgotLoading"
            style="background: linear-gradient(to right, #3E2723, #5C3A1E)"
            @click="submitForgotPasswordRequest"
          >
            {{ forgotLoading ? 'Sending...' : 'Send Request' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showRequestStatusModal" class="app-modal-overlay">
      <div class="app-modal-dialog max-w-md">
        <div class="px-8 py-6 border-b-2 border-slate-200">
          <h3 class="text-2xl font-bold" style="color: #3E2723">{{ requestStatusTitle }}</h3>
          <p class="text-sm mt-3" style="color: #5C3A1E">{{ requestStatusMessage }}</p>
          <p v-if="requestStatusNote" class="text-sm mt-3" style="color: #3E2723">
            <span class="font-semibold">Note:</span> {{ requestStatusNote }}
          </p>
        </div>

        <div class="px-8 py-5 border-t-2 border-slate-200 flex items-center justify-end gap-4">
          <button
            type="button"
            class="app-btn-secondary app-btn-solid-red px-6 py-2.5 font-medium"
            @click="closeRequestStatusModal"
          >
            Close
          </button>
          <button
            v-if="requestStatusAction === 'set-password'"
            type="button"
            class="app-btn text-white hover:opacity-90 px-8 py-2.5 font-semibold text-base"
            style="background: linear-gradient(to right, #3E2723, #5C3A1E)"
            @click="openSetupFromStatusModal"
          >
            Set New Password
          </button>
        </div>
      </div>
    </div>
  </LandingLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import LandingLayout from '@/components/layout/LandingLayout.vue'
import api, { authService } from '@/services/api'
import { useAppModal } from '@/composables/useAppModal'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { showError, showSuccess, showConfirm } = useAppModal()

const councilType = route.params.type
const loading = ref(false)
const loadingMembers = ref(false)
const showPassword = ref(false)
const showSetupPassword = ref(false)
const showSetupConfirmPassword = ref(false)
const members = ref([])
const errorMessage = ref('')
const infoMessage = ref('')
const showPasswordSetupPrompt = ref(false)
const showSetupModal = ref(false)
const setupLoading = ref(false)
const setupError = ref('')
const showForgotModal = ref(false)
const forgotReason = ref('')
const forgotLoading = ref(false)
const forgotError = ref('')
const showRequestStatusModal = ref(false)
const requestStatusTitle = ref('')
const requestStatusMessage = ref('')
const requestStatusNote = ref('')
const requestStatusAction = ref('')
const passwordNotSet = ref(false)
let statusCheckTimer = null

const form = ref({
  full_name: '',
  password: '',
  user_type: councilType
})

const setupForm = ref({
  password: '',
  confirmPassword: ''
})

const councilInfo = computed(() => ({
  bor: {
    name: 'Board of Regents',
    description: '12 Voting Members - Individual Accounts',
    color: 'bg-amber-100',
    textColor: 'text-yellow-600',
    buttonClass: 'bg-yellow-600 hover:bg-yellow-700',
    passwordHint: 'Enter your personal password',
    memberInfo: 'Each BOR member has individual credentials'
  },
  uac: {
    name: 'Academic Council',
    description: 'University Academic Council',
    color: 'bg-blue-100',
    textColor: 'text-blue-600',
    buttonClass: 'bg-blue-600 hover:bg-blue-700',
    passwordHint: 'Enter shared password',
    memberInfo: 'Academic deans and faculty representatives'
  },
  uadmin: {
    name: 'Administrative Council',
    description: 'University Administrative Council',
    color: 'bg-green-100',
    textColor: 'text-green-600',
    buttonClass: 'bg-green-600 hover:bg-green-700',
    passwordHint: 'Enter shared password',
    memberInfo: 'Administrative officers and department heads'
  }
}[councilType]))

// Fetch members based on council type
const fetchMembers = async () => {
  loadingMembers.value = true
  try {
    const endpoint = councilType === 'bor' 
      ? '/members/bor' 
      : `/members/${councilType}`
    
    const response = await api.get(endpoint)
    members.value = response.data || []
  } catch (error) {
    console.error('Failed to load members:', error)
    members.value = []
  } finally {
    loadingMembers.value = false
  }
}

const handleLogin = async () => {
  loading.value = true
  errorMessage.value = ''
  infoMessage.value = ''
  showPasswordSetupPrompt.value = false

  try {
    await authStore.recipientLogin(form.value)
    await showSuccess(`Welcome to the ${councilInfo.value.name} portal.`, 'Login Successful', 1000)
    router.push('/recipient/home')
  } catch (e) {
    if (e.code === 'PASSWORD_NOT_SET') {
      errorMessage.value = e.message || "This user havent set their password yet."
      showPasswordSetupPrompt.value = true
      return
    }

    errorMessage.value = e.message || 'Login failed. Please check your credentials.'
    await showError(errorMessage.value, 'Login Failed')
  } finally {
    loading.value = false
  }
}

const checkRecipientStatus = async () => {
  const fullName = form.value.full_name?.trim()
  if (!fullName) {
    showPasswordSetupPrompt.value = false
    return
  }

  const selectedMember = members.value.find(
    (member) => (member.full_name || '').trim().toLowerCase() === fullName.toLowerCase()
  )
  if (!selectedMember) {
    showPasswordSetupPrompt.value = false
    return
  }

  try {
    const response = await authService.recipientStatus({
      full_name: fullName,
      user_type: councilType
    })

    const status = response?.data
    if (status?.exists && !status?.has_password) {
      showPasswordSetupPrompt.value = true
      infoMessage.value = ''
      errorMessage.value = "This user havent set their password yet."
      passwordNotSet.value = true
    } else {
      showPasswordSetupPrompt.value = false
      if (errorMessage.value === "This user havent set their password yet.") {
        errorMessage.value = ''
      }
      passwordNotSet.value = false
    }

    const requestStatus = status?.reset_request?.status
    if (requestStatus === 'approved') {
      requestStatusTitle.value = 'Password Reset Approved'
      requestStatusMessage.value = 'Your password reset request was approved by OUBS. You can now set a new password.'
      requestStatusNote.value = status?.reset_request?.reviewer_note || ''
      requestStatusAction.value = 'set-password'
      showRequestStatusModal.value = true
    } else if (requestStatus === 'rejected') {
      requestStatusTitle.value = 'Password Reset Rejected'
      requestStatusMessage.value = 'Your password reset request was rejected by OUBS.'
      requestStatusNote.value = status?.reset_request?.reviewer_note || ''
      requestStatusAction.value = ''
      showRequestStatusModal.value = true
    }
  } catch (error) {
    showPasswordSetupPrompt.value = false
  }
}

const scheduleRecipientStatusCheck = () => {
  if (statusCheckTimer) {
    clearTimeout(statusCheckTimer)
  }

  statusCheckTimer = setTimeout(() => {
    checkRecipientStatus()
  }, 250)
}

const openSetupModal = () => {
  setupError.value = ''
  setupForm.value.password = ''
  setupForm.value.confirmPassword = ''
  showSetupModal.value = true
}

const closeSetupModal = () => {
  showSetupModal.value = false
}

const openForgotModal = () => {
  if (passwordNotSet.value) {
    errorMessage.value = 'You must set a password first before you can request a password reset.'
    return
  }

  forgotError.value = ''
  forgotReason.value = ''

  if (!form.value.full_name?.trim()) {
    errorMessage.value = 'Please select your full name first.'
    return
  }

  showForgotModal.value = true
}

const closeForgotModal = () => {
  showForgotModal.value = false
}

const submitForgotPasswordRequest = async () => {
  forgotError.value = ''
  infoMessage.value = ''
  forgotLoading.value = true
  try {
    const confirmed = await showConfirm({
      title: 'Send Password Reset Request',
      message: 'This request will be submitted to OUBS for review.',
      confirmText: 'Send Request',
      cancelText: 'Cancel',
    })
    if (!confirmed) {
      forgotLoading.value = false
      return
    }

    await authService.requestPasswordReset({
      full_name: form.value.full_name.trim(),
      user_type: councilType,
      reason: forgotReason.value.trim(),
    })
    showForgotModal.value = false
    infoMessage.value = 'Password reset request submitted. Please wait for OUBS review.'
    await showSuccess(infoMessage.value, 'Request Submitted')
  } catch (error) {
    forgotError.value = error.message || 'Failed to submit request.'
    await showError(forgotError.value, 'Request Failed')
  } finally {
    forgotLoading.value = false
  }
}

const closeRequestStatusModal = () => {
  showRequestStatusModal.value = false
}

const openSetupFromStatusModal = () => {
  showRequestStatusModal.value = false
  openSetupModal()
}

const submitSetPassword = async () => {
  setupError.value = ''
  infoMessage.value = ''

  if (!form.value.full_name) {
    setupError.value = 'Please select your full name first.'
    return
  }

  if (!setupForm.value.password || !setupForm.value.confirmPassword) {
    setupError.value = 'Please complete both password fields.'
    return
  }

  if (setupForm.value.password.length < 6) {
    setupError.value = 'Password must be at least 6 characters.'
    return
  }

  if (setupForm.value.password !== setupForm.value.confirmPassword) {
    setupError.value = 'Passwords do not match.'
    return
  }

  setupLoading.value = true
  try {
    await authService.setRecipientPassword({
      full_name: form.value.full_name,
      user_type: councilType,
      password: setupForm.value.password
    })

    showSetupModal.value = false
    showPasswordSetupPrompt.value = false
    infoMessage.value = 'Password set successfully. You can now sign in.'
    form.value.password = ''
    await showSuccess(infoMessage.value, 'Password Updated')
  } catch (error) {
    setupError.value = error.message || 'Failed to set password.'
    await showError(setupError.value, 'Update Failed')
  } finally {
    setupLoading.value = false
  }
}

// Load members when component mounts
onMounted(() => {
  fetchMembers()
})

watch(() => form.value.full_name, () => {
  showRequestStatusModal.value = false
  scheduleRecipientStatusCheck()
})

onBeforeUnmount(() => {
  if (statusCheckTimer) {
    clearTimeout(statusCheckTimer)
  }
})
</script>


