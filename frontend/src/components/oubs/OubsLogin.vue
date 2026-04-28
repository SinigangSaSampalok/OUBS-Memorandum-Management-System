<template>
  <LandingLayout>
    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center py-8 sm:py-12 px-4 sm:px-6 lg:px-8">
      <div class="content-container oubs-login-card max-w-xl rounded-2xl border border-slate-200 bg-white/95 p-5 sm:p-7 shadow-[0_24px_64px_-36px_rgba(15,23,42,0.55)]">
        <div class="max-w-md w-full mx-auto space-y-6 sm:space-y-8 oubs-login-card-margin">
          <!-- Header -->
          <div>
            <h2 class="mt-4 sm:mt-6 text-center text-2xl sm:text-3xl font-extrabold" style="color: #3E2723">
              OUBS Portal Login
            </h2>
            <p class="mt-2 text-center text-lg" style="color: #5C3A1E">
              Office of University Board Secretary – Secure Access
            </p>
          </div>

          <!-- Login Form -->
          <form class="mt-6 sm:mt-8 space-y-6" @submit.prevent="handleLogin">
            <div class="rounded-xl border border-slate-200 bg-slate-50/60 px-4 sm:px-6 py-4">
              <!-- Username (fixed) -->
              <div class="mb-6">
                <label class="block text-lg font-medium mb-2" style="color: #3E2723">
                  Username
                </label>
                <div class="flex items-center bg-gray-100 px-4 py-3 rounded-md">
                  <svg class="w-5 h-5 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      fill-rule="evenodd"
                      d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  <span class="text-lg font-semibold" style="color: #3E2723">oubs</span>
                </div>
              </div>

              <!-- Password -->
              <div>
                <label class="block text-lg font-medium mb-2" style="color: #3E2723">
                  Password
                </label>

                <div class="relative">
                  <input
                    v-model="password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    class="app-input pr-10"
                    placeholder="Enter your password"
                  />

                  <!-- Show / Hide Button -->
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700"
                    tabindex="-1"
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
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5
                           c4.478 0 8.268 2.943 9.542 7
                           -1.274 4.057-5.064 7-9.542 7
                           -4.477 0-8.268-2.943-9.542-7z" />
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
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M10.584 10.586a2 2 0 002.828 2.828" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            <br>
            <!-- Submit -->
            <button
              type="submit"
              :disabled="loading"
              class="app-btn w-full text-lg text-white font-semibold hover:opacity-90 py-3"
              style="background: linear-gradient(to right, #3E2723, #5C3A1E)"
            >
              <span v-if="loading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10"
                    stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0
                       C5.373 0 0 5.373 0 12h4z" />
                </svg>
                Authenticating...
              </span>
              <span v-else>Sign in to OUBS Portal</span>
            </button>
          </form>
          <br>
          <!-- Security Notice -->
          <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
            <div class="flex ">
              <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20 items-center">
                <path
                  fill-rule="evenodd"
                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92
                     c.75 1.334-.213 2.98-1.742 2.98H4.42
                     c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92z"
                  clip-rule="evenodd"
                />
              </svg>
              <div class="ml-3">
                <h3 class="text-lg font-medium" style="color: #92400E">Security Notice</h3>
                <p class="text-lg" style="color: #B45309">
                  This portal is restricted to authorized OUBS personnel only.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </LandingLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import LandingLayout from '@/components/layout/LandingLayout.vue'
import { useAppModal } from '@/composables/useAppModal'

const router = useRouter()
const authStore = useAuthStore()
const { showError, showSuccess } = useAppModal()

const password = ref('')
const loading = ref(false)
const showPassword = ref(false)

const handleLogin = async () => {
  loading.value = true
  try {
    await authStore.login({ password: password.value })
    await showSuccess('Welcome to the OUBS portal.', 'Login Successful', 1000)
    router.push('/oubs/home')
  } catch (error) {
    await showError(error.message || 'Login failed', 'Login Failed')
  } finally {
    loading.value = false
  }
}
</script>


