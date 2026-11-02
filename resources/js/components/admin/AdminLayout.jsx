import { useEffect, useState } from 'react'
import { Link, router, usePage } from '@inertiajs/react'
import { toast } from 'sonner'
import {
  Building2, ChevronsUpDown, LayoutDashboard, LogOut, Menu, PanelLeft, Store, UserX,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Sheet, SheetContent, SheetDescription, SheetTitle } from '@/components/ui/sheet'
import { Toaster } from '@/components/ui/sonner'
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { ImpersonationResult } from '@/components/admin/TenantBits'
import { cn } from '@/lib/utils'
import { number } from '@/lib/format'

const NAV = [
  { href: '/admin', label: 'Dashboard', icon: LayoutDashboard, match: (u) => u === '/admin' },
  { href: '/admin/tenants', label: 'Tenant', icon: Building2, match: (u) => u.startsWith('/admin/tenants'), count: 'tenants' },
  { href: '/admin/retention', label: 'Tenant Tidak Aktif', icon: UserX, match: (u) => u.startsWith('/admin/retention'), count: 'inactive' },
]

const initials = (name = '') =>
  name.split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]?.toUpperCase()).join('') || 'A'

function NavLinks({ url, counts, collapsed, onNavigate }) {
  const path = url.split('?')[0]
  return (
    <nav className="flex flex-col gap-2">
      {NAV.map(({ href, label, icon: Icon, match, count }) => {
        const active = match(path)
        const n = count ? counts?.[count] : null
        return (
          <Link
            key={href}
            href={href}
            onClick={onNavigate}
            title={collapsed ? label : undefined}
            className={cn(
              'flex h-12 items-center gap-3 rounded-xl border px-4 text-[15px] transition-colors',
              collapsed && 'justify-center px-0',
              active
                ? 'border-border bg-background font-medium text-foreground shadow-[0_1px_3px_rgba(16,24,40,0.08)]'
                : 'border-transparent text-foreground/80 hover:bg-muted'
            )}
          >
            <Icon className="h-[18px] w-[18px] shrink-0" strokeWidth={1.75} />
            {!collapsed && <span className="flex-1 truncate">{label}</span>}
            {!collapsed && n > 0 && (
              <span className="rounded-md bg-muted px-2 py-0.5 text-xs font-medium tabular-nums text-foreground/70">
                {number(n)}
              </span>
            )}
          </Link>
        )
      })}
    </nav>
  )
}

function Brand({ collapsed, onToggle }) {
  return (
    <div className={cn('flex items-center', collapsed ? 'justify-center' : 'justify-between')}>
      <div className="flex items-center gap-2.5">
        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
          <Store className="h-5 w-5" />
        </div>
        {!collapsed && <span className="text-xl font-semibold tracking-tight">GoKasir</span>}
      </div>
      {onToggle && !collapsed && (
        <button
          type="button" onClick={onToggle} aria-label="Ciutkan sidebar"
          className="flex h-9 w-9 items-center justify-center rounded-lg border text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
        >
          <PanelLeft className="h-[18px] w-[18px]" />
        </button>
      )}
    </div>
  )
}

function UserMenu({ admin, collapsed }) {
  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <button
          type="button"
          className={cn(
            'flex w-full items-center gap-3 rounded-2xl border bg-background p-3 text-left shadow-[0_1px_2px_rgba(16,24,40,0.04)] transition-colors hover:bg-muted/50',
            collapsed && 'justify-center p-2'
          )}
        >
          <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
            {initials(admin?.name)}
          </span>
          {!collapsed && (
            <>
              <span className="min-w-0 flex-1">
                <span className="block truncate text-sm font-semibold">{admin?.name}</span>
                <span className="block truncate text-xs text-muted-foreground">{admin?.email}</span>
              </span>
              <ChevronsUpDown className="h-4 w-4 shrink-0 text-muted-foreground" />
            </>
          )}
        </button>
      </DropdownMenuTrigger>
      <DropdownMenuContent side="top" align="start" className="w-56">
        <DropdownMenuLabel className="font-normal">
          <div className="truncate text-sm font-medium">{admin?.name}</div>
          <div className="truncate text-xs text-muted-foreground">{admin?.email}</div>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuItem onSelect={() => router.post('/admin/logout')} className="text-destructive focus:text-destructive">
          <LogOut className="mr-2 h-4 w-4" /> Keluar
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}

export default function AdminLayout({ children }) {
  const page = usePage()
  const admin = page.props.auth?.admin
  const counts = page.props.navCounts
  const flash = page.props.flash
  const [open, setOpen] = useState(false)
  const [collapsed, setCollapsed] = useState(() => {
    try { return localStorage.getItem('admin.sidebar') === 'collapsed' } catch { return false }
  })

  const toggle = () => {
    setCollapsed((c) => {
      try { localStorage.setItem('admin.sidebar', c ? 'open' : 'collapsed') } catch { /* storage diblokir */ }
      return !c
    })
  }

  useEffect(() => {
    if (flash?.success) toast.success(flash.success)
    if (flash?.error) toast.error(flash.error)
  }, [flash?.success, flash?.error])

  const sidebar = (isCollapsed, onNavigate, onToggle) => (
    <div className="flex h-full flex-col gap-8 px-5 py-6">
      <Brand collapsed={isCollapsed} onToggle={onToggle} />
      {isCollapsed && (
        <button
          type="button" onClick={toggle} aria-label="Perluas sidebar"
          className="mx-auto -mt-4 flex h-9 w-9 items-center justify-center rounded-lg border text-muted-foreground hover:bg-muted hover:text-foreground"
        >
          <PanelLeft className="h-[18px] w-[18px] rotate-180" />
        </button>
      )}
      <NavLinks url={page.url} counts={counts} collapsed={isCollapsed} onNavigate={onNavigate} />
      <div className="mt-auto"><UserMenu admin={admin} collapsed={isCollapsed} /></div>
    </div>
  )

  return (
    <div className="min-h-screen bg-background">
      <aside
        className={cn(
          'fixed inset-y-0 left-0 z-20 hidden border-r bg-background transition-[width] duration-200 lg:block',
          collapsed ? 'w-24' : 'w-72'
        )}
      >
        {sidebar(collapsed, undefined, toggle)}
      </aside>

      <div className={cn('transition-[padding] duration-200', collapsed ? 'lg:pl-24' : 'lg:pl-72')}>
        <header className="sticky top-0 z-30 flex h-14 items-center gap-3 border-b bg-background/95 px-4 backdrop-blur lg:hidden">
          <Button variant="ghost" size="icon" onClick={() => setOpen(true)} aria-label="Buka menu">
            <Menu className="h-5 w-5" />
          </Button>
          <span className="text-sm font-semibold">GoKasir Admin</span>
        </header>

        <Sheet open={open} onOpenChange={setOpen}>
          <SheetContent side="left" className="w-72 p-0">
            <SheetTitle className="sr-only">Menu</SheetTitle>
            <SheetDescription className="sr-only">Navigasi admin</SheetDescription>
            {sidebar(false, () => setOpen(false))}
          </SheetContent>
        </Sheet>

        <main className="mx-auto w-full max-w-[1400px] space-y-6 p-4 md:p-8">{children}</main>
      </div>

      <Toaster richColors position="top-right" />
      {flash?.impersonation && <ImpersonationResult key={flash.impersonation.token} data={flash.impersonation} />}
    </div>
  )
}

export function PageHeader({ title, description, actions }) {
  return (
    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h1 className="text-[26px] font-semibold leading-tight tracking-tight">{title}</h1>
        {description && <p className="mt-1.5 text-[15px] text-muted-foreground">{description}</p>}
      </div>
      {actions && <div className="flex flex-wrap items-center gap-3">{actions}</div>}
    </div>
  )
}
