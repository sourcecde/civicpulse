import { useContext } from 'react'
import { AuthContext, type AuthContextValue } from '../contexts/authContext'

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth must be used inside <AuthProvider>')
  }
  return context
}

/** For components rendered only behind ProtectedRoute. */
export function useCurrentUser() {
  const { user } = useAuth()
  if (!user) {
    throw new Error('useCurrentUser requires an authenticated user')
  }
  return user
}
