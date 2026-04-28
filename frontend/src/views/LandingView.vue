<template>
  <LandingLayout>

    <div class="flex flex-col items-center px-4 py-8 sm:py-12">

      <!-- Welcome Section -->
      <div class="text-center mb-10 sm:mb-12 max-w-3xl">
        <h2 class="text-3xl sm:text-5xl font-bold mb-4" style="color: #3E2723">Welcome to Office of University Board Secretary Reply System</h2>
        <p class="text-lg sm:text-xl" style="color: #5C3A1E">
          A centralized platform for approval workflows and communication 
          between the Office of University Board Secretary and University Councils.
        </p>
      </div>
      <br>
      <!-- Login Option Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8 w-full max-w-4xl mb-10 sm:mb-12">

        <!-- OUBS Card -->
        <div
          class="bg-white rounded-2xl border border-slate-200 shadow-[0_16px_40px_-26px_rgba(15,23,42,0.45)] overflow-hidden transform transition-transform hover:-translate-y-1 cursor-pointer focus-visible:ring-2 focus-visible:ring-amber-500/60"
          role="button"
          tabindex="0"
          @click="selectRole('oubs')"
          @keydown.enter.prevent="selectRole('oubs')"
          @keydown.space.prevent="selectRole('oubs')"
        >
          <div class="p-7 flex flex-col items-center" style="background: #5C3A1E; padding-top: 16px;">
            <div class="flex items-center justify-center w-20 h-20 bg-white rounded-xl">
              <svg class="w-10 h-10" style="color: #5C3A1E" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-white text-center mt-4">OUBS</h3>
            <p class="text-center mt-2 text-base" style="color: #D4A017">Office of University Board Secretary</p>
          </div>
          <div class="p-7">
            <ul class="space-y-3 text-gray-600 text-base">
              <li class="flex items-center">
                <svg class="w-6 h-6 mr-2 flex-shrink-0" style="color: #2E7D32" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Upload and manage documents
              </li>
              <li class="flex items-center">
                <svg class="w-6 h-6 mr-2 flex-shrink-0" style="color: #2E7D32" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Monitor approval status
              </li>
              <li class="flex items-center">
                <svg class="w-6 h-6 mr-2 flex-shrink-0" style="color: #2E7D32" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Generate reports and summaries
              </li>
            </ul>
            <button 
              @click="selectRole('oubs')"
              class="app-btn w-full mt-6 text-white py-4 px-5 rounded-lg transition-opacity font-semibold hover:opacity-80 text-lg"
              style="background: #5C3A1E"
            >
              Access OUBS Portal
            </button>
          </div>
        </div>

        <!-- Recipients Card -->
        <div
          class="bg-white rounded-2xl border border-slate-200 shadow-[0_16px_40px_-26px_rgba(15,23,42,0.45)] overflow-hidden transform transition-transform hover:-translate-y-1 cursor-pointer focus-visible:ring-2 focus-visible:ring-amber-500/60"
          role="button"
          tabindex="0"
          @click="selectRole('recipient')"
          @keydown.enter.prevent="selectRole('recipient')"
          @keydown.space.prevent="selectRole('recipient')"
        >
          <div class="p-7 flex flex-col items-center" style="background: linear-gradient(135deg, #5C3A1E, #3E2723); padding-top: 16px;">
            <div class="flex items-center justify-center w-20 h-20 bg-white rounded-xl">
              <svg class="w-10 h-10" style="color: #D4A017" fill="currentColor" viewBox="0 0 20 20">
                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-white text-center mt-4">RECIPIENTS</h3>
            <p class="text-center mt-2 text-base" style="color: #D4A017">University Council Members</p>
          </div>
          <div class="p-7">
            <ul class="space-y-3 text-gray-600 text-base">
              <li class="flex items-center">
                <svg class="w-6 h-6 mr-2 flex-shrink-0" style="color: #2E7D32" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                View assigned documents
              </li>
              <li class="flex items-center">
                <svg class="w-6 h-6 mr-2 flex-shrink-0" style="color: #2E7D32" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Submit replies with e-signature
              </li>
              <li class="flex items-center">
                <svg class="w-6 h-6 mr-2 flex-shrink-0" style="color: #2E7D32" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Track your responses
              </li>
            </ul>
            <button 
              @click="selectRole('recipient')"
              class="app-btn w-full mt-6 text-white py-4 px-5 rounded-lg transition-opacity font-semibold hover:opacity-80 text-lg"
              style="background: linear-gradient(to right, #3E2723, #5C3A1E)"
            >
              Access Recipient Portal
            </button>
          </div>
        </div>
      </div>
      <br>
      <!-- Features Row -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8 w-full max-w-5xl">

        <div class="flex flex-col items-center text-center">
          <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4" style="background: linear-gradient(135deg, #D4A017, #C09010)">
            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
            </svg>
          </div>
          <h4 class="text-lg font-semibold" style="color: #3E2723">Secure Document Management</h4>
          <p class="mt-2" style="color: #5C3A1E">End-to-end encrypted document storage and sharing</p>
        </div>

        <div class="flex flex-col items-center text-center">
          <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4" style="background: linear-gradient(135deg, #D4A017, #C09010)">
            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
          </div>
          <h4 class="text-lg font-semibold" style="color: #3E2723">Digital Approval Workflow</h4>
          <p class="mt-2" style="color: #5C3A1E">Streamlined approval process with e-signatures</p>
        </div>

        <div class="flex flex-col items-center text-center">
          <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4" style="background: linear-gradient(135deg, #D4A017, #C09010)">
            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z" clip-rule="evenodd" />
            </svg>
          </div>
          <h4 class="text-lg font-semibold" style="color: #3E2723">Real-time Monitoring</h4>
          <p class="mt-2" style="color: #5C3A1E">Track document status and responses in real-time</p>
        </div>

      </div>

    </div>

  </LandingLayout>
</template>

<script setup>
import { useRouter } from 'vue-router';
import LandingLayout from '@/components/layout/LandingLayout.vue';
import { useAppModal } from '@/composables/useAppModal';

const router = useRouter();
const { showConfirm } = useAppModal();

const recipientPrivacyNotice = `
<div class="privacy-panel">
  <p class="privacy-intro">
    By continuing to the Recipient Portal, you acknowledge and agree to the following:
  </p>

  <div class="privacy-section">
    <h4 class="privacy-title">E-Signature Consent</h4>
    <ul class="privacy-list">
      <li>Your uploaded e-signature will be collected and stored in this system.</li>
      <li>Your e-signature will be used as your official signature when you submit responses.</li>
      <li>You are responsible for protecting your account credentials and preventing unauthorized use.</li>
    </ul>
  </div>

  <div class="privacy-section">
    <h4 class="privacy-title">Document Access and Copies</h4>
    <ul class="privacy-list">
      <li>Documents assigned to you may be viewed and downloaded for official university work only.</li>
      <li>Downloaded copies may contain sensitive or confidential information and must be handled securely.</li>
      <li>Sharing, forwarding, or reproducing documents outside authorized recipients is prohibited.</li>
    </ul>
  </div>

  <div class="privacy-section">
    <h4 class="privacy-title">Records and Accountability</h4>
    <ul class="privacy-list">
      <li>Your actions in the system (viewing, downloading, signing, and submitting responses) may be logged for audit and compliance purposes.</li>
    </ul>
  </div>

  <p class="privacy-intro">If you do not agree, select Cancel.</p>
</div>
`;

const selectRole = async (role) => {
  if (role === 'oubs') {
    router.push('/oubs/login');
  } else {
    const accepted = await showConfirm({
      title: 'Privacy Notice and Consent',
      message: recipientPrivacyNotice,
      confirmText: 'I Agree',
      cancelText: 'Cancel',
    });

    if (!accepted) {
      return;
    }

    router.push('/recipient/select');
  }
};
</script>
