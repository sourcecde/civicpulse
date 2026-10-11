import { ArrowRight, UserPlus } from 'lucide-react'
import type { ReportHistoryEntry } from '../types/api'
import { formatDateTime, formatRelative, STATUS_COLORS } from '../utils/format'
import { StatusBadge } from './StatusBadge'

export function HistoryTimeline({
  entries,
  nameFor,
}: {
  entries: ReportHistoryEntry[]
  nameFor: (id: number | null) => string | null
}) {
  return (
    <ol className="relative space-y-6">
      {entries.map((entry, index) => {
        const isAssignment = entry.to_status === 'ASSIGNED' && entry.to_operator_id !== entry.from_operator_id
        const last = index === entries.length - 1
        return (
          <li key={entry.id} className="relative flex gap-4">
            {!last && <span className="absolute left-[15px] top-8 h-[calc(100%-0.5rem)] w-px bg-slate-200" aria-hidden />}
            <span
              className="relative flex size-8 shrink-0 items-center justify-center rounded-full ring-4 ring-white"
              style={{ backgroundColor: `${STATUS_COLORS[entry.to_status]}1f`, color: STATUS_COLORS[entry.to_status] }}
            >
              {isAssignment ? <UserPlus className="size-4" aria-hidden /> : <ArrowRight className="size-4" aria-hidden />}
            </span>
            <div className="min-w-0 flex-1 pt-0.5">
              <div className="flex flex-wrap items-center gap-1.5 text-sm">
                {entry.from_status && (
                  <>
                    <StatusBadge status={entry.from_status} />
                    <ArrowRight className="size-3.5 text-slate-400" aria-label="to" />
                  </>
                )}
                <StatusBadge status={entry.to_status} />
              </div>
              <p className="mt-1.5 text-sm text-slate-700">
                {isAssignment ? (
                  <>
                    Assigned to <span className="font-medium">{nameFor(entry.to_operator_id)}</span>
                  </>
                ) : (
                  'Status updated'
                )}{' '}
                by <span className="font-medium">{nameFor(entry.actor_id) ?? 'System'}</span>
              </p>
              {entry.reason && (
                <p className="mt-1.5 rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-600">“{entry.reason}”</p>
              )}
              <time className="mt-1 block text-xs text-slate-500" dateTime={entry.created_at} title={formatDateTime(entry.created_at)}>
                {formatDateTime(entry.created_at)} · {formatRelative(entry.created_at)}
              </time>
            </div>
          </li>
        )
      })}
    </ol>
  )
}
