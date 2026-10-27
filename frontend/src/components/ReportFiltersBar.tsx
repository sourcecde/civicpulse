import { FilterX } from 'lucide-react'
import { useState } from 'react'
import type { Category, ReportFilters, ReportStatus, SortColumn, SortDirection, User, Operator } from '../types/api'
import { REPORT_STATUSES } from '../types/api'
import { STATUS_LABELS } from '../utils/format'
import { countActiveFilters, PER_PAGE_OPTIONS, DEFAULT_PER_PAGE } from '../utils/reportFilters'

const SORT_OPTIONS: { value: `${SortColumn}:${SortDirection}`; label: string }[] = [
  { value: 'created_at:desc', label: 'Newest first' },
  { value: 'created_at:asc', label: 'Oldest first' },
  { value: 'updated_at:desc', label: 'Recently updated' },
  { value: 'assigned_at:desc', label: 'Recently assigned' },
  { value: 'resolved_at:desc', label: 'Recently resolved' },
  { value: 'status:asc', label: 'Status (A–Z)' },
  { value: 'status:desc', label: 'Status (Z–A)' },
]

const CUSTOM = '__custom'

export function ReportFiltersBar({
  filters,
  onChange,
  onReset,
  categories,
  operators,
  currentUser,
}: {
  filters: ReportFilters
  onChange: (patch: Partial<ReportFilters>) => void
  onReset: () => void
  categories: Category[]
  operators: Operator[]
  currentUser: User
}) {
  const active = countActiveFilters(filters)
  const sortValue = `${filters.sort ?? 'created_at'}:${filters.direction ?? 'desc'}`
  const assignedTo = filters.assigned_to ?? ''
  const knownAssignee =
    assignedTo === '' || assignedTo === 'me' || operators.some((op) => String(op.id) === assignedTo)
  const [customMode, setCustomMode] = useState(false)
  const [customId, setCustomId] = useState('')
  const showCustom = customMode || !knownAssignee

  return (
    <div className="space-y-4">
      {/* Status tabs */}
      <div className="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1" role="tablist" aria-label="Filter by status">
        {([undefined, ...REPORT_STATUSES] as (ReportStatus | undefined)[]).map((status) => {
          const selected = filters.status === status
          return (
            <button
              key={status ?? 'all'}
              type="button"
              role="tab"
              aria-selected={selected}
              onClick={() => onChange({ status })}
              className={`whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium transition ${
                selected ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-white hover:text-slate-900'
              }`}
            >
              {status ? STATUS_LABELS[status] : 'All statuses'}
            </button>
          )
        })}
      </div>

      <div className="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs sm:grid-cols-2 lg:grid-cols-6">
        <div className="lg:col-span-1">
          <label htmlFor="filter-category" className="label">Category</label>
          <select
            id="filter-category"
            className="input mt-1.5"
            value={filters.category_id ?? ''}
            onChange={(event) => onChange({ category_id: event.target.value ? Number(event.target.value) : undefined })}
          >
            <option value="">All categories</option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>{category.name}</option>
            ))}
            {filters.category_id && !categories.some((c) => c.id === filters.category_id) && (
              <option value={filters.category_id}>Category #{filters.category_id}</option>
            )}
          </select>
        </div>

        <div className="lg:col-span-1">
          <label htmlFor="filter-assignee" className="label">Assigned to</label>
          <select
            id="filter-assignee"
            className="input mt-1.5"
            value={showCustom ? CUSTOM : assignedTo}
            onChange={(event) => {
              if (event.target.value === CUSTOM) {
                setCustomMode(true)
                return
              }
              setCustomMode(false)
              onChange({ assigned_to: event.target.value || undefined })
            }}
          >
            <option value="">Anyone</option>
            <option value="me">Me ({currentUser.name})</option>
            {operators
              .filter((op) => op.id !== currentUser.id)
              .map((op) => (
                <option key={op.id} value={op.id}>{op.name}</option>
              ))}
            <option value={CUSTOM}>{knownAssignee ? 'Other user ID…' : `User #${assignedTo}`}</option>
          </select>
          {showCustom && (
            <form
              className="mt-2 flex gap-2"
              onSubmit={(event) => {
                event.preventDefault()
                if (/^[1-9]\d*$/.test(customId)) {
                  onChange({ assigned_to: customId })
                  setCustomMode(false)
                }
              }}
            >
              <input
                type="number"
                min={1}
                inputMode="numeric"
                className="input"
                placeholder={knownAssignee ? 'User ID' : assignedTo}
                value={customId}
                onChange={(event) => setCustomId(event.target.value)}
                aria-label="Operator user ID"
              />
              <button type="submit" className="btn-secondary" disabled={!/^[1-9]\d*$/.test(customId)}>Apply</button>
            </form>
          )}
        </div>

        <div className="lg:col-span-1">
          <label htmlFor="filter-from" className="label">Created from</label>
          <DateInput
            id="filter-from"
            value={filters.created_from}
            max={filters.created_to}
            onChange={(value) => onChange({ created_from: value })}
          />
        </div>

        <div className="lg:col-span-1">
          <label htmlFor="filter-to" className="label">Created to</label>
          <DateInput
            id="filter-to"
            value={filters.created_to}
            min={filters.created_from}
            onChange={(value) => onChange({ created_to: value })}
          />
        </div>

        <div className="lg:col-span-1">
          <label htmlFor="filter-sort" className="label">Sort by</label>
          <select
            id="filter-sort"
            className="input mt-1.5"
            value={sortValue}
            onChange={(event) => {
              const [sort, direction] = event.target.value.split(':') as [SortColumn, SortDirection]
              onChange({ sort, direction })
            }}
          >
            {SORT_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>{option.label}</option>
            ))}
            {!SORT_OPTIONS.some((o) => o.value === sortValue) && <option value={sortValue}>{sortValue.replace(':', ' ')}</option>}
          </select>
        </div>

        <div className="flex items-end gap-2 lg:col-span-1">
          <div className="flex-1">
            <label htmlFor="filter-per-page" className="label">Per page</label>
            <select
              id="filter-per-page"
              className="input mt-1.5"
              value={filters.per_page ?? DEFAULT_PER_PAGE}
              onChange={(event) => onChange({ per_page: Number(event.target.value) })}
            >
              {PER_PAGE_OPTIONS.map((size) => (
                <option key={size} value={size}>{size}</option>
              ))}
              {filters.per_page && !(PER_PAGE_OPTIONS as readonly number[]).includes(filters.per_page) && (
                <option value={filters.per_page}>{filters.per_page}</option>
              )}
            </select>
          </div>
          <button
            type="button"
            onClick={() => {
              setCustomMode(false)
              setCustomId('')
              onReset()
            }}
            className="btn-secondary h-[38px]"
            disabled={active === 0 && !filters.sort && !filters.per_page}
            title="Clear all filters"
          >
            <FilterX className="size-4" aria-hidden />
            <span className="sr-only sm:not-sr-only lg:sr-only xl:not-sr-only">Clear</span>
          </button>
        </div>
      </div>
    </div>
  )
}

function DateInput({
  id,
  value,
  min,
  max,
  onChange,
}: {
  id: string
  value?: string
  min?: string
  max?: string
  onChange: (value: string | undefined) => void
}) {
  return (
    <input
      id={id}
      type="date"
      className="input mt-1.5"
      value={value ?? ''}
      min={min}
      max={max}
      onChange={(event) => onChange(event.target.value || undefined)}
    />
  )
}
