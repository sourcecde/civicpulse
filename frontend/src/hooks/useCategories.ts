import { useMemo } from 'react'
import { fetchCategories } from '../services/reportService'
import type { Category } from '../types/api'
import { useApiQuery } from './useApiQuery'

export function useCategories() {
  const query = useApiQuery(fetchCategories)
  const categories = useMemo(() => query.data ?? [], [query.data])
  const byId = useMemo(() => new Map<number, Category>(categories.map((c) => [c.id, c])), [categories])
  return { categories, byId, loading: query.loading, error: query.error }
}
