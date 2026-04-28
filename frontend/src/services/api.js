import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

// Determine API base URL based on environment
function getApiBaseUrl() {
  // In development, use Vite's environment variables if available
  if (import.meta.env.VITE_API_URL) {
    return import.meta.env.VITE_API_URL;
  }

  // In production, construct from current location
  if (window.location.protocol === 'https:') {
    return `${window.location.protocol}//${window.location.hostname}${window.location.port ? ':' + window.location.port : ''}/api`;
  }

  // Default to localhost for development
  return 'http://localhost:8080/api';
}

const API_BASE_URL = getApiBaseUrl();

// Create axios instance for regular API calls
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true, // Allow cookies to be sent with CORS requests
});

// Create separate axios instance for CSRF token (bypass interceptors)
const csrfAxios = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true,
});

// CSRF token management
let csrfToken = null;
let csrfTokenFetchPromise = null; // For handling concurrent requests

// Function to fetch CSRF token from the backend
export async function initializeCsrfToken() {
  // Return existing promise if already fetching
  if (csrfTokenFetchPromise) {
    return csrfTokenFetchPromise;
  }

  csrfTokenFetchPromise = (async () => {
    try {
      console.log(`[CSRF] Fetching CSRF token from: ${API_BASE_URL}/auth/csrf-token`);
      
      // Use csrfAxios directly to avoid interceptors
      const response = await csrfAxios.get('/auth/csrf-token', {
        timeout: 5000, // 5 second timeout
      });
      
      console.log('[CSRF] Response received:', response.data);
      
      // Extract token from response
      let token = response.data?.token;
      
      if (!token) {
        console.error('[CSRF] ERROR: Invalid response structure:', response.data);
        throw new Error('No CSRF token in response. Received: ' + JSON.stringify(response.data));
      }
      
      csrfToken = token;
      console.log('[CSRF] Token initialized successfully:', csrfToken.substring(0, 10) + '...');
      return csrfToken;
    } catch (error) {
      console.error('[CSRF] ERROR: Failed to fetch CSRF token:', {
        message: error.message,
        status: error.response?.status,
        data: error.response?.data,
      });
      csrfTokenFetchPromise = null; // Reset for retry
      throw error;
    }
  })();

  return csrfTokenFetchPromise;
}

// Function to refresh CSRF token
export async function refreshCsrfToken() {
  csrfTokenFetchPromise = null; // Reset cache
  return initializeCsrfToken();
}

// Request interceptor to add token and CSRF token
let requestRetryCount = 0;
const MAX_CSRF_RETRIES = 2;

api.interceptors.request.use(
  async (config) => {
    // Handle FormData - don't set Content-Type header, let axios handle it
    if (config.data instanceof FormData) {
      delete config.headers['Content-Type'];
      console.log(`[FormData] Detected FormData, allowing axios to set Content-Type automatically`);
    }
    
    // Add bearer token if available
    const authStore = useAuthStore();
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`;
      console.log(`[BEARER] Token added to ${config.method.toUpperCase()} ${config.url}`);
    }
    
    // Add CSRF token for state-changing requests (POST, PUT, DELETE, PATCH)
    if (['post', 'put', 'delete', 'patch'].includes(config.method)) {
      // Ensure we have a CSRF token before making the request
      if (!csrfToken) {
        try {
          console.log(`[CSRF] Token missing for ${config.method.toUpperCase()} request, fetching...`);
          await initializeCsrfToken();
        } catch (error) {
          console.warn('[CSRF] WARNING: Token initialization failed, continuing without token');
        }
      }
      
      if (csrfToken) {
        config.headers['X-CSRF-TOKEN'] = csrfToken;
        console.log(`[CSRF] Token added to ${config.method.toUpperCase()} ${config.url}`, {
          token: csrfToken.substring(0, 16) + '...',
          header: 'X-CSRF-TOKEN',
        });
      } else {
        console.warn(`[CSRF] WARNING: No CSRF token available for ${config.method.toUpperCase()} ${config.url}`);
      }
    }
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor for error handling and token refresh
api.interceptors.response.use(
  (response) => {
    // Reset retry counter on success
    requestRetryCount = 0;
    
    // Log successful state-changing requests with CSRF validation
    if (['post', 'put', 'delete', 'patch'].includes(response.config.method)) {
      console.log(`[SUCCESS] ${response.config.method.toUpperCase()} ${response.config.url} - Status ${response.status} (CSRF validation passed)`, {
        endpoint: response.config.url,
        method: response.config.method.toUpperCase(),
        status: response.status,
        csrf: '✓ Valid',
      });
    } else {
      console.log(`[SUCCESS] ${response.config.method.toUpperCase()} ${response.config.url} - Status ${response.status}`);
    }
    
    // Return the full response data
    return response.data;
  },
  async (error) => {
    console.error('[ERROR] API Error:', error.message);
    
    // Handle 403 Forbidden - may indicate CSRF token is invalid
    if (error.response?.status === 403) {
      const errorMsg = error.response?.data?.error || '';
      const isCSRFError = errorMsg.toLowerCase().includes('csrf') || 
                          errorMsg.includes('token') ||
                          error.response?.data?.message?.toLowerCase().includes('csrf');
      
      if (isCSRFError && requestRetryCount < MAX_CSRF_RETRIES) {
        console.warn(`[CSRF] Token validation failed, retrying... (${requestRetryCount + 1}/${MAX_CSRF_RETRIES})`);
        console.warn('[CSRF] Error details:', error.response?.data);
        requestRetryCount++;
        
        try {
          // Refresh CSRF token and retry request
          await refreshCsrfToken();
          
          // Retry the original request
          const retryConfig = error.config;
          if (csrfToken && ['post', 'put', 'delete', 'patch'].includes(retryConfig.method)) {
            retryConfig.headers['X-CSRF-TOKEN'] = csrfToken;
            console.log(`[CSRF] Retrying ${retryConfig.method.toUpperCase()} with new CSRF token`);
          }
          
          return api.request(retryConfig);
        } catch (refreshError) {
          console.error('CSRF token refresh failed:', refreshError.message);
          requestRetryCount = 0;
          // Continue to normal error handling
        }
      }
      requestRetryCount = 0;
    }
    
    // Handle 401 Unauthorized
    const requestUrl = error.config?.url || '';
    const isAuthLoginRequest = requestUrl.includes('/auth/login') || requestUrl.includes('/auth/recipient-login');

    if (error.response?.status === 401 && !isAuthLoginRequest) {
      const authStore = useAuthStore();
      authStore.logout();
      window.location.href = '/';
    }
    
    // Create a user-friendly error object
    const errorMessage = error.response?.data?.error 
      || error.response?.data?.message 
      || error.message 
      || 'An unexpected error occurred';
    
    // Return a rejected promise with rich error details
    const enhancedError = new Error(errorMessage);
    enhancedError.status = error.response?.status;
    enhancedError.code = error.response?.data?.code || null;
    enhancedError.data = error.response?.data || null;
    return Promise.reject(enhancedError);
  }
);

// Authentication services
export const authService = {
  login: (credentials) => api.post('/auth/login', credentials),
  recipientLogin: (credentials) => api.post('/auth/recipient-login', credentials),
  recipientStatus: (data) => api.post('/auth/recipient-status', data),
  setRecipientPassword: (data) => api.post('/auth/set-recipient-password', data),
  requestPasswordReset: (data) => api.post('/auth/request-password-reset', data),
  loginLogs: () => api.get('/auth/login-logs'),
};

// Document services
export const documentService = {
  getAll: () => api.get('/documents'),
  getById: (id) => api.get(`/documents/${id}`),
  create: (data) => api.post('/documents', data),
  update: (id, data) => api.put(`/documents/${id}`, data),
  delete: (id) => api.delete(`/documents/${id}`),
  upload: (formData) => api.post('/documents/upload', formData),
  download: (id, params = null) => api.get(`/documents/download/${id}`, { responseType: 'blob', params }),
  myDocuments: () => api.get('/documents/my-documents'),
  byRecipientType: (type) => api.get(`/documents/recipient-type/${type}`),
  archive: (id) => api.post(`/documents/archive/${id}`),
  restore: (id) => api.post(`/documents/restore/${id}`),
  archivedDocuments: () => api.get('/documents/archived'),
  archivedByRecipientType: (type) => api.get(`/documents/archived/recipient-type/${type}`),
};

// Reply slip services
export const replySlipService = {
  create: (data) => api.post('/reply-slips', data),
  byDocument: (documentId) => api.get(`/reply-slips/document/${documentId}`),
  myReplies: () => api.get('/reply-slips/my-replies'),
  download: (id) => api.get(`/reply-slips/download/${id}`, { responseType: 'blob' }),
};

// Summary services
export const summaryService = {
  byDocument: (documentId) => api.get(`/summary/document/${documentId}`),
  download: (documentId) => api.get(`/summary/download/${documentId}`, { responseType: 'blob' }),
};

// Member services
export const memberService = {
  byType: (type) => api.get(`/members/${type}`),
  collegeCampuses: () => api.get('/college-campuses'),
  create: (data) => api.post('/users', data),
  update: (id, data) => api.put(`/users/${id}`, data),
  delete: (id) => api.delete(`/users/${id}`),
};

export const passwordResetService = {
  list: () => api.get('/password-reset-requests'),
  review: (id, data) => api.put(`/password-reset-requests/${id}`, data),
};

export const documentReviewService = {
  list: (params = null) => api.get('/document-reviews', { params }),
  update: (id, data) => api.put(`/document-reviews/${id}`, data),
  letter: (id) => api.get(`/document-reviews/${id}/letter`, { responseType: 'blob' }),
  reviewer: () => api.get('/document-reviewer'),
  setReviewer: (userId) => api.put('/document-reviewer', { user_id: userId }),
  unsetReviewer: () => api.delete('/document-reviewer'),
};

// User services
export const userService = {
  profile: () => api.get('/users/profile'),
  updateSignature: (data) => api.put('/users/update-signature', data),
};

// Dashboard services
export const dashboardService = {
  stats: () => api.get('/dashboard/stats'),
  recentActivities: () => api.get('/dashboard/recent-activities'),
};

export default api;
