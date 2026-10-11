import type { LoginResponse, Resource, User } from '../types/api'
import { apiClient } from './apiClient'

const DEVICE_NAME = 'civicpulse-dashboard'

export async function login(email: string, password: string): Promise<LoginResponse> {
  const { data } = await apiClient.post<LoginResponse>('/login', {
    email,
    password,
    device_name: DEVICE_NAME,
  })
  return data
}

export async function fetchCurrentUser(): Promise<User> {
  const { data } = await apiClient.get<Resource<User>>('/me')
  return data.data
}

/** Revokes only the token used for this request (POST /api/logout → 204). */
export async function logout(): Promise<void> {
  await apiClient.post('/logout')
}
