import { describe, expect, it } from 'vitest'
import type { Report, User } from '../types/api'
import { availableWorkflowActions } from './workflow'

const operator: User = { id: 10, name: 'Op', email: 'op@test', role: 'OPERATOR', created_at: '' }
const otherOperator: User = { ...operator, id: 11 }
const admin: User = { ...operator, id: 1, role: 'ADMIN' }
const citizen: User = { ...operator, id: 20, role: 'CITIZEN' }

function report(overrides: Partial<Report>): Report {
  return {
    id: 'uuid',
    category_id: 1,
    description: '',
    location: '',
    tracking_number: 'CP-1',
    reporter_email: 'r@test',
    created_at: '',
    updated_at: '',
    status: 'OPEN',
    assigned_operator_id: null,
    assigned_at: null,
    started_at: null,
    resolved_at: null,
    version: 0,
    ...overrides,
  }
}

describe('availableWorkflowActions', () => {
  it('offers assignment on OPEN reports to operators and admins', () => {
    expect(availableWorkflowActions(report({}), operator).actions).toEqual([{ kind: 'assign' }])
    expect(availableWorkflowActions(report({}), admin).actions).toEqual([{ kind: 'assign' }])
  })

  it('offers nothing to citizens', () => {
    const result = availableWorkflowActions(report({}), citizen)
    expect(result.actions).toEqual([])
    expect(result.reason).toMatch(/operators and administrators/)
  })

  it('lets the assigned operator start an ASSIGNED report', () => {
    const result = availableWorkflowActions(report({ status: 'ASSIGNED', assigned_operator_id: 10 }), operator)
    expect(result.actions).toEqual([{ kind: 'transition', to: 'IN_PROGRESS', label: 'Start work' }])
  })

  it('lets the assigned operator resolve an IN_PROGRESS report', () => {
    const result = availableWorkflowActions(report({ status: 'IN_PROGRESS', assigned_operator_id: 10 }), operator)
    expect(result.actions).toEqual([{ kind: 'transition', to: 'RESOLVED', label: 'Mark as resolved' }])
  })

  it('blocks other operators from changing status', () => {
    const result = availableWorkflowActions(report({ status: 'ASSIGNED', assigned_operator_id: 10 }), otherOperator)
    expect(result.actions).toEqual([])
    expect(result.reason).toMatch(/assigned operator/)
  })

  it('lets admins transition reports assigned to anyone', () => {
    const result = availableWorkflowActions(report({ status: 'IN_PROGRESS', assigned_operator_id: 10 }), admin)
    expect(result.actions).toHaveLength(1)
  })

  it('offers nothing for RESOLVED reports, and never re-assignment', () => {
    for (const user of [operator, admin]) {
      const result = availableWorkflowActions(report({ status: 'RESOLVED', assigned_operator_id: 10 }), user)
      expect(result.actions).toEqual([])
    }
    expect(
      availableWorkflowActions(report({ status: 'ASSIGNED', assigned_operator_id: 10 }), admin).actions,
    ).not.toContainEqual({ kind: 'assign' })
  })
})
