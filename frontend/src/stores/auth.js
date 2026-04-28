import { defineStore } from 'pinia';
import { ref } from 'vue';
import { authService } from '@/services/api';

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null);
  const token = ref(null);
  const isAuthenticated = ref(false);

  // OUBS Login (password only)
  const login = async (credentials) => {
    try {
      console.log('Attempting login with:', credentials);
      
      const response = await authService.login(credentials);
      
      console.log('Login response:', response);
      
      // Check if response has the expected structure
      if (!response || !response.user || !response.token) {
        throw new Error('Invalid response from server');
      }
      
      user.value = response.user;
      token.value = response.token;
      isAuthenticated.value = true;
      
      localStorage.setItem('user', JSON.stringify(response.user));
      localStorage.setItem('token', response.token);
      
      console.log('Login successful, user stored:', user.value);
      
      return response;
    } catch (error) {
      console.error('Login error:', error);
      logout();

      const friendlyError = new Error(error.message || 'Login failed. Please try again.');
      friendlyError.status = error.status;
      friendlyError.code = error.code;
      friendlyError.data = error.data;
      throw friendlyError;
    }
  };

  // Recipient Login (full name, password, user_type)
  const recipientLogin = async (credentials) => {
    try {
      console.log('Attempting recipient login with:', {
        full_name: credentials.full_name,
        user_type: credentials.user_type
      });
      
      const response = await authService.recipientLogin(credentials);
      
      console.log('Recipient login response:', response);
      
      // Check if response has the expected structure
      if (!response || !response.user || !response.token) {
        throw new Error('Invalid response from server');
      }
      
      user.value = response.user;
      token.value = response.token;
      isAuthenticated.value = true;
      
      localStorage.setItem('user', JSON.stringify(response.user));
      localStorage.setItem('token', response.token);
      
      console.log('Recipient login successful, user stored:', user.value);
      
      return response;
    } catch (error) {
      console.error('Recipient login error:', error);
      logout();

      const friendlyError = new Error(error.message || 'Login failed. Please check your credentials.');
      friendlyError.status = error.status;
      friendlyError.code = error.code;
      friendlyError.data = error.data;
      throw friendlyError;
    }
  };

  const logout = () => {
    user.value = null;
    token.value = null;
    isAuthenticated.value = false;
    
    localStorage.removeItem('user');
    localStorage.removeItem('token');
    
    console.log('User logged out');
  };

  const updateUser = (updatedUser) => {
    user.value = updatedUser;
    localStorage.setItem('user', JSON.stringify(updatedUser));
  };

  return {
    user,
    token,
    isAuthenticated,
    login,
    recipientLogin,
    logout,
    updateUser
  };
});
