import { ChevronLeft, ChevronRight } from 'lucide-react'
import type { Paginated } from '../types/api'

export function Pagination({
  meta,
  onPageChange,
}: {
  meta: Paginated<unknown>['meta']
  onPageChange: (page: number) => void
}) {
  const { current_page: current, last_page: last, from, to, total } = meta
  const pages = pageWindow(current, last)

  return (
    <nav
      className="flex flex-col items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 sm:flex-row"
      aria-label="Pagination"
    >
      <p className="text-sm text-slate-500">
        {total === 0 ? (
          'No results'
        ) : (
          <>
            Showing <span className="font-medium text-slate-900">{from}</span>–
            <span className="font-medium text-slate-900">{to}</span> of{' '}
            <span className="font-medium text-slate-900">{total.toLocaleString()}</span>
          </>
        )}
      </p>
      <div className="flex items-center gap-1">
        <button
          type="button"
          className="page-btn"
          onClick={() => onPageChange(current - 1)}
          disabled={current <= 1}
          aria-label="Previous page"
        >
          <ChevronLeft className="size-4" />
        </button>
        {pages.map((page, index) =>
          page === null ? (
            <span key={`gap-${index}`} className="px-1.5 text-sm text-slate-400">
              …
            </span>
          ) : (
            <button
              key={page}
              type="button"
              onClick={() => onPageChange(page)}
              className={`page-btn ${page === current ? 'page-btn-active' : ''}`}
              aria-current={page === current ? 'page' : undefined}
            >
              {page}
            </button>
          ),
        )}
        <button
          type="button"
          className="page-btn"
          onClick={() => onPageChange(current + 1)}
          disabled={current >= last}
          aria-label="Next page"
        >
          <ChevronRight className="size-4" />
        </button>
      </div>
    </nav>
  )
}

/** e.g. 1 … 4 5 6 … 12 */
function pageWindow(current: number, last: number): (number | null)[] {
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1)
  const pages = new Set([1, last, current - 1, current, current + 1])
  const sorted = [...pages].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b)
  const result: (number | null)[] = []
  sorted.forEach((page, i) => {
    if (i > 0 && page - sorted[i - 1] > 1) result.push(null)
    result.push(page)
  })
  return result
}
