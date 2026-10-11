import type { ReportStatus } from '../types/api'
import { STATUS_LABELS } from '../utils/format'

const STYLES: Record<ReportStatus, string> = {
  OPEN: 'bg-blue-50 text-blue-700 ring-blue-600/20',
  ASSIGNED: 'bg-amber-50 text-amber-800 ring-amber-600/20',
  IN_PROGRESS: 'bg-violet-50 text-violet-700 ring-violet-600/20',
  RESOLVED: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
}

const DOTS: Record<ReportStatus, string> = {
  OPEN: 'bg-blue-500',
  ASSIGNED: 'bg-amber-500',
  IN_PROGRESS: 'bg-violet-500',
  RESOLVED: 'bg-emerald-500',
}

export function StatusBadge({ status }: { status: ReportStatus }) {
  return (
    <span
      className={`inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset ${STYLES[status]}`}
    >
      <span className={`size-1.5 rounded-full ${DOTS[status]}`} aria-hidden />
      {STATUS_LABELS[status]}
    </span>
  )
}
