import {
  REPORT_STATUSES,
  SORTABLE_COLUMNS,
  type ReportFilters,
  type ReportStatus,
  type SortColumn,
  type SortDirection,
} from '../types/api'

export const DEFAULT_PER_PAGE = 15
export const PER_PAGE_OPTIONS = [10, 15, 25, 50, 100] as const

const DATE_PATTERN = /^\d{4}-\d{2}-\d{2}$/
const ASSIGNED_TO_PATTERN = /^(me|[1-9]\d*)$/

/**
 * Reads report filters from the URL query string. Values the backend would
 * reject are dropped, so a hand-edited URL degrades to "no filter" rather
 * than a 422.
 */
export function filtersFromSearchParams(params: URLSearchParams): ReportFilters {
  const filters: ReportFilters = {}

  const status = params.get('status')
  if (status && (REPORT_STATUSES as readonly string[]).includes(status)) {
    filters.status = status as ReportStatus
  }

  const categoryId = positiveInt(params.get('category_id'))
  if (categoryId) filters.category_id = categoryId

  const assignedTo = params.get('assigned_to')
  if (assignedTo && ASSIGNED_TO_PATTERN.test(assignedTo)) filters.assigned_to = assignedTo

  const from = params.get('created_from')
  if (from && DATE_PATTERN.test(from)) filters.created_from = from

  const to = params.get('created_to')
  if (to && DATE_PATTERN.test(to)) filters.created_to = to

  const sort = params.get('sort')
  if (sort && (SORTABLE_COLUMNS as readonly string[]).includes(sort)) filters.sort = sort as SortColumn

  const direction = params.get('direction')
  if (direction === 'asc' || direction === 'desc') filters.direction = direction as SortDirection

  const perPage = positiveInt(params.get('per_page'))
  if (perPage && perPage <= 100) filters.per_page = perPage

  const page = positiveInt(params.get('page'))
  if (page) filters.page = page

  return filters
}

/** Serialises filters back to a query string, omitting empty values. */
export function filtersToSearchParams(filters: ReportFilters): URLSearchParams {
  const params = new URLSearchParams()
  for (const [key, value] of Object.entries(filters)) {
    if (value !== undefined && value !== null && value !== '') {
      params.set(key, String(value))
    }
  }
  return params
}

/** Applies a filter change and resets to page 1 unless the page itself changed. */
export function updateFilters(current: ReportFilters, patch: Partial<ReportFilters>): ReportFilters {
  const next: ReportFilters = { ...current, ...patch }
  if (!('page' in patch)) {
    delete next.page
  }
  for (const key of Object.keys(next) as (keyof ReportFilters)[]) {
    if (next[key] === undefined || next[key] === '') delete next[key]
  }
  return next
}

export function countActiveFilters(filters: ReportFilters): number {
  return (['status', 'category_id', 'assigned_to', 'created_from', 'created_to'] as const).filter(
    (key) => filters[key] !== undefined,
  ).length
}

function positiveInt(value: string | null): number | undefined {
  if (!value || !/^[1-9]\d*$/.test(value)) return undefined
  return Number(value)
}
