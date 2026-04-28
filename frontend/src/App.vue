<template>
  <router-view />
  <AppModalHost />
  <NotificationContainer />
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'
import AppModalHost from '@/components/common/AppModalHost.vue'
import NotificationContainer from '@/components/common/NotificationContainer.vue'

const authStore = useAuthStore()
</script>

<style>
/* Global styles */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
