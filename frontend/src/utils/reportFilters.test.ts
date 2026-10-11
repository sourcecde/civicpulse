import { describe, expect, it } from 'vitest'
import { filtersFromSearchParams, filtersToSearchParams, updateFilters } from './reportFilters'

describe('report filters', () => {
  it('round-trips valid filters through the query string', () => {
    const filters = {
      status: 'IN_PROGRESS' as const,
      category_id: 3,
      assigned_to: 'me',
      created_from: '2026-10-01',
      created_to: '2026-10-11',
      sort: 'assigned_at' as const,
      direction: 'asc' as const,
      per_page: 25,
      page: 2,
    }
    expect(filtersFromSearchParams(filtersToSearchParams(filters))).toEqual(filters)
  })

  it('drops values the backend would reject', () => {
    const params = new URLSearchParams(
      'status=CLOSED&category_id=abc&assigned_to=bob&created_from=11-10-2026&sort=email&direction=up&per_page=500&page=0',
    )
    expect(filtersFromSearchParams(params)).toEqual({})
  })

  it('accepts numeric operator ids for assigned_to', () => {
    expect(filtersFromSearchParams(new URLSearchParams('assigned_to=12'))).toEqual({ assigned_to: '12' })
  })

  it('resets to the first page when a filter changes', () => {
    expect(updateFilters({ status: 'OPEN', page: 4 }, { category_id: 2 })).toEqual({ status: 'OPEN', category_id: 2 })
  })

  it('keeps filters when only the page changes', () => {
    expect(updateFilters({ status: 'OPEN', page: 1 }, { page: 3 })).toEqual({ status: 'OPEN', page: 3 })
  })

  it('removes cleared filters', () => {
    expect(updateFilters({ status: 'OPEN', assigned_to: 'me' }, { status: undefined })).toEqual({ assigned_to: 'me' })
  })
})
