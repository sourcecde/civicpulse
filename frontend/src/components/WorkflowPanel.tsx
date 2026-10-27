import { CheckCircle2, Info, Play, UserPlus } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { useToast } from '../hooks/useToast'
import { toApiError, type ApiError } from '../services/errors'
import { assignReport, updateReportStatus } from '../services/reportService'
import type { Report, Operator, User } from '../types/api'
import { availableWorkflowActions, type WorkflowAction } from '../utils/workflow'
import { ConfirmDialog } from './ConfirmDialog'

const REASON_MAX = 2000
const CUSTOM = '__custom'

type Pending =
  | { kind: 'assign'; operatorId: number; operatorLabel: string }
  | { kind: 'transition'; to: 'IN_PROGRESS' | 'RESOLVED'; label: string }

export function WorkflowPanel({
  report,
  user,
  operators,
  onChanged,
}: {
  report: Report
  user: User
  operators: Operator[]
  onChanged: () => void
}) {
  const { notify } = useToast()
  const availability = availableWorkflowActions(report, user)

  const [selected, setSelected] = useState<string>(user.role === 'OPERATOR' ? String(user.id) : '')
  const [customId, setCustomId] = useState('')
  const [reason, setReason] = useState('')
  const [pending, setPending] = useState<Pending | null>(null)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState<ApiError | null>(null)

  const operatorId = selected === CUSTOM ? Number(customId) : Number(selected)
  const operatorValid = Number.isInteger(operatorId) && operatorId > 0

  function operatorLabel(id: number): string {
    if (id === user.id) return `${user.name} (you)`
    return operators.find((op) => op.id === id)?.name ?? `user #${id}`
  }

  function requestAssign(event: FormEvent) {
    event.preventDefault()
    if (!operatorValid) return
    setError(null)
    setPending({ kind: 'assign', operatorId, operatorLabel: operatorLabel(operatorId) })
  }

  function requestTransition(action: Extract<WorkflowAction, { kind: 'transition' }>) {
    setError(null)
    setPending({ kind: 'transition', to: action.to, label: action.label })
  }

  async function confirm() {
    if (!pending) return
    setBusy(true)
    try {
      const trimmed = reason.trim() || undefined
      if (pending.kind === 'assign') {
        await assignReport(report.id, pending.operatorId, trimmed)
        notify('success', 'Report assigned', `${report.tracking_number} is now assigned to ${pending.operatorLabel}.`)
      } else {
        await updateReportStatus(report.id, pending.to, trimmed)
        notify(
          'success',
          pending.to === 'RESOLVED' ? 'Report resolved' : 'Work started',
          `${report.tracking_number} is now ${pending.to === 'RESOLVED' ? 'resolved' : 'in progress'}.`,
        )
      }
      setReason('')
      setPending(null)
      onChanged()
    } catch (caught) {
      const apiError = toApiError(caught)
      setError(apiError)
      setPending(null)
      notify('error', 'Action failed', apiError.message)
      // The report may have been changed by someone else (e.g. already assigned); show the latest state.
      if (apiError.status === 422 || apiError.status === 403) onChanged()
    } finally {
      setBusy(false)
    }
  }

  const reasonField = (
    <div>
      <label htmlFor="workflow-reason" className="label">
        Reason <span className="font-normal text-slate-400">(optional)</span>
      </label>
      <textarea
        id="workflow-reason"
        rows={3}
        maxLength={REASON_MAX}
        value={reason}
        onChange={(event) => setReason(event.target.value)}
        className="input mt-1.5 resize-y"
        placeholder="Visible in the report's audit history"
      />
      <p className="mt-1 text-right text-xs text-slate-400">{reason.length}/{REASON_MAX}</p>
    </div>
  )

  return (
    <div className="space-y-4">
      {error && (
        <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
          {error.message}
        </div>
      )}

      {availability.actions.length === 0 && (
        <div className="flex gap-2.5 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">
          {report.status === 'RESOLVED' ? (
            <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-emerald-600" aria-hidden />
          ) : (
            <Info className="mt-0.5 size-4 shrink-0 text-slate-400" aria-hidden />
          )}
          <span>{availability.reason}</span>
        </div>
      )}

      {availability.actions.map((action) =>
        action.kind === 'assign' ? (
          <form key="assign" onSubmit={requestAssign} className="space-y-4">
            <div>
              <label htmlFor="assign-operator" className="label">Assign to operator</label>
              <select
                id="assign-operator"
                className="input mt-1.5"
                value={selected}
                onChange={(event) => setSelected(event.target.value)}
              >
                <option value="" disabled>Select an operator…</option>
                {user.role === 'OPERATOR' && <option value={user.id}>Me ({user.name})</option>}
                {operators
                  .filter((op) => op.id !== user.id)
                  .map((op) => (
                    <option key={op.id} value={op.id}>{op.name}</option>
                  ))}
                <option value={CUSTOM}>Enter a user ID…</option>
              </select>
              {selected === CUSTOM && (
                <input
                  type="number"
                  min={1}
                  inputMode="numeric"
                  className="input mt-2"
                  placeholder="Operator user ID"
                  value={customId}
                  onChange={(event) => setCustomId(event.target.value)}
                  aria-label="Operator user ID"
                />
              )}
              <p className="mt-1.5 text-xs text-slate-500">
                The list shows operators who already have assignments. Use a user ID for anyone else; the server
                checks that the user is an operator.
              </p>
              {error?.fieldErrors.operator_id && <p className="field-error">{error.fieldErrors.operator_id[0]}</p>}
            </div>
            {reasonField}
            <button type="submit" className="btn-primary w-full justify-center" disabled={!operatorValid}>
              <UserPlus className="size-4" aria-hidden />
              Assign report
            </button>
          </form>
        ) : (
          <div key={action.to} className="space-y-4">
            {reasonField}
            <button
              type="button"
              className={`w-full justify-center ${action.to === 'RESOLVED' ? 'btn-success' : 'btn-primary'}`}
              onClick={() => requestTransition(action)}
            >
              {action.to === 'RESOLVED' ? <CheckCircle2 className="size-4" aria-hidden /> : <Play className="size-4" aria-hidden />}
              {action.label}
            </button>
          </div>
        ),
      )}

      <ConfirmDialog
        open={pending !== null}
        busy={busy}
        title={
          pending?.kind === 'assign'
            ? 'Assign this report?'
            : pending?.to === 'RESOLVED'
              ? 'Mark this report as resolved?'
              : 'Start work on this report?'
        }
        confirmLabel={pending?.kind === 'assign' ? 'Assign' : pending?.label ?? 'Confirm'}
        onConfirm={() => void confirm()}
        onCancel={() => setPending(null)}
      >
        {pending?.kind === 'assign' ? (
          <p>
            <span className="font-medium text-slate-900">{report.tracking_number}</span> will be assigned to{' '}
            <span className="font-medium text-slate-900">{pending.operatorLabel}</span> and move to{' '}
            <span className="font-medium text-slate-900">Assigned</span>.
          </p>
        ) : pending ? (
          <p>
            <span className="font-medium text-slate-900">{report.tracking_number}</span> will move to{' '}
            <span className="font-medium text-slate-900">{pending.to === 'RESOLVED' ? 'Resolved' : 'In progress'}</span>.
            {pending.to === 'RESOLVED' && ' This is the final workflow step and cannot be undone from the dashboard.'}
          </p>
        ) : null}
        {reason.trim() && (
          <p className="mt-3 rounded-md bg-slate-50 px-3 py-2 text-slate-600">Reason: “{reason.trim()}”</p>
        )}
      </ConfirmDialog>
    </div>
  )
}
