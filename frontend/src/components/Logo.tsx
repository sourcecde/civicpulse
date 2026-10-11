import { Activity } from 'lucide-react'

export function Logo({ inverted = false }: { inverted?: boolean }) {
  return (
    <div className="flex items-center gap-2.5">
      <span className="flex size-8 items-center justify-center rounded-lg bg-teal-600 text-white shadow-sm">
        <Activity className="size-5" aria-hidden />
      </span>
      <span className={`text-base font-semibold tracking-tight ${inverted ? 'text-white' : 'text-slate-900'}`}>
        CivicPulse
      </span>
    </div>
  )
}
