import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('auth_token'))
  const user = ref(JSON.parse(localStorage.getItem('auth_user') || 'null'))

  const isAuthenticated = computed(() => !!token.value)
  const currentUser = computed(() => user.value)

  function setToken(newToken) {
    token.value = newToken
    localStorage.setItem('auth_token', newToken)
    axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`
    axios.defaults.baseURL = 'http://127.0.0.1:8000';
  }

  function setUser(userData) {
    user.value = userData
    localStorage.setItem('auth_user', JSON.stringify(userData))
  }

  function clearAuth() {
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    delete axios.defaults.headers.common['Authorization']
  }

  async function logout() {
    try {
      await axios.post('/api/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      clearAuth()
    }
  }

  async function checkAuth() {
    if (!token.value) return false

    try {
      const response = await axios.get('/api/user')
      setUser(response.data.user)
      return true
    } catch (error) {
      if (error.response?.status === 401) {
        clearAuth()
      }
      return false
    }
  }

  // Initialize axios header
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  return {
    token,
    user,
    isAuthenticated,
    currentUser,
    setToken,
    setUser,
    clearAuth,
    logout,
    checkAuth
  }
})
