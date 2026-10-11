import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { DASHBOARD_ROLES } from '../contexts/authContext'
import { useAuth } from '../hooks/useAuth'
import { LoadingState } from './Feedback'

export function ProtectedRoute() {
  const { status, user, logout } = useAuth()
  const location = useLocation()

  if (status === 'loading') {
    return (
      <div className="flex min-h-svh items-center justify-center bg-slate-50">
        <LoadingState label="Checking your session…" />
      </div>
    )
  }

  if (status === 'anonymous' || !user) {
    return <Navigate to="/login" replace state={{ from: `${location.pathname}${location.search}` }} />
  }

  // A citizen token restored from storage: the backend would answer 403 anyway.
  if (!DASHBOARD_ROLES.includes(user.role)) {
    return (
      <div className="flex min-h-svh items-center justify-center bg-slate-50 p-6">
        <div className="max-w-sm rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
          <h1 className="text-base font-semibold text-slate-900">Access restricted</h1>
          <p className="mt-2 text-sm text-slate-600">
            The operator dashboard is only available to operators and administrators.
          </p>
          <button type="button" className="btn-primary mt-5" onClick={() => void logout()}>
            Sign out
          </button>
        </div>
      </div>
    )
  }

  return <Outlet />
}
