import { RefreshCw } from 'lucide-react'
import { useCallback, useMemo } from 'react'
import { useSearchParams } from 'react-router-dom'
import { Card } from '../components/Card'
import { EmptyState, ErrorState, LoadingState, Spinner } from '../components/Feedback'
import { PageHeader } from '../components/PageHeader'
import { Pagination } from '../components/Pagination'
import { ReportFiltersBar } from '../components/ReportFiltersBar'
import { ReportsTable } from '../components/ReportsTable'
import { useApiQuery } from '../hooks/useApiQuery'
import { useCurrentUser } from '../hooks/useAuth'
import { useCategories } from '../hooks/useCategories'
import { useKnownOperators } from '../hooks/useKnownOperators'
import { fetchReports } from '../services/reportService'
import type { ReportFilters, SortColumn } from '../types/api'
import { countActiveFilters, filtersFromSearchParams, filtersToSearchParams, updateFilters } from '../utils/reportFilters'

export function ReportsPage() {
  const user = useCurrentUser()
  const [searchParams, setSearchParams] = useSearchParams()
  const queryString = searchParams.toString()
  // Filters live in the URL, so they survive reloads, back/forward and links.
  const filters = useMemo(() => filtersFromSearchParams(new URLSearchParams(queryString)), [queryString])

  const fetcher = useCallback(() => fetchReports(filters), [filters])
  const reports = useApiQuery(fetcher)
  const { categories } = useCategories()
  const { operators } = useKnownOperators()

  const setFilters = useCallback(
    (next: ReportFilters) => setSearchParams(filtersToSearchParams(next)),
    [setSearchParams],
  )
  const changeFilters = useCallback(
    (patch: Partial<ReportFilters>) => setFilters(updateFilters(filters, patch)),
    [filters, setFilters],
  )

  const sort = filters.sort ?? 'created_at'
  const direction = filters.direction ?? 'desc'
  const onSort = (column: SortColumn) =>
    changeFilters({ sort: column, direction: column === sort && direction === 'desc' ? 'asc' : 'desc' })

  const isMine = filters.assigned_to === 'me'
  const page = reports.data
  const hasFilters = countActiveFilters(filters) > 0

  return (
    <>
      <PageHeader
        title={isMine ? 'My assignments' : 'Reports'}
        description={
          isMine
            ? 'Reports currently assigned to you.'
            : 'Search the report queue by status, category, assignee and date.'
        }
        actions={
          <button type="button" onClick={reports.reload} className="btn-secondary" disabled={reports.loading}>
            {reports.loading ? <Spinner className="size-4" /> : <RefreshCw className="size-4" aria-hidden />}
            Refresh
          </button>
        }
      />

      <ReportFiltersBar
        filters={filters}
        onChange={changeFilters}
        onReset={() => setFilters({})}
        categories={categories}
        operators={operators}
        currentUser={user}
      />

      <Card className="mt-4" bodyClassName="">
        {reports.error ? (
          <ErrorState error={reports.error} onRetry={reports.reload} />
        ) : !page ? (
          <LoadingState label="Loading reports…" />
        ) : page.data.length === 0 ? (
          <EmptyState
            title="No reports found"
            description={hasFilters ? 'No reports match the current filters.' : 'There are no reports yet.'}
            action={
              hasFilters && (
                <button type="button" className="btn-secondary" onClick={() => setFilters({})}>
                  Clear filters
                </button>
              )
            }
          />
        ) : (
          <div className={`transition-opacity ${reports.loading ? 'opacity-60' : ''}`} aria-busy={reports.loading}>
            <ReportsTable reports={page.data} sorting={{ sort, direction, onSort }} />
            <Pagination meta={page.meta} onPageChange={(next) => changeFilters({ page: next })} />
          </div>
        )}
      </Card>
    </>
  )
}
