// Types mirror the Laravel API resources in app/Http/Resources and the
// controllers in app/Http/Controllers. Keep them in sync with the backend.

export const REPORT_STATUSES = ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED'] as const
export type ReportStatus = (typeof REPORT_STATUSES)[number]

export type UserRole = 'CITIZEN' | 'OPERATOR' | 'ADMIN'

/** App\Http\Resources\UserResource */
export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  created_at: string
}

/** App\Http\Resources\CategoryResource */
export interface Category {
  id: number
  name: string
  description: string | null
}

/**
 * App\Http\Resources\ReportResource.
 *
 * `category` and `assigned_operator` are only present when the backend eager
 * loads them (GET /api/reports); GET /api/reports/{id} omits both keys.
 */
export interface Report {
  id: string
  category_id: number
  category?: Category | null
  description: string
  /** PostGIS geography as hex-encoded EWKB, e.g. "0101000020E6100000…". */
  location: string
  tracking_number: string
  reporter_email: string
  created_at: string
  updated_at: string
  status: ReportStatus
  assigned_operator_id: number | null
  assigned_operator?: User | null
  assigned_at: string | null
  started_at: string | null
  resolved_at: string | null
  version: number
}

/** One row of GET /api/reports/{id}/history (plain array, no `data` wrapper). */
export interface ReportHistoryEntry {
  id: number
  from_status: ReportStatus | null
  to_status: ReportStatus
  from_operator_id: number | null
  to_operator_id: number | null
  actor_id: number | null
  reason: string | null
  created_at: string
}

/** GET /api/dashboard/summary */
export interface DashboardSummary {
  total: number
  by_status: Record<ReportStatus, number>
}

/** POST /api/login */
export interface LoginResponse {
  token: string
  token_type: 'Bearer'
  user: User
}

export interface Resource<T> {
  data: T
}

/** Laravel paginated API resource collection. */
export interface Paginated<T> {
  data: T[]
  links: {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
  }
  meta: {
    current_page: number
    from: number | null
    last_page: number
    path: string
    per_page: number
    to: number | null
    total: number
  }
}

export const SORTABLE_COLUMNS = ['created_at', 'updated_at', 'assigned_at', 'resolved_at', 'status'] as const
export type SortColumn = (typeof SORTABLE_COLUMNS)[number]
export type SortDirection = 'asc' | 'desc'

/** Query parameters accepted by GET /api/reports (App\Http\Requests\ListReportsRequest). */
export interface ReportFilters {
  status?: ReportStatus
  category_id?: number
  /** `me` or a numeric user id. */
  assigned_to?: string
  /** YYYY-MM-DD, inclusive. */
  created_from?: string
  /** YYYY-MM-DD, inclusive. */
  created_to?: string
  sort?: SortColumn
  direction?: SortDirection
  per_page?: number
  page?: number
}
