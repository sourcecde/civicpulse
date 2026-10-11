import type { LucideIcon } from 'lucide-react'
import { Link } from 'react-router-dom'

export function StatCard({
  label,
  value,
  icon: Icon,
  accent,
  to,
  hint,
  loading,
  className = '',
}: {
  label: string
  value: number | null
  icon: LucideIcon
  /** Hex colour for the icon chip. */
  accent: string
  to: string
  hint?: string
  loading?: boolean
  className?: string
}) {
  return (
    <Link
      to={to}
      className={`group rounded-xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5 transition hover:border-slate-300 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-teal-600 ${className}`}
    >
      <div className="flex items-center justify-between">
        <p className="text-sm font-medium text-slate-500">{label}</p>
        <span
          className="flex size-8 items-center justify-center rounded-lg"
          style={{ backgroundColor: `${accent}14`, color: accent }}
        >
          <Icon className="size-4" aria-hidden />
        </span>
      </div>
      <p className="mt-3 text-2xl font-semibold sm:text-3xl tabular-nums tracking-tight text-slate-900">
        {value === null ? (
          <span className={`inline-block h-8 w-16 rounded bg-slate-100 ${loading ? 'animate-pulse' : ''}`} />
        ) : (
          value.toLocaleString()
        )}
      </p>
      {hint && <p className="mt-1 text-xs text-slate-500 group-hover:text-slate-700">{hint}</p>}
    </Link>
  )
}
