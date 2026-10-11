import axios, { AxiosError } from 'axios'
import { getStoredToken } from './tokenStorage'

export const API_BASE_URL: string = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api'

/**
 * Shared Axios instance for the Laravel API.
 *
 * `Accept: application/json` matters: without it Laravel answers an
 * unauthenticated request with a redirect to a web login route that does not
 * exist, instead of a 401 JSON response.
 */
export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 15000,
})

apiClient.interceptors.request.use((config) => {
  const token = getStoredToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

type UnauthorizedListener = () => void
let onUnauthorized: UnauthorizedListener | null = null

/** Registered by AuthProvider so an expired/revoked token signs the user out. */
export function setUnauthorizedListener(listener: UnauthorizedListener | null): void {
  onUnauthorized = listener
}

apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    const isLogin = error.config?.url?.endsWith('/login')
    if (error.response?.status === 401 && !isLogin) {
      onUnauthorized?.()
    }
    return Promise.reject(error)
  },
)
