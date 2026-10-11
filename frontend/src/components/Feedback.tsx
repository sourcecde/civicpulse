import { AlertTriangle, Inbox, Loader2, RotateCw } from 'lucide-react'
import type { ReactNode } from 'react'
import type { ApiError } from '../services/errors'

export function Spinner({ className = 'size-5' }: { className?: string }) {
  return <Loader2 className={`animate-spin text-slate-400 ${className}`} aria-hidden />
}

export function LoadingState({ label = 'Loading…' }: { label?: string }) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 py-16 text-sm text-slate-500" role="status">
      <Spinner className="size-6" />
      {label}
    </div>
  )
}

export function ErrorState({ error, onRetry }: { error: ApiError; onRetry?: () => void }) {
  const fieldMessages = Object.values(error.fieldErrors).flat()
  return (
    <div className="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center" role="alert">
      <span className="flex size-10 items-center justify-center rounded-full bg-red-50">
        <AlertTriangle className="size-5 text-red-600" aria-hidden />
      </span>
      <div>
        <p className="text-sm font-medium text-slate-900">Something went wrong</p>
        <p className="mt-1 max-w-md text-sm text-slate-600">{error.message}</p>
        {fieldMessages.length > 1 && (
          <ul className="mt-2 space-y-0.5 text-sm text-slate-600">
            {fieldMessages.slice(1).map((message) => (
              <li key={message}>{message}</li>
            ))}
          </ul>
        )}
      </div>
      {onRetry && (
        <button type="button" onClick={onRetry} className="btn-secondary">
          <RotateCw className="size-4" aria-hidden />
          Try again
        </button>
      )}
    </div>
  )
}

export function EmptyState({ title, description, action }: { title: string; description?: string; action?: ReactNode }) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
      <span className="flex size-10 items-center justify-center rounded-full bg-slate-100">
        <Inbox className="size-5 text-slate-500" aria-hidden />
      </span>
      <div>
        <p className="text-sm font-medium text-slate-900">{title}</p>
        {description && <p className="mt-1 max-w-md text-sm text-slate-600">{description}</p>}
      </div>
      {action}
    </div>
  )
}
