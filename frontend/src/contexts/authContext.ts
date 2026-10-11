import { createContext } from 'react'
import type { User } from '../types/api'

export type AuthStatus = 'loading' | 'authenticated' | 'anonymous'

export interface AuthContextValue {
  status: AuthStatus
  user: User | null
  /** Set when the session ended because the API rejected the token. */
  sessionExpired: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
}

/** Roles allowed into the operator dashboard (mirrors ReportPolicy::viewAny). */
export const DASHBOARD_ROLES: User['role'][] = ['OPERATOR', 'ADMIN']

/** Thrown by login() when valid credentials belong to a role without dashboard access. */
export class DashboardAccessError extends Error {}

export const AuthContext = createContext<AuthContextValue | null>(null)
