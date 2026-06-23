import axios from 'axios'

// Single Axios instance shared across the entire app.
// baseURL points at the Herd-served Laravel backend.
const api = axios.create({
  baseURL: 'http://backend.test/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Request interceptor — reads the Sanctum token from localStorage and attaches
// it as a Bearer token on every outgoing request. This means no individual
// component or store needs to manage the Authorization header manually.
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default api
