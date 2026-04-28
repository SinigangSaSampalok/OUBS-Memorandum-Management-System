/**
 * CSRF Token Testing Utility
 * Tests all CSRF-protected endpoints and logs results to console
 */

import axios from 'axios';

// Determine API base URL
function getApiBaseUrl() {
  if (import.meta.env.VITE_API_URL) {
    return import.meta.env.VITE_API_URL;
  }
  if (window.location.protocol === 'https:') {
    return `${window.location.protocol}//${window.location.hostname}${window.location.port ? ':' + window.location.port : ''}/api`;
  }
  return 'http://localhost:8080/api';
}

const API_BASE_URL = getApiBaseUrl();

class CSRFTester {
  constructor() {
    this.results = [];
    this.csrfToken = null;
    this.bearerToken = null;
  }

  log(level, message, data = null) {
    const timestamp = new Date().toLocaleTimeString();
    const prefix = {
      info: '[INFO]',
      success: '[SUCCESS]',
      error: '[ERROR]',
      test: '[TEST]',
      request: '[REQUEST]',
      response: '[RESPONSE]',
      csrf: '[CSRF]',
    }[level] || '[LOG]';

    const logMessage = `[${timestamp}] ${prefix} ${message}`;
    
    if (data) {
      console.log(logMessage, data);
    } else {
      console.log(logMessage);
    }

    this.results.push({ level, message, data, timestamp });
  }

  async fetchCSRFToken() {
    this.log('csrf', 'Fetching CSRF token...');
    
    try {
      const response = await axios.get(`${API_BASE_URL}/auth/csrf-token`, {
        withCredentials: true,
        headers: {
          'Content-Type': 'application/json',
        },
      });

      this.csrfToken = response.data.token;
      this.log('csrf', 'CSRF token obtained successfully', {
        token: this.csrfToken.substring(0, 16) + '...',
        header: response.data.headerName,
        cookie: response.data.cookieName,
      });

      return this.csrfToken;
    } catch (error) {
      this.log('error', 'Failed to fetch CSRF token', {
        status: error.response?.status,
        message: error.message,
        data: error.response?.data,
      });
      throw error;
    }
  }

  async makeRequest(method, endpoint, data = null, description = '') {
    const fullUrl = `${API_BASE_URL}${endpoint}`;
    this.log('request', `${method.toUpperCase()} ${endpoint} ${description ? `(${description})` : ''}`);

    try {
      const config = {
        method,
        url: fullUrl,
        withCredentials: true,
        headers: {
          'Content-Type': 'application/json',
        },
      };

      // Add CSRF token for state-changing requests
      if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method.toUpperCase())) {
        config.headers['X-CSRF-TOKEN'] = this.csrfToken;
        this.log('info', 'CSRF token added to X-CSRF-TOKEN header', { token: this.csrfToken.substring(0, 16) + '...' });
      }

      // Add bearer token if available
      if (this.bearerToken) {
        config.headers['Authorization'] = `Bearer ${this.bearerToken}`;
        this.log('info', 'Authorization Bearer token added');
      }

      if (data) {
        config.data = data;
        this.log('info', 'Request payload', data);
      }

      const response = await axios(config);

      this.log('success', `Response received (Status ${response.status})`, {
        status: response.status,
        statusText: response.statusText,
        dataKeys: Object.keys(response.data || {}),
      });

      // Store bearer token if login was successful
      if (endpoint.includes('login') && response.data?.data?.token) {
        this.bearerToken = response.data.data.token;
        this.log('success', 'Bearer token obtained (login successful)');
      }

      return response;
    } catch (error) {
      this.log('error', `Request failed: ${method.toUpperCase()} ${endpoint}`, {
        status: error.response?.status,
        statusText: error.response?.statusText,
        message: error.message,
        errorData: error.response?.data,
        headers: error.response?.headers,
      });

      // Log CSRF-specific errors
      if (error.response?.status === 403) {
        this.log('error', 'CSRF VALIDATION FAILED: Token may be invalid or expired');
      }

      throw error;
    }
  }

  async testPublicEndpoints() {
    this.log('test', 'Testing Public Endpoints (with CSRF protection)');

    // Test login endpoint
    try {
      this.log('test', 'Test 1: POST /auth/login');
      await this.makeRequest('POST', '/auth/login', 
        { email: 'oubs', password: 'oubs123' },
        'User login'
      );
    } catch (error) {
      this.log('error', 'Test 1 failed - expected for invalid credentials');
    }

    // Test recipient login
    try {
      this.log('test', 'Test 2: POST /auth/recipient-login');
      await this.makeRequest('POST', '/auth/recipient-login',
        { documentId: 1, password: 'test' },
        'Recipient login'
      );
    } catch (error) {
      this.log('error', 'Test 2 failed - expected for invalid credentials');
    }

    // Test recipient status
    try {
      this.log('test', 'Test 3: POST /auth/recipient-status');
      await this.makeRequest('POST', '/auth/recipient-status',
        { documentId: 1 },
        'Check recipient status'
      );
    } catch (error) {
      this.log('error', 'Test 3 failed', error.message);
    }

    // Test password reset request
    try {
      this.log('test', 'Test 4: POST /auth/request-password-reset');
      await this.makeRequest('POST', '/auth/request-password-reset',
        { email: 'test@example.com' },
        'Request password reset'
      );
    } catch (error) {
      this.log('error', 'Test 4 failed', error.message);
    }
  }

  async testProtectedEndpoints() {
    this.log('test', 'Testing Protected Endpoints (with CSRF + Bearer Token)');

    if (!this.bearerToken) {
      this.log('error', 'No bearer token available. Skipping protected endpoint tests.');
      this.log('info', 'Try logging in with valid credentials first');
      return;
    }

    // Test create user
    try {
      this.log('test', 'Test 5: POST /users');
      await this.makeRequest('POST', '/users',
        {
          email: 'test@example.com',
          firstName: 'Test',
          lastName: 'User',
          password: 'password123',
        },
        'Create new user'
      );
    } catch (error) {
      this.log('error', 'Test 5 failed', error.message);
    }

    // Test create document
    try {
      this.log('test', 'Test 6: POST /documents');
      await this.makeRequest('POST', '/documents',
        {
          title: 'Test Document',
          documentTypeId: 1,
          description: 'Test description',
        },
        'Create new document'
      );
    } catch (error) {
      this.log('error', 'Test 6 failed', error.message);
    }

    // Test update user
    try {
      this.log('test', 'Test 7: PUT /users/1');
      await this.makeRequest('PUT', '/users/1',
        { firstName: 'Updated' },
        'Update user'
      );
    } catch (error) {
      this.log('error', 'Test 7 failed', error.message);
    }

    // Test create reply slip
    try {
      this.log('test', 'Test 8: POST /reply-slips');
      await this.makeRequest('POST', '/reply-slips',
        {
          documentId: 1,
          action: 'approve',
          comment: 'Looks good',
        },
        'Create reply slip'
      );
    } catch (error) {
      this.log('error', 'Test 8 failed', error.message);
    }
  }

  async runAllTests() {
    console.clear();
    this.log('test', '======== CSRF TOKEN PROTECTION TEST SUITE ========');
    
    this.log('info', `Testing API at: ${API_BASE_URL}`);

    try {
      // Step 1: Fetch CSRF token
      await this.fetchCSRFToken();

      // Step 2: Test public endpoints
      await this.testPublicEndpoints();

      // Step 3: Test protected endpoints (if authenticated)
      await this.testProtectedEndpoints();

      // Summary
      this.printSummary();
    } catch (error) {
      this.log('error', 'Test suite error', error);
    }
  }

  printSummary() {
    this.log('test', '\n======== TEST EXECUTION SUMMARY ========')

    const summary = {
      total: this.results.length,
      success: this.results.filter(r => r.level === 'success').length,
      errors: this.results.filter(r => r.level === 'error').length,
      requests: this.results.filter(r => r.level === 'request').length,
    };

    this.log('info', 'Summary', summary);
    this.log('info', 'All results logged to window.csrfTester.results');
  }
}

// Export for global use
window.csrfTester = new CSRFTester();

export default CSRFTester;
