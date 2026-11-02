import { useState } from 'react'
import { Link, router } from '@inertiajs/react'
import { MoreHorizontal, PauseCircle, PlayCircle, Trash2, Undo2, LogIn, Eye, Copy, Check } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
  AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import {
  Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog'
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { dateTime } from '@/lib/format'

export function StatusBadge({ tenant }) {
  if (tenant.deleted) return <Badge variant="outline" className="border-destructive/40 text-destructive">Terhapus</Badge>
  if (tenant.status === 'suspended') return <Badge variant="destructive">Suspended</Badge>
  if (tenant.status === 'expired') return <Badge variant="secondary">Expired</Badge>
  if (!tenant.is_activated) return <Badge variant="outline">Belum aktivasi</Badge>
  return <Badge className="bg-emerald-600 hover:bg-emerald-600">Aktif</Badge>
}

export function Pagination({ paginator }) {
  const { current_page, last_page, from, to, total, prev_page_url, next_page_url } = paginator
  const go = (url) => url && router.visit(url, { preserveScroll: true, preserveState: true })
  return (
    <div className="flex flex-col items-center justify-between gap-3 border-t px-4 py-3 text-sm sm:flex-row">
      <span className="text-muted-foreground">
        {total > 0 ? `Menampilkan ${from}–${to} dari ${total}` : 'Tidak ada data'}
      </span>
      <div className="flex items-center gap-2">
        <span className="text-muted-foreground">Hal. {current_page} / {last_page}</span>
        <Button variant="outline" size="sm" disabled={!prev_page_url} onClick={() => go(prev_page_url)}>Sebelumnya</Button>
        <Button variant="outline" size="sm" disabled={!next_page_url} onClick={() => go(next_page_url)}>Berikutnya</Button>
      </div>
    </div>
  )
}

/** Aksi tenant: suspend / aktifkan / impersonate / hapus / pulihkan — dipakai di daftar (menu) dan detail (tombol). */
export function TenantActions({ tenant, mode = 'menu', onDone }) {
  const [dialog, setDialog] = useState(null) // 'suspend' | 'delete' | 'impersonate'
  const [reason, setReason] = useState('')
  const [confirmName, setConfirmName] = useState('')
  const [busy, setBusy] = useState(false)
  const [errors, setErrors] = useState({})

  const close = () => { setDialog(null); setReason(''); setConfirmName(''); setErrors({}) }
  const run = (method, url, data = {}) => {
    setBusy(true)
    const opts = {
      preserveScroll: true,
      onSuccess: () => { close(); onDone?.() },
      onError: (e) => setErrors(e),
      onFinish: () => setBusy(false),
    }
    // router.delete(url, options) tidak punya argumen data terpisah
    if (method === 'delete') router.delete(url, { ...data, ...opts })
    else router[method](url, data, opts)
  }
  const base = `/admin/tenants/${tenant.id}`
  const isActive = tenant.status === 'active'
  const suspended = tenant.status === 'suspended'

  const items = tenant.deleted
    ? [{ key: 'restore', label: 'Pulihkan', icon: Undo2, onClick: () => run('post', `${base}/restore`) }]
    : [
        { key: 'impersonate', label: 'Impersonate', icon: LogIn, disabled: !isActive, onClick: () => setDialog('impersonate') },
        suspended
          ? { key: 'activate', label: 'Aktifkan', icon: PlayCircle, onClick: () => run('post', `${base}/activate`) }
          : { key: 'suspend', label: 'Suspend', icon: PauseCircle, onClick: () => setDialog('suspend') },
        { key: 'delete', label: 'Hapus', icon: Trash2, destructive: true, onClick: () => setDialog('delete') },
      ]

  return (
    <>
      {mode === 'menu' ? (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon" aria-label={`Aksi untuk ${tenant.business_name}`}>
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem asChild>
              <Link href={base}><Eye className="mr-2 h-4 w-4" /> Lihat detail</Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            {items.map((i) => (
              <DropdownMenuItem key={i.key} disabled={i.disabled} onSelect={i.onClick}
                className={i.destructive ? 'text-destructive focus:text-destructive' : ''}>
                <i.icon className="mr-2 h-4 w-4" /> {i.label}
              </DropdownMenuItem>
            ))}
          </DropdownMenuContent>
        </DropdownMenu>
      ) : (
        <>
          {items.map((i) => (
            <Button key={i.key} variant={i.destructive ? 'outline' : 'outline'} size="sm" disabled={i.disabled || busy}
              className={i.destructive ? 'text-destructive hover:text-destructive' : ''} onClick={i.onClick}
              title={i.disabled ? 'Hanya tenant berstatus aktif yang bisa di-impersonate' : undefined}>
              <i.icon className="mr-2 h-4 w-4" /> {i.label}
            </Button>
          ))}
        </>
      )}

      {/* Suspend */}
      <AlertDialog open={dialog === 'suspend'} onOpenChange={(o) => !o && close()}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Suspend {tenant.business_name}?</AlertDialogTitle>
            <AlertDialogDescription>
              Semua user tenant ini akan ditolak aksesnya dan sesi aplikasi mereka langsung dicabut. Data tidak dihapus,
              dan tenant bisa diaktifkan kembali kapan saja.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <div className="space-y-2">
            <Label htmlFor="reason">Alasan (opsional, tercatat di audit log)</Label>
            <Input id="reason" value={reason} maxLength={255} onChange={(e) => setReason(e.target.value)} />
          </div>
          <AlertDialogFooter>
            <AlertDialogCancel>Batal</AlertDialogCancel>
            <AlertDialogAction disabled={busy} onClick={(e) => { e.preventDefault(); run('post', `${base}/suspend`, { reason }) }}>
              Suspend
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Hapus — wajib ketik nama tenant */}
      <Dialog open={dialog === 'delete'} onOpenChange={(o) => !o && close()}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Hapus {tenant.business_name}?</DialogTitle>
            <DialogDescription>
              Tenant disembunyikan dan seluruh sesi usernya dicabut. Data tidak dihapus permanen — tenant bisa
              dipulihkan lewat filter “Terhapus”.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2">
            <Label htmlFor="confirm">Ketik <b>{tenant.business_name}</b> untuk konfirmasi</Label>
            <Input id="confirm" value={confirmName} onChange={(e) => setConfirmName(e.target.value)} autoComplete="off" />
            {errors.confirm && <p className="text-sm text-destructive">{errors.confirm}</p>}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={close}>Batal</Button>
            <Button variant="destructive" disabled={busy || confirmName !== tenant.business_name}
              onClick={() => run('delete', base, { data: { confirm: confirmName } })}>
              Hapus tenant
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Impersonate */}
      <AlertDialog open={dialog === 'impersonate'} onOpenChange={(o) => !o && close()}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Impersonate {tenant.business_name}?</AlertDialogTitle>
            <AlertDialogDescription>
              Sistem akan menerbitkan token API berumur pendek atas nama owner tenant. Tindakan ini tercatat di audit
              log. Gunakan hanya untuk troubleshooting.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Batal</AlertDialogCancel>
            <AlertDialogAction disabled={busy} onClick={(e) => { e.preventDefault(); run('post', `${base}/impersonate`) }}>
              Terbitkan token
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  )
}

/** Dialog hasil impersonate — dirender di layout, membaca flash `impersonation` (ditampilkan sekali). */
export function ImpersonationResult({ data }) {
  const [open, setOpen] = useState(true)
  const [copied, setCopied] = useState(null)
  if (!data) return null

  const copy = async (key, text) => {
    try { await navigator.clipboard.writeText(text); setCopied(key); setTimeout(() => setCopied(null), 1500) } catch { /* clipboard diblokir */ }
  }
  const curl = `curl -H "Authorization: Bearer ${data.token}" -H "Accept: application/json" ${data.api_url}/auth/me`

  const Row = ({ k, label, text }) => (
    <div className="space-y-1">
      <Label>{label}</Label>
      <div className="flex items-start gap-2">
        <code className="flex-1 break-all rounded-md bg-muted p-2 text-xs">{text}</code>
        <Button type="button" variant="outline" size="icon" onClick={() => copy(k, text)} aria-label={`Salin ${label}`}>
          {copied === k ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
        </Button>
      </div>
    </div>
  )

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogContent className="max-w-xl">
        <DialogHeader>
          <DialogTitle>Token impersonate siap</DialogTitle>
          <DialogDescription>
            Bertindak sebagai <b>{data.user.name}</b> (owner {data.tenant}). Berlaku sampai {dateTime(data.expires_at)}.
            Token hanya ditampilkan sekali — salin sekarang.
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4">
          <Row k="token" label="Bearer token" text={data.token} />
          <Row k="curl" label="Coba lewat API" text={curl} />
        </div>
        <DialogFooter><Button onClick={() => setOpen(false)}>Selesai</Button></DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
