import { useCallback, useEffect, useState } from 'react'
import { toApiError, type ApiError } from '../services/errors'

interface QueryResult<T> {
  fetcher: () => Promise<T>
  nonce: number
  data: T | null
  error: ApiError | null
}

export interface ApiQuery<T> {
  /** Latest successful data. Kept while a refetch is in flight so the UI doesn't flash. */
  data: T | null
  error: ApiError | null
  loading: boolean
  reload: () => void
}

/**
 * Runs `fetcher` whenever it changes (callers memoise it with useCallback on
 * the inputs it depends on) or when `reload()` is called.
 */
export function useApiQuery<T>(fetcher: () => Promise<T>): ApiQuery<T> {
  const [nonce, setNonce] = useState(0)
  const [result, setResult] = useState<QueryResult<T> | null>(null)

  useEffect(() => {
    let cancelled = false
    fetcher().then(
      (data) => {
        if (!cancelled) setResult({ fetcher, nonce, data, error: null })
      },
      (error: unknown) => {
        if (!cancelled) setResult({ fetcher, nonce, data: null, error: toApiError(error) })
      },
    )
    return () => {
      cancelled = true
    }
  }, [fetcher, nonce])

  const isCurrent = result !== null && result.fetcher === fetcher && result.nonce === nonce
  const reload = useCallback(() => setNonce((n) => n + 1), [])

  return {
    data: result?.data ?? null,
    error: isCurrent ? result.error : null,
    loading: !isCurrent,
    reload,
  }
}
