import { useEffect, useState } from 'react'
import { Link, router, usePage } from '@inertiajs/react'
import { toast } from 'sonner'
import { LayoutDashboard, Building2, UserX, LogOut, Menu, Store } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Sheet, SheetContent, SheetTitle, SheetDescription } from '@/components/ui/sheet'
import { Toaster } from '@/components/ui/sonner'
import { cn } from '@/lib/utils'
import { ImpersonationResult } from '@/components/admin/TenantBits'

const NAV = [
  { href: '/admin', label: 'Dashboard', icon: LayoutDashboard, match: (u) => u === '/admin' },
  { href: '/admin/tenants', label: 'Tenant', icon: Building2, match: (u) => u.startsWith('/admin/tenants') },
  { href: '/admin/retention', label: 'Tenant Tidak Aktif', icon: UserX, match: (u) => u.startsWith('/admin/retention') },
]

function NavLinks({ url, onNavigate }) {
  const path = url.split('?')[0]
  return (
    <nav className="flex flex-col gap-1">
      {NAV.map(({ href, label, icon: Icon, match }) => (
        <Link
          key={href}
          href={href}
          onClick={onNavigate}
          className={cn(
            'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
            match(path) ? 'bg-secondary text-foreground' : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground'
          )}
        >
          <Icon className="h-4 w-4" />
          {label}
        </Link>
      ))}
    </nav>
  )
}

function Brand() {
  return (
    <div className="flex items-center gap-2 px-3">
      <div className="flex h-8 w-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
        <Store className="h-4 w-4" />
      </div>
      <div className="leading-tight">
        <div className="text-sm font-semibold">GoKasir</div>
        <div className="text-xs text-muted-foreground">Admin Platform</div>
      </div>
    </div>
  )
}

export default function AdminLayout({ children }) {
  const page = usePage()
  const admin = page.props.auth?.admin
  const flashProps = page.props.flash
  const [open, setOpen] = useState(false)

  useEffect(() => {
    if (flashProps?.success) toast.success(flashProps.success)
    if (flashProps?.error) toast.error(flashProps.error)
  }, [flashProps?.success, flashProps?.error])

  const logout = () => router.post('/admin/logout')

  const sidebar = (
    <div className="flex h-full flex-col gap-6 py-4">
      <Brand />
      <div className="px-2"><NavLinks url={page.url} onNavigate={() => setOpen(false)} /></div>
      <div className="mt-auto border-t px-3 pt-4">
        <div className="mb-3 min-w-0">
          <div className="truncate text-sm font-medium">{admin?.name}</div>
          <div className="truncate text-xs text-muted-foreground">{admin?.email}</div>
        </div>
        <Button variant="outline" size="sm" className="w-full justify-start" onClick={logout}>
          <LogOut className="mr-2 h-4 w-4" /> Keluar
        </Button>
      </div>
    </div>
  )

  return (
    <div className="min-h-screen bg-muted/30">
      <aside className="fixed inset-y-0 left-0 hidden w-60 border-r bg-background lg:block">{sidebar}</aside>

      <div className="lg:pl-60">
        <header className="sticky top-0 z-30 flex h-14 items-center gap-3 border-b bg-background/95 px-4 backdrop-blur lg:hidden">
          <Button variant="ghost" size="icon" onClick={() => setOpen(true)} aria-label="Buka menu">
            <Menu className="h-5 w-5" />
          </Button>
          <span className="text-sm font-semibold">GoKasir Admin</span>
        </header>

        <Sheet open={open} onOpenChange={setOpen}>
          <SheetContent side="left" className="w-60 p-0">
            <SheetTitle className="sr-only">Menu</SheetTitle>
            <SheetDescription className="sr-only">Navigasi admin</SheetDescription>
            {sidebar}
          </SheetContent>
        </Sheet>

        <main className="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-8">{children}</main>
      </div>
      <Toaster richColors position="top-right" />
      {flashProps?.impersonation && (
        <ImpersonationResult key={flashProps.impersonation.token} data={flashProps.impersonation} />
      )}
    </div>
  )
}

export function PageHeader({ title, description, actions }) {
  return (
    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
        {description && <p className="mt-1 text-sm text-muted-foreground">{description}</p>}
      </div>
      {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
    </div>
  )
}
