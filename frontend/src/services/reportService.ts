import type {
  Category,
  DashboardSummary,
  Paginated,
  Report,
  ReportFilters,
  ReportHistoryEntry,
  Resource,
  Operator,
} from '../types/api'
import { apiClient } from './apiClient'

export async function fetchDashboardSummary(): Promise<DashboardSummary> {
  const { data } = await apiClient.get<Resource<DashboardSummary>>('/dashboard/summary')
  return data.data
}

export async function fetchReports(filters: ReportFilters): Promise<Paginated<Report>> {
  const { data } = await apiClient.get<Paginated<Report>>('/reports', { params: filters })
  return data
}

export async function fetchReport(id: string): Promise<Report> {
  const { data } = await apiClient.get<Resource<Report>>(`/reports/${encodeURIComponent(id)}`)
  return data.data
}

export async function fetchReportHistory(id: string): Promise<ReportHistoryEntry[]> {
  const { data } = await apiClient.get<ReportHistoryEntry[]>(`/reports/${encodeURIComponent(id)}/history`)
  return data
}

export async function assignReport(id: string, operatorId: number, reason?: string): Promise<Report> {
  const { data } = await apiClient.post<Resource<Report>>(`/reports/${encodeURIComponent(id)}/assign`, {
    operator_id: operatorId,
    reason: reason || null,
  })
  return data.data
}

export async function updateReportStatus(
  id: string,
  status: 'IN_PROGRESS' | 'RESOLVED',
  reason?: string,
): Promise<Report> {
  const { data } = await apiClient.patch<Resource<Report>>(`/reports/${encodeURIComponent(id)}/status`, {
    status,
    reason: reason || null,
  })
  return data.data
}

/** Active categories only (GET /api/categories is public). */
export async function fetchCategories(): Promise<Category[]> {
  const { data } = await apiClient.get<Resource<Category[]>>('/categories')
  return data.data
}

export async function fetchOperators(): Promise<Operator[]> {
    const { data } = await apiClient.get<Resource<Operator[]>>('/operators')
    return data.data
}
