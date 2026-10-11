import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts'
import { REPORT_STATUSES, type DashboardSummary } from '../types/api'
import { STATUS_COLORS, STATUS_LABELS } from '../utils/format'

export function StatusChart({ summary }: { summary: DashboardSummary }) {
  const data = REPORT_STATUSES.map((status) => ({
    status,
    name: STATUS_LABELS[status],
    value: summary.by_status[status],
  }))

  return (
    <div className="flex flex-col items-center gap-6 sm:flex-row lg:flex-col">
      <div className="relative h-48 w-48 shrink-0">
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Pie
              data={data}
              dataKey="value"
              nameKey="name"
              innerRadius="68%"
              outerRadius="100%"
              paddingAngle={summary.total > 0 ? 2 : 0}
              stroke="none"
              isAnimationActive={false}
            >
              {data.map((entry) => (
                <Cell key={entry.status} fill={STATUS_COLORS[entry.status]} />
              ))}
            </Pie>
            <Tooltip
              formatter={(value) => [Number(value).toLocaleString(), 'Reports']}
              contentStyle={{ borderRadius: 8, borderColor: '#e2e8f0', fontSize: 13 }}
            />
          </PieChart>
        </ResponsiveContainer>
        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
          <span className="text-2xl font-semibold tabular-nums text-slate-900">{summary.total.toLocaleString()}</span>
          <span className="text-xs text-slate-500">reports</span>
        </div>
      </div>

      <ul className="w-full space-y-3">
        {data.map((entry) => {
          const percent = summary.total > 0 ? Math.round((entry.value / summary.total) * 100) : 0
          return (
            <li key={entry.status}>
              <div className="flex items-center justify-between text-sm">
                <span className="flex items-center gap-2 text-slate-700">
                  <span className="size-2.5 rounded-sm" style={{ backgroundColor: STATUS_COLORS[entry.status] }} />
                  {entry.name}
                </span>
                <span className="tabular-nums text-slate-900">
                  {entry.value.toLocaleString()}
                  <span className="ml-1.5 text-slate-400">{percent}%</span>
                </span>
              </div>
              <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                <div
                  className="h-full rounded-full"
                  style={{ width: `${percent}%`, backgroundColor: STATUS_COLORS[entry.status] }}
                />
              </div>
            </li>
          )
        })}
      </ul>
    </div>
  )
}
