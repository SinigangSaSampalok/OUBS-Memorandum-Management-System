<template>
  <div class="fixed left-0 bottom-0 h-screen w-20 bg-white border-r border-gray-200 shadow-lg flex flex-col items-center py-4 z-30">
    <!-- Main Navigation Items -->
    <div class="flex-1 flex flex-col items-center gap-4">
      <slot />
    </div>

    <!-- Notification Bell at Bottom -->
    <div class="w-full flex justify-center pb-4">
      <NotificationBell />
    </div>
  </div>
</template>

<script setup>
import NotificationBell from './NotificationBell.vue'
</script>

<style scoped>
/* Sidebar styling */
</style>
