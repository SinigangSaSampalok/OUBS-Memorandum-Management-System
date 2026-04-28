import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './index.css'
import { initializeCsrfToken } from './services/api'
import './services/csrfTester' // Import CSRF tester for console testing

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Initialize CSRF token before mounting the app
// This ensures the token is available for all POST/PUT/DELETE requests
console.log('[APP] Initializing Vue application...');

initializeCsrfToken()
  .then(() => {
    console.log('[CSRF] Token initialized - app is ready');
    app.mount('#app');
  })
  .catch((error) => {
    console.warn('[CSRF] WARNING: Token initialization failed:', error.message);
    console.warn('[CSRF] WARNING: Proceeding without CSRF token (may cause login to fail)');
    // Still mount the app - token can be retried on first request
    app.mount('#app');
  });