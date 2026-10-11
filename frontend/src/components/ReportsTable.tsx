import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react'
import { Link, useLocation } from 'react-router-dom'
import type { Report, SortColumn, SortDirection } from '../types/api'
import { formatDateTime, formatRelative } from '../utils/format'
import { StatusBadge } from './StatusBadge'

interface SortState {
  sort: SortColumn
  direction: SortDirection
  onSort: (column: SortColumn) => void
}

export function ReportsTable({
  reports,
  sorting,
  compact = false,
}: {
  reports: Report[]
  sorting?: SortState
  compact?: boolean
}) {
  const location = useLocation()
  // Remember where we came from so "Back" on the detail page keeps the filters.
  const from = `${location.pathname}${location.search}`

  return (
    <div className="overflow-x-auto">
      <table className="w-full min-w-[720px] text-left text-sm">
        <thead className="bg-slate-50/80 text-xs font-medium uppercase tracking-wide text-slate-500">
          <tr>
            <th scope="col" className="px-5 py-3">Report</th>
            <th scope="col" className="px-3 py-3">Category</th>
            <SortableHeader label="Status" column="status" sorting={sorting} />
            <th scope="col" className="px-3 py-3">Assigned to</th>
            <SortableHeader label="Created" column="created_at" sorting={sorting} />
            {!compact && <SortableHeader label="Updated" column="updated_at" sorting={sorting} />}
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {reports.map((report) => (
            <tr key={report.id} className="group relative transition hover:bg-slate-50">
              <td className="max-w-sm px-5 py-3.5">
                <Link
                  to={`/reports/${report.id}`}
                  state={{ from }}
                  className="font-medium text-slate-900 after:absolute after:inset-0 group-hover:text-teal-700 focus-visible:outline-none"
                >
                  {report.tracking_number}
                </Link>
                <p className="mt-0.5 truncate text-slate-500">{report.description}</p>
                <span className="mt-1.5 inline-block sm:hidden">
                  <StatusBadge status={report.status} />
                </span>
              </td>
              <td className="px-3 py-3.5 text-slate-700">
                {report.category?.name ?? <span className="text-slate-400">#{report.category_id}</span>}
              </td>
              <td className="px-3 py-3.5">
                <StatusBadge status={report.status} />
              </td>
              <td className="px-3 py-3.5 text-slate-700">
                {report.assigned_operator?.name ??
                  (report.assigned_operator_id ? `User #${report.assigned_operator_id}` : (
                    <span className="text-slate-400">Unassigned</span>
                  ))}
              </td>
              <td className="whitespace-nowrap px-3 py-3.5 text-slate-600" title={formatDateTime(report.created_at)}>
                {formatRelative(report.created_at)}
              </td>
              {!compact && (
                <td className="whitespace-nowrap px-3 py-3.5 text-slate-600" title={formatDateTime(report.updated_at)}>
                  {formatRelative(report.updated_at)}
                </td>
              )}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

function SortableHeader({ label, column, sorting }: { label: string; column: SortColumn; sorting?: SortState }) {
  if (!sorting) {
    return <th scope="col" className="px-3 py-3">{label}</th>
  }

  const active = sorting.sort === column
  const Icon = !active ? ArrowUpDown : sorting.direction === 'asc' ? ArrowUp : ArrowDown

  return (
    <th
      scope="col"
      className="px-3 py-3"
      aria-sort={active ? (sorting.direction === 'asc' ? 'ascending' : 'descending') : 'none'}
    >
      <button
        type="button"
        onClick={() => sorting.onSort(column)}
        className={`inline-flex items-center gap-1 uppercase tracking-wide hover:text-slate-900 ${active ? 'text-slate-900' : ''}`}
      >
        {label}
        <Icon className="size-3.5" aria-hidden />
      </button>
    </th>
  )
}
