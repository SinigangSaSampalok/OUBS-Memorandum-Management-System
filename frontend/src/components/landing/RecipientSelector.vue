<template>
  <LandingLayout>
 

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center px-4 py-8 sm:py-12">
      <div class="w-full max-w-6xl oubs-recipient-selector">
        <!-- Title Section -->
        <div class="flex flex-col items-center text-center mb-10 sm:mb-16">
          <h2 class="text-3xl sm:text-5xl font-bold mb-4" style="color: #3E2723">Recipient Portal Access</h2>
          <p class="text-lg sm:text-xl max-w-3xl" style="color: #5C3A1E">
            Please select your council to proceed with authentication
          </p>
          <br>
        </div>

        <!-- Council Cards -->
        <div class="flex justify-center mb-10 sm:mb-16">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8 w-full max-w-6xl">
            <!-- Board of Regents -->
            <div
              class="bg-white rounded-2xl border border-slate-200 shadow-[0_16px_40px_-26px_rgba(15,23,42,0.45)] overflow-hidden transform transition-transform hover:-translate-y-1 cursor-pointer focus-visible:ring-2 focus-visible:ring-amber-500/60"
              role="button"
              tabindex="0"
              @click="selectCouncil('bor')"
              @keydown.enter.prevent="selectCouncil('bor')"
              @keydown.space.prevent="selectCouncil('bor')"
            >
              <div class="p-7 flex flex-col items-center" style="background: linear-gradient(to right, #3E2723, #5C3A1E)">
                <div class="flex items-center justify-center w-20 h-20 bg-white rounded-xl">
                  <svg class="w-10 h-10" style="color: #D4A017" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                  </svg>
                </div>
                <h3 class="text-2xl font-bold text-white text-center mt-4">Board of Regents</h3>
                <p class="text-center mt-2 text-base" style="color: #D4A017">12 Members</p>
              </div>
              <div class="p-7">
                <ul class="space-y-3 text-gray-600 mb-6 text-base">
                  <li class="flex items-center">
                    <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Individual accounts for each member
                  </li>
                  <li class="flex items-center">
                    <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Unique passwords per member
                  </li>
                  <li class="flex items-center">
                    <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Highest-level decision making
                  </li>
                </ul>
                <button
                  @click="selectCouncil('bor')"
                  class="app-btn w-full text-white py-4 px-5 rounded-lg transition-colors font-semibold text-lg hover:opacity-90"
                  style="background: linear-gradient(to right, #3E2723, #5C3A1E)"
                >
                  Access BOR Portal
                </button>
              </div>
            </div>

            <!-- University Academic Council -->
            <div
              class="bg-white rounded-2xl border border-slate-200 shadow-[0_16px_40px_-26px_rgba(15,23,42,0.45)] overflow-hidden transform transition-transform hover:-translate-y-1 cursor-pointer focus-visible:ring-2 focus-visible:ring-amber-500/60"
              role="button"
              tabindex="0"
              @click="selectCouncil('uac')"
              @keydown.enter.prevent="selectCouncil('uac')"
              @keydown.space.prevent="selectCouncil('uac')"
            >
              <div class="p-7 flex flex-col items-center" style="background: linear-gradient(135deg, #5C3A1E, #3E2723)">
                <div class="flex items-center justify-center w-20 h-20 bg-white rounded-xl">
                  <svg class="w-10 h-10" style="color: #D4A017" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                  </svg>
                </div>
                <h3 class="text-2xl font-bold text-white text-center mt-4">Academic Council</h3>
                <p class="text-center mt-2 text-base" style="color: #D4A017">University Academic Council</p>
              </div>
              <div class="p-7">
                <ul class="space-y-3 text-gray-600 mb-6 text-base">
                  <li class="flex items-center">
                    <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Academic affairs and policies
                  </li>
                  <li class="flex items-center">
                    <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Individual login with shared password
                  </li>
                  <li class="flex items-center">
                    <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Curriculum and academic standards
                  </li>
                </ul>
                <button
                  @click="selectCouncil('uac')"
                  class="app-btn w-full text-white py-4 px-5 rounded-lg transition-colors font-semibold text-lg hover:opacity-90"
                  style="background: linear-gradient(to right, #5C3A1E, #3E2723)"
                >
                  Access Academic Council Portal
                </button>
              </div>
            </div>

            <!-- University Administrative Council -->
            <div
              class="bg-white rounded-2xl border border-slate-200 shadow-[0_16px_40px_-26px_rgba(15,23,42,0.45)] overflow-hidden transform transition-transform hover:-translate-y-1 cursor-pointer focus-visible:ring-2 focus-visible:ring-amber-500/60"
              role="button"
              tabindex="0"
              @click="selectCouncil('uadmin')"
              @keydown.enter.prevent="selectCouncil('uadmin')"
              @keydown.space.prevent="selectCouncil('uadmin')"
            >
              <div class="p-7 flex flex-col items-center" style="background: linear-gradient(to bottom, #3E2723, #5C3A1E)">
                <div class="flex items-center justify-center w-20 h-20 bg-white rounded-xl">
                  <svg class="w-10 h-10" style="color: #D4A017" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                  </svg>
                </div>
                <h3 class="text-2xl font-bold text-white text-center mt-4">Administrative Council</h3>
                <p class="text-center mt-2 text-base" style="color: #D4A017">University Administrative Council</p>
              </div>
              <div class="p-7">
                <ul class="space-y-3 text-gray-600 mb-6 text-base">
                  <li class="flex items-center">
                    <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Administrative operations
                  </li>
                  <li class="flex items-center">
                    <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Individual login with shared password
                  </li>
                  <li class="flex items-center">
                    <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Budget and resource management
                  </li>
                </ul>
                <button
                  @click="selectCouncil('uadmin')"
                  class="app-btn w-full text-white py-4 px-5 rounded-lg transition-colors font-semibold text-lg hover:opacity-90"
                  style="background: linear-gradient(to right, #3E2723, #5C3A1E)"
                >
                  Access Admin Council Portal
                </button>
              </div>
            </div>
          </div>
        </div>
        <br>

        
        <br>
        <!-- Features -->
        <div class="flex justify-center">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8 w-full max-w-6xl">
            <div class="flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4" style="background: linear-gradient(135deg, #D4A017, #C09010)">
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </div>
              <h4 class="text-lg font-semibold" style="color: #3E2723">Secure Authentication</h4>
              <p class="mt-2" style="color: #5C3A1E">Role-based access with individual credentials</p>
            </div>
            <div class="flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4" style="background: linear-gradient(135deg, #D4A017, #C09010)">
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z" />
                </svg>
              </div>
              <h4 class="text-lg font-semibold" style="color: #3E2723">Document Review</h4>
              <p class="mt-2" style="color: #5C3A1E">Access and review assigned documents securely</p>
            </div>
            <div class="flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4" style="background: linear-gradient(135deg, #D4A017, #C09010)">
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd" />
                </svg>
              </div>
              <h4 class="text-lg font-semibold" style="color: #3E2723">Digital Signatures</h4>
              <p class="mt-2" style="color: #5C3A1E">Submit official replies with e-signatures</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    
  </LandingLayout>
</template>

<script setup>
import { useRouter } from 'vue-router';
import LandingLayout from '@/components/layout/LandingLayout.vue';
const router = useRouter();

const selectCouncil = (council) => {
  router.push(`/recipient/login/${council}`);
};
</script>
