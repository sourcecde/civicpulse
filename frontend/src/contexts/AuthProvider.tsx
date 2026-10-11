import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react'
import { apiClient, setUnauthorizedListener } from '../services/apiClient'
import * as authService from '../services/authService'
import { toApiError } from '../services/errors'
import { clearStoredToken, getStoredToken, storeToken } from '../services/tokenStorage'
import type { User } from '../types/api'
import { AuthContext, DASHBOARD_ROLES, DashboardAccessError, type AuthStatus } from './authContext'

export function AuthProvider({ children }: { children: ReactNode }) {
  const [status, setStatus] = useState<AuthStatus>(() => (getStoredToken() ? 'loading' : 'anonymous'))
  const [user, setUser] = useState<User | null>(null)
  const [sessionExpired, setSessionExpired] = useState(false)

  const endSession = useCallback((expired: boolean) => {
    clearStoredToken()
    setUser(null)
    setStatus('anonymous')
    setSessionExpired(expired)
  }, [])

  // Any 401 from the API (expired or revoked token) signs the user out.
  useEffect(() => {
    setUnauthorizedListener(() => endSession(true))
    return () => setUnauthorizedListener(null)
  }, [endSession])

  // Restore the session from a stored token on first load.
  useEffect(() => {
    if (!getStoredToken()) return

    let cancelled = false
    authService
      .fetchCurrentUser()
      .then((current) => {
        if (cancelled) return
        setUser(current)
        setStatus('authenticated')
      })
      .catch((error: unknown) => {
        // A 401 means the stored token expired or was revoked; anything else
        // (e.g. API down) still leaves us unable to verify the token.
        if (!cancelled) endSession(toApiError(error).status === 401)
      })

    return () => {
      cancelled = true
    }
  }, [endSession])

  const login = useCallback(async (email: string, password: string) => {
    const result = await authService.login(email, password)

    if (!DASHBOARD_ROLES.includes(result.user.role)) {
      // Revoke the token we were just issued; citizens cannot use the dashboard.
      await apiClient
        .post('/logout', null, { headers: { Authorization: `Bearer ${result.token}` } })
        .catch(() => undefined)
      throw new DashboardAccessError(
        'This dashboard is only available to operators and administrators.',
      )
    }

    storeToken(result.token)
    setUser(result.user)
    setSessionExpired(false)
    setStatus('authenticated')
  }, [])

  const logout = useCallback(async () => {
    try {
      await authService.logout()
    } catch {
      // The token is discarded locally regardless.
    }
    endSession(false)
  }, [endSession])

  const value = useMemo(
    () => ({ status, user, sessionExpired, login, logout }),
    [status, user, sessionExpired, login, logout],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
