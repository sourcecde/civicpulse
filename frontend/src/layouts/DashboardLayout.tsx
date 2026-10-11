import { ClipboardList, LayoutDashboard, LogOut, Menu, UserCheck, X } from 'lucide-react'
import { useState } from 'react'
import { NavLink, Outlet, useLocation } from 'react-router-dom'
import { Logo } from '../components/Logo'
import { useAuth, useCurrentUser } from '../hooks/useAuth'

const NAV = [
  { to: '/', label: 'Dashboard', icon: LayoutDashboard, end: true },
  { to: '/reports', label: 'All reports', icon: ClipboardList, end: true },
]

export function DashboardLayout() {
  const user = useCurrentUser()
  const { logout } = useAuth()
  const location = useLocation()
  const [openFor, setOpenFor] = useState<string | null>(null)
  // The mobile drawer closes itself on navigation because it is tied to the path it was opened on.
  const sidebarOpen = openFor === location.key

  const myAssignmentsActive =
    location.pathname === '/reports' && new URLSearchParams(location.search).get('assigned_to') === 'me'

  const sidebar = (
    <div className="flex h-full flex-col bg-slate-900">
      <div className="flex h-16 items-center justify-between px-5">
        <Logo inverted />
        <button
          type="button"
          className="rounded-md p-1 text-slate-400 hover:text-white lg:hidden"
          onClick={() => setOpenFor(null)}
          aria-label="Close navigation"
        >
          <X className="size-5" />
        </button>
      </div>
      <nav className="flex-1 space-y-1 px-3 py-4" aria-label="Main">
        <p className="px-3 pb-2 text-xs font-medium uppercase tracking-wider text-slate-500">Operations</p>
        {NAV.map(({ to, label, icon: Icon, end }) => (
          <NavLink
            key={to}
            to={to}
            end={end}
            className={({ isActive }) =>
              `nav-link ${isActive && !(to === '/reports' && myAssignmentsActive) ? 'nav-link-active' : ''}`
            }
          >
            <Icon className="size-4" aria-hidden />
            {label}
          </NavLink>
        ))}
        {user.role === 'OPERATOR' && (
          <NavLink to="/reports?assigned_to=me" className={`nav-link ${myAssignmentsActive ? 'nav-link-active' : ''}`}>
            <UserCheck className="size-4" aria-hidden />
            My assignments
          </NavLink>
        )}
      </nav>
      <div className="border-t border-slate-800 p-4">
        <div className="flex items-center gap-3">
          <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-teal-600/20 text-sm font-semibold text-teal-300">
            {initials(user.name)}
          </span>
          <div className="min-w-0 flex-1">
            <p className="truncate text-sm font-medium text-white">{user.name}</p>
            <p className="truncate text-xs text-slate-400">{user.email}</p>
          </div>
        </div>
      </div>
    </div>
  )

  return (
    <div className="min-h-svh bg-slate-50">
      {/* Desktop sidebar */}
      <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 lg:block">{sidebar}</aside>

      {/* Mobile drawer */}
      {sidebarOpen && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <div className="absolute inset-0 bg-slate-900/50" onClick={() => setOpenFor(null)} aria-hidden />
          <aside className="relative h-full w-72 max-w-[85%] shadow-xl">{sidebar}</aside>
        </div>
      )}

      <div className="lg:pl-64">
        <header className="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
          <button
            type="button"
            className="-ml-1 rounded-md p-1.5 text-slate-600 hover:bg-slate-100 lg:hidden"
            onClick={() => setOpenFor(location.key)}
            aria-label="Open navigation"
          >
            <Menu className="size-5" />
          </button>
          <div className="lg:hidden">
            <Logo />
          </div>
          <div className="ml-auto flex items-center gap-3">
            <span className="hidden text-sm text-slate-600 sm:inline">{user.name}</span>
            <span className="rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-600/20">
              {user.role === 'ADMIN' ? 'Administrator' : 'Operator'}
            </span>
            <button type="button" onClick={() => void logout()} className="btn-ghost">
              <LogOut className="size-4" aria-hidden />
              <span className="hidden sm:inline">Sign out</span>
            </button>
          </div>
        </header>
        <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
          <Outlet />
        </main>
      </div>
    </div>
  )
}

function initials(name: string): string {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('')
}
