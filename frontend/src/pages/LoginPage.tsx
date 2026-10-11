import { AlertCircle, Eye, EyeOff, LogIn } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { Navigate, useLocation, useNavigate } from 'react-router-dom'
import { Spinner } from '../components/Feedback'
import { Logo } from '../components/Logo'
import { DashboardAccessError } from '../contexts/authContext'
import { useAuth } from '../hooks/useAuth'
import { toApiError } from '../services/errors'

export function LoginPage() {
  const { status, login, sessionExpired } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const from = (location.state as { from?: string } | null)?.from ?? '/'

  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({})

  if (status === 'authenticated') {
    return <Navigate to={from} replace />
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setSubmitting(true)
    setError(null)
    setFieldErrors({})
    try {
      await login(email.trim(), password)
      navigate(from, { replace: true })
    } catch (caught) {
      if (caught instanceof DashboardAccessError) {
        setError(caught.message)
      } else {
        const apiError = toApiError(caught)
        // Laravel reports wrong credentials as a 422 on `email`.
        if (apiError.status === 422 && apiError.fieldErrors.email && !apiError.fieldErrors.password) {
          setError(apiError.fieldErrors.email[0] ?? apiError.message)
        } else {
          setError(apiError.message)
          setFieldErrors(apiError.fieldErrors)
        }
      }
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="flex min-h-svh bg-slate-50">
      <div className="relative hidden w-[44%] flex-col justify-between overflow-hidden bg-slate-900 p-12 lg:flex">
        <div
          className="pointer-events-none absolute inset-0 opacity-40"
          style={{
            backgroundImage:
              'radial-gradient(circle at 20% 20%, rgba(13,148,136,0.45), transparent 45%), radial-gradient(circle at 80% 80%, rgba(37,99,235,0.35), transparent 45%)',
          }}
          aria-hidden
        />
        <div className="relative">
          <Logo inverted />
        </div>
        <div className="relative max-w-md">
          <h2 className="text-3xl font-semibold leading-tight tracking-tight text-white">
            Turn citizen reports into resolved civic issues.
          </h2>
          <p className="mt-4 text-base leading-relaxed text-slate-300">
            Triage incoming reports, assign field operators and track every issue from first report to resolution,
            with a full audit trail.
          </p>
        </div>
        <p className="relative text-xs text-slate-500">CivicPulse operator console</p>
      </div>

      <div className="flex flex-1 items-center justify-center p-6">
        <div className="w-full max-w-sm">
          <div className="mb-8 lg:hidden">
            <Logo />
          </div>
          <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Sign in</h1>
          <p className="mt-1 text-sm text-slate-500">Operator and administrator access only.</p>

          {sessionExpired && !error && (
            <div className="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="status">
              Your session has ended. Please sign in again.
            </div>
          )}

          {error && (
            <div className="mt-6 flex gap-2.5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
              <AlertCircle className="mt-0.5 size-4 shrink-0" aria-hidden />
              <span>{error}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="mt-6 space-y-5" noValidate>
            <div>
              <label htmlFor="email" className="label">Email address</label>
              <input
                id="email"
                type="email"
                autoComplete="username"
                required
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                className="input mt-1.5"
                placeholder="operator1@civicpulse.test"
                aria-invalid={Boolean(fieldErrors.email)}
              />
              {fieldErrors.email && <p className="field-error">{fieldErrors.email[0]}</p>}
            </div>
            <div>
              <label htmlFor="password" className="label">Password</label>
              <div className="relative mt-1.5">
                <input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  autoComplete="current-password"
                  required
                  value={password}
                  onChange={(event) => setPassword(event.target.value)}
                  className="input pr-10"
                  aria-invalid={Boolean(fieldErrors.password)}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((value) => !value)}
                  className="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
                  aria-label={showPassword ? 'Hide password' : 'Show password'}
                >
                  {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                </button>
              </div>
              {fieldErrors.password && <p className="field-error">{fieldErrors.password[0]}</p>}
            </div>
            <button type="submit" className="btn-primary w-full justify-center py-2.5" disabled={submitting || !email || !password}>
              {submitting ? <Spinner className="size-4 text-white" /> : <LogIn className="size-4" aria-hidden />}
              {submitting ? 'Signing in…' : 'Sign in'}
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}
