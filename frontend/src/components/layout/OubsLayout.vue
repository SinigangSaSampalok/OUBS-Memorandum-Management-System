<template>
  <div class="min-h-screen flex flex-col bg-[radial-gradient(circle_at_top,#fef9f5_0%,#efe5d7_44%,#e9dccb_100%)]">

    <header class="shadow-xl" style="background: linear-gradient(to right, #3E2723, #5C3A1E)">
      <div class="flex justify-center px-4 py-4 sm:px-6">
        <div class="max-w-5xl w-full flex flex-col items-center gap-4 sm:flex-row sm:items-center sm:justify-between sm:gap-8">
          <div class="flex items-center gap-3 sm:gap-4">
            <slot name="header-action" />
            <div class="flex items-center justify-center w-14 h-14 sm:w-20 sm:h-20 rounded-full bg-white p-1">
              <img
                src="@/assets/logo_oubs.png"
                alt="OUBS Logo"
                class="w-full h-full object-contain"
              />
            </div>

            <div class="text-left min-w-0">
              <div class="text-left cursor-pointer">
                <h1 class="text-base sm:text-2xl font-bold leading-tight" style="color: #FFFFFF">Office of University Board Secretary</h1>
                <p class="mt-1 text-xs sm:text-sm" style="color: #D4A017">Centralized Reply Management System</p>
              </div>
            </div>
          </div>

          <div class="text-center sm:text-right">
            <p class="text-xs sm:text-sm font-semibold" style="color: #D4A017">Benguet State University</p>
          </div>
        </div>
      </div>

      <div class="w-full h-1" style="background: #D4A017"></div>
    </header>

    <div class="flex flex-1 flex-col lg:flex-row">
      <main class="flex-1 flex flex-col">
        <slot />
      </main>
    </div>

    <footer class="shadow-inner w-full" style="background: linear-gradient(to right, #3E2723, #5C3A1E)">
      <div class="w-full h-1" style="background: #D4A017"></div>

      <div class="w-full px-4 py-6 sm:py-8">
        <div class="flex flex-col items-center gap-3">
          <div class="flex items-center gap-2" style="color: #D4A017">
            <div class="w-1 h-1 rounded-full" style="background: #D4A017"></div>
            <p class="text-sm sm:text-base font-semibold text-center">
              &copy; 2026 Office of University Board Secretary. All rights reserved.
            </p>
            <div class="w-1 h-1 rounded-full" style="background: #D4A017"></div>
          </div>
          <p class="text-xs sm:text-sm text-center" style="color: rgba(212, 160, 23, 0.8)">
            For technical support, contact:
            <a
              href="https://mail.google.com/mail/?view=cm&fs=1&to=oubs@bsu.edu.ph"
              target="_blank"
              rel="noopener noreferrer"
              class="font-semibold underline"
              style="color: #D4A017"
            >oubs@bsu.edu.ph</a>
            | Tel:
            <a href="tel:422-2009" class="font-semibold" style="color: #D4A017">422-2009</a>
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>
