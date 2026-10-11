import type { Report, ReportStatus, User } from '../types/api'

export type WorkflowAction =
  | { kind: 'assign' }
  | { kind: 'transition'; to: 'IN_PROGRESS' | 'RESOLVED'; label: string }

export interface WorkflowAvailability {
  actions: WorkflowAction[]
  /** Why nothing is available, when `actions` is empty. */
  reason: string | null
}

/**
 * Mirrors the backend rules so the UI only offers transitions the API accepts:
 * - ReportPolicy::assign: OPERATOR or ADMIN; ReportWorkflowService::assign: only OPEN reports.
 * - ReportPolicy::transition: ADMIN, or the OPERATOR the report is assigned to.
 * - ReportWorkflowService::transition: ASSIGNED → IN_PROGRESS → RESOLVED, no skipping.
 * The backend still enforces all of this; this only shapes the UI.
 */
export function availableWorkflowActions(report: Report, user: User): WorkflowAvailability {
  const isStaff = user.role === 'OPERATOR' || user.role === 'ADMIN'
  if (!isStaff) {
    return { actions: [], reason: 'Only operators and administrators can change reports.' }
  }

  if (report.status === 'OPEN') {
    return { actions: [{ kind: 'assign' }], reason: null }
  }

  if (report.status === 'RESOLVED') {
    return { actions: [], reason: 'This report is resolved. The workflow is complete.' }
  }

  const canTransition = user.role === 'ADMIN' || report.assigned_operator_id === user.id
  if (!canTransition) {
    return {
      actions: [],
      reason: 'Only the assigned operator or an administrator can update the status of this report.',
    }
  }

  const next = NEXT_TRANSITION[report.status]
  return next ? { actions: [next], reason: null } : { actions: [], reason: null }
}

const NEXT_TRANSITION: Partial<Record<ReportStatus, WorkflowAction>> = {
  ASSIGNED: { kind: 'transition', to: 'IN_PROGRESS', label: 'Start work' },
  IN_PROGRESS: { kind: 'transition', to: 'RESOLVED', label: 'Mark as resolved' },
}
