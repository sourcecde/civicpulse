import { ArrowRight, CheckCircle2, CircleDot, ClipboardList, Hammer, RefreshCw, UserCheck } from 'lucide-react'
import { useCallback, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { Card } from '../components/Card'
import { EmptyState, ErrorState, LoadingState, Spinner } from '../components/Feedback'
import { PageHeader } from '../components/PageHeader'
import { ReportsTable } from '../components/ReportsTable'
import { StatCard } from '../components/StatCard'
import { StatusChart } from '../components/StatusChart'
import { useApiQuery } from '../hooks/useApiQuery'
import { useCurrentUser } from '../hooks/useAuth'
import { fetchDashboardSummary, fetchReports } from '../services/reportService'
import { STATUS_COLORS } from '../utils/format'

const RECENT_LIMIT = 6
const fetchRecentReports = () => fetchReports({ sort: 'created_at', direction: 'desc', per_page: RECENT_LIMIT })

export function DashboardPage() {
  const user = useCurrentUser()
  const summary = useApiQuery(fetchDashboardSummary)
  const recent = useApiQuery(fetchRecentReports)
  const [refreshedAt, setRefreshedAt] = useState(() => new Date())

  const refreshing = summary.loading || recent.loading
  const refresh = useCallback(() => {
    summary.reload()
    recent.reload()
    setRefreshedAt(new Date())
  }, [summary, recent])

  const counts = summary.data
  const cards = useMemo(
    () => [
      { label: 'Total reports', value: counts?.total ?? null, icon: ClipboardList, accent: '#0f766e', to: '/reports', hint: 'All reports' },
      { label: 'Open', value: counts?.by_status.OPEN ?? null, icon: CircleDot, accent: STATUS_COLORS.OPEN, to: '/reports?status=OPEN', hint: 'Awaiting assignment' },
      { label: 'Assigned', value: counts?.by_status.ASSIGNED ?? null, icon: UserCheck, accent: STATUS_COLORS.ASSIGNED, to: '/reports?status=ASSIGNED', hint: 'Waiting for work to start' },
      { label: 'In progress', value: counts?.by_status.IN_PROGRESS ?? null, icon: Hammer, accent: STATUS_COLORS.IN_PROGRESS, to: '/reports?status=IN_PROGRESS', hint: 'Work under way' },
      { label: 'Resolved', value: counts?.by_status.RESOLVED ?? null, icon: CheckCircle2, accent: STATUS_COLORS.RESOLVED, to: '/reports?status=RESOLVED', hint: 'Marked resolved' },
    ],
    [counts],
  )

  return (
    <>
      <PageHeader
        title={`Welcome back, ${user.name}`}
        description="Live overview of civic reports across all categories."
        actions={
          <>
            <span className="hidden text-xs text-slate-500 sm:inline">
              Updated {refreshedAt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
            </span>
            <button type="button" onClick={refresh} className="btn-secondary" disabled={refreshing}>
              {refreshing ? <Spinner className="size-4" /> : <RefreshCw className="size-4" aria-hidden />}
              Refresh
            </button>
          </>
        }
      />

      {summary.error ? (
        <Card className="mb-6">
          <ErrorState error={summary.error} onRetry={summary.reload} />
        </Card>
      ) : (
        <div className="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 xl:grid-cols-5">
          {cards.map((card) => (
            <StatCard
              key={card.label}
              {...card}
              loading={summary.loading}
              className={card.label === 'Total reports' ? 'col-span-2 lg:col-span-1' : ''}
            />
          ))}
        </div>
      )}

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <Card title="Status distribution" description="Share of reports in each workflow stage" className="lg:col-span-1">
          {summary.data ? (
            summary.data.total === 0 ? (
              <EmptyState title="No reports yet" description="Reports submitted by citizens will appear here." />
            ) : (
              <StatusChart summary={summary.data} />
            )
          ) : summary.error ? (
            <p className="py-10 text-center text-sm text-slate-500">Chart unavailable.</p>
          ) : (
            <LoadingState />
          )}
        </Card>

        <Card
          title="Recent reports"
          description="Newest reports first"
          className="lg:col-span-2"
          bodyClassName=""
          actions={
            <Link to="/reports" className="inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:text-teal-800">
              View all <ArrowRight className="size-4" aria-hidden />
            </Link>
          }
        >
          {recent.error ? (
            <ErrorState error={recent.error} onRetry={recent.reload} />
          ) : !recent.data ? (
            <LoadingState />
          ) : recent.data.data.length === 0 ? (
            <EmptyState title="No reports yet" description="New citizen reports will show up here." />
          ) : (
            <ReportsTable reports={recent.data.data} compact />
          )}
        </Card>
      </div>
    </>
  )
}
