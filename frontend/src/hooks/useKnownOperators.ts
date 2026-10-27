import { useMemo } from 'react'
import { fetchOperators } from '../services/reportService'
import { useApiQuery } from './useApiQuery'
import { useCurrentUser } from './useAuth'

export function useKnownOperators() {
    const currentUser = useCurrentUser()
    const query = useApiQuery(fetchOperators)

    const operators = useMemo(
        () => query.data ?? [],
        [query.data]
    )

    /** Names for operators plus the signed-in user. */
    const nameFor = useMemo(() => {
        const names = new Map<number, string>(
            operators.map((op) => [op.id, op.name])
        )

        names.set(currentUser.id, `${currentUser.name} (you)`)

        return (id: number | null | undefined): string | null =>
            id == null ? null : names.get(id) ?? `User #${id}`
    }, [operators, currentUser])

    return {
        operators,
        nameFor,
        loading: query.loading,
    }
}
