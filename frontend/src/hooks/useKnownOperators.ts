import { useMemo } from 'react'
import { fetchReports } from '../services/reportService'
import type { User } from '../types/api'
import { useApiQuery } from './useApiQuery'
import { useCurrentUser } from './useAuth'

// The API has no "list operators" endpoint, so operator names are collected
// from the assigned_operator of the most recently assigned reports. Operators
// who have never been assigned a report do not appear here; the UI offers a
// manual user-id entry for them.
const fetchRecentlyAssigned = () =>
  fetchReports({ sort: 'assigned_at', direction: 'desc', per_page: 100 })

export function useKnownOperators() {
  const currentUser = useCurrentUser()
  const query = useApiQuery(fetchRecentlyAssigned)

  const operators = useMemo(() => {
    const map = new Map<number, User>()
    if (currentUser.role === 'OPERATOR') {
      map.set(currentUser.id, currentUser)
    }
    for (const report of query.data?.data ?? []) {
      if (report.assigned_operator) {
        map.set(report.assigned_operator.id, report.assigned_operator)
      }
    }
    return [...map.values()].sort((a, b) => a.name.localeCompare(b.name))
  }, [query.data, currentUser])

  /** Names for any user id we have seen: operators plus the signed-in user. */
  const nameFor = useMemo(() => {
    const names = new Map<number, string>(operators.map((op) => [op.id, op.name]))
    names.set(currentUser.id, `${currentUser.name} (you)`)
    return (id: number | null | undefined): string | null => (id == null ? null : names.get(id) ?? `User #${id}`)
  }, [operators, currentUser])

  return { operators, nameFor, loading: query.loading }
}
