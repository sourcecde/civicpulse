import { ArrowLeft, Check, Copy, ExternalLink, MapPin, RefreshCw } from 'lucide-react'
import { useCallback, useState, type ReactNode } from 'react'
import { Link, useLocation, useParams } from 'react-router-dom'
import { Card } from '../components/Card'
import { EmptyState, ErrorState, LoadingState, Spinner } from '../components/Feedback'
import { HistoryTimeline } from '../components/HistoryTimeline'
import { PageHeader } from '../components/PageHeader'
import { StatusBadge } from '../components/StatusBadge'
import { WorkflowPanel } from '../components/WorkflowPanel'
import { useApiQuery } from '../hooks/useApiQuery'
import { useCurrentUser } from '../hooks/useAuth'
import { useCategories } from '../hooks/useCategories'
import { useKnownOperators } from '../hooks/useKnownOperators'
import { fetchReport, fetchReportHistory } from '../services/reportService'
import { formatDateTime, formatRelative } from '../utils/format'
import { formatCoordinates, openStreetMapUrl, parseWkbPoint } from '../utils/location'

export function ReportDetailPage() {
  const { reportId = '' } = useParams()
  const location = useLocation()
  const backTo = (location.state as { from?: string } | null)?.from ?? '/reports'
  const user = useCurrentUser()

  const reportFetcher = useCallback(() => fetchReport(reportId), [reportId])
  const historyFetcher = useCallback(() => fetchReportHistory(reportId), [reportId])
  const report = useApiQuery(reportFetcher)
  const history = useApiQuery(historyFetcher)
  const { byId: categoriesById } = useCategories()
  const { operators, nameFor } = useKnownOperators()

  const reloadAll = useCallback(() => {
    report.reload()
    history.reload()
  }, [report, history])

  const backLink = (
    <Link to={backTo} className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-900">
      <ArrowLeft className="size-4" aria-hidden />
      Back to reports
    </Link>
  )

  // Show data for this id only; useApiQuery keeps stale data across id changes.
  const data = report.data?.id === reportId ? report.data : null

  if (report.error) {
    return (
      <>
        <div className="mb-6">{backLink}</div>
        <Card>
          <ErrorState error={report.error} onRetry={report.error.status === 404 ? undefined : report.reload} />
        </Card>
      </>
    )
  }

  if (!data) {
    return (
      <>
        <div className="mb-6">{backLink}</div>
        <LoadingState label="Loading report…" />
      </>
    )
  }

  const coords = parseWkbPoint(data.location)
  const category = categoriesById.get(data.category_id)

  return (
    <>
      <PageHeader
        eyebrow={backLink}
        title={
          <span className="flex flex-wrap items-center gap-3">
            {data.tracking_number}
            <StatusBadge status={data.status} />
          </span>
        }
        description={`Reported ${formatRelative(data.created_at)} · ${category?.name ?? `Category #${data.category_id}`}`}
        actions={
          <button type="button" onClick={reloadAll} className="btn-secondary" disabled={report.loading || history.loading}>
            {report.loading || history.loading ? <Spinner className="size-4" /> : <RefreshCw className="size-4" aria-hidden />}
            Refresh
          </button>
        }
      />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div className="space-y-6 lg:col-span-2">
          <Card title="Report details">
            <p className="whitespace-pre-line text-sm leading-relaxed text-slate-800">{data.description}</p>
            <dl className="mt-6 grid grid-cols-1 gap-x-6 gap-y-5 border-t border-slate-100 pt-5 sm:grid-cols-2">
              <Field label="Tracking number">
                <CopyValue value={data.tracking_number} />
              </Field>
              <Field label="Report ID">
                <CopyValue value={data.id} mono />
              </Field>
              <Field label="Category">{category?.name ?? `Category #${data.category_id}`}</Field>
              <Field label="Reporter">
                {data.reporter_email ? (
                  <a href={`mailto:${data.reporter_email}`} className="text-teal-700 hover:underline">{data.reporter_email}</a>
                ) : (
                  '—'
                )}
              </Field>
              <Field label="Location">
                {coords ? (
                  <span className="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span className="inline-flex items-center gap-1.5 font-mono text-[13px]">
                      <MapPin className="size-4 text-slate-400" aria-hidden />
                      {formatCoordinates(coords)}
                    </span>
                    <a
                      href={openStreetMapUrl(coords)}
                      target="_blank"
                      rel="noreferrer"
                      className="inline-flex items-center gap-1 text-teal-700 hover:underline"
                    >
                      Open map <ExternalLink className="size-3.5" aria-hidden />
                    </a>
                  </span>
                ) : (
                  <span className="text-slate-400">Unavailable</span>
                )}
              </Field>
              <Field label="Assigned operator">
                {data.assigned_operator_id ? nameFor(data.assigned_operator_id) : <span className="text-slate-400">Unassigned</span>}
              </Field>
            </dl>
          </Card>

          <Card title="Status history" description="Append-only audit trail of workflow changes">
            {history.error ? (
              <ErrorState error={history.error} onRetry={history.reload} />
            ) : !history.data ? (
              <LoadingState />
            ) : history.data.length === 0 ? (
              <EmptyState title="No workflow changes yet" description="Assignment and status changes will appear here." />
            ) : (
              <HistoryTimeline entries={history.data} nameFor={nameFor} />
            )}
          </Card>
        </div>

        <div className="space-y-6">
          <Card title="Workflow" description="Available actions for your role">
            <WorkflowPanel key={`${data.id}:${data.version}`} report={data} user={user} operators={operators} onChanged={reloadAll} />
          </Card>

          <Card title="Timeline">
            <dl className="space-y-3 text-sm">
              <TimestampRow label="Created" value={data.created_at} />
              <TimestampRow label="Assigned" value={data.assigned_at} />
              <TimestampRow label="Work started" value={data.started_at} />
              <TimestampRow label="Resolved" value={data.resolved_at} />
              <TimestampRow label="Last updated" value={data.updated_at} />
              <div className="flex justify-between gap-4 border-t border-slate-100 pt-3">
                <dt className="text-slate-500">Version</dt>
                <dd className="tabular-nums text-slate-900">{data.version}</dd>
              </div>
            </dl>
          </Card>
        </div>
      </div>
    </>
  )
}

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="min-w-0">
      <dt className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</dt>
      <dd className="mt-1 text-sm text-slate-900">{children}</dd>
    </div>
  )
}

function TimestampRow({ label, value }: { label: string; value: string | null }) {
  return (
    <div className="flex justify-between gap-4">
      <dt className="text-slate-500">{label}</dt>
      <dd className={`text-right ${value ? 'text-slate-900' : 'text-slate-400'}`} title={value ?? undefined}>
        {formatDateTime(value)}
      </dd>
    </div>
  )
}

function CopyValue({ value, mono = false }: { value: string; mono?: boolean }) {
  const [copied, setCopied] = useState(false)
  return (
    <span className="flex min-w-0 items-center gap-1.5">
      <span className={`truncate ${mono ? 'font-mono text-[13px]' : ''}`} title={value}>{value}</span>
      <button
        type="button"
        className="shrink-0 rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
        onClick={() => {
          void navigator.clipboard?.writeText(value).then(() => {
            setCopied(true)
            window.setTimeout(() => setCopied(false), 1500)
          })
        }}
        aria-label="Copy to clipboard"
      >
        {copied ? <Check className="size-3.5 text-emerald-600" /> : <Copy className="size-3.5" />}
      </button>
    </span>
  )
}
