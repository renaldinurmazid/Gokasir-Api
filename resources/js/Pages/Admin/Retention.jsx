import { useEffect, useRef, useState } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import { MessageCircle, Search } from 'lucide-react'
import AdminLayout, { PageHeader } from '@/components/admin/AdminLayout'
import { Pagination } from '@/components/admin/TenantBits'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { cn } from '@/lib/utils'
import { date, number, relativeDays, waNumber } from '@/lib/format'

const THRESHOLDS = [14, 30, 60, 90]

export default function Retention({ tenants, days, q: initialQ, buckets, never_sold }) {
  const [q, setQ] = useState(initialQ)
  const first = useRef(true)
  const visit = (params) =>
    router.get('/admin/retention', { days, q, ...params }, { preserveState: true, preserveScroll: true, replace: true })

  useEffect(() => {
    if (first.current) { first.current = false; return }
    const t = setTimeout(() => visit({ q }), 350)
    return () => clearTimeout(t)
  }, [q])

  return (
    <AdminLayout>
      <Head title="Tenant Tidak Aktif" />
      <PageHeader
        title="Tenant Tidak Aktif"
        description="Tenant aktif yang lama tidak bertransaksi — target follow-up dan retensi. Tenant yang belum pernah bertransaksi dihitung sejak tanggal daftar."
      />

      <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        {THRESHOLDS.map((d) => (
          <button key={d} type="button" onClick={() => visit({ days: d })} className="text-left" aria-pressed={days === d}>
            <Card className={cn('transition-colors hover:bg-muted/50', days === d && 'border-primary ring-1 ring-primary')}>
              <CardHeader className="pb-2">
                <CardDescription>Tanpa transaksi &gt; {d} hari</CardDescription>
                <CardTitle className="text-2xl tabular-nums">{number(buckets[d])}</CardTitle>
              </CardHeader>
              <CardContent className="text-xs text-muted-foreground">tenant</CardContent>
            </Card>
          </button>
        ))}
      </section>

      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p className="text-sm text-muted-foreground">
          Menampilkan tenant tanpa transaksi lebih dari <b>{days} hari</b> · {number(never_sold)} tenant belum pernah bertransaksi sama sekali.
        </p>
        <div className="relative w-full sm:w-72">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input className="pl-8" placeholder="Cari tenant atau telepon…" value={q} onChange={(e) => setQ(e.target.value)} aria-label="Cari tenant" />
        </div>
      </div>

      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Tenant</TableHead>
              <TableHead>Terakhir aktif</TableHead>
              <TableHead className="text-right">Tidak aktif</TableHead>
              <TableHead className="text-right">Total transaksi</TableHead>
              <TableHead className="text-right">Produk</TableHead>
              <TableHead className="text-right">Token</TableHead>
              <TableHead>Login owner terakhir</TableHead>
              <TableHead className="text-right">Hubungi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {tenants.data.length === 0 && (
              <TableRow><TableCell colSpan={8} className="h-24 text-center text-muted-foreground">Tidak ada tenant yang cocok. 🎉</TableCell></TableRow>
            )}
            {tenants.data.map((t) => {
              const wa = waNumber(t.phone)
              const msg = encodeURIComponent(`Halo ${t.owner_name ?? ''}, kami dari GoKasir. Kami lihat toko ${t.business_name} sudah lama tidak bertransaksi. Ada kendala yang bisa kami bantu?`)
              return (
                <TableRow key={t.id}>
                  <TableCell>
                    <Link href={`/admin/tenants/${t.id}`} className="font-medium hover:underline">{t.business_name}</Link>
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                      {t.owner_name ?? '—'}{t.phone ? ` · ${t.phone}` : ''}
                      {!t.is_activated && <Badge variant="outline">Belum aktivasi</Badge>}
                    </div>
                  </TableCell>
                  <TableCell className="whitespace-nowrap">
                    {t.never_sold ? <span className="text-muted-foreground">Belum pernah transaksi</span> : date(t.last_activity_at)}
                  </TableCell>
                  <TableCell className="text-right font-medium tabular-nums">{number(t.days_inactive)} hari</TableCell>
                  <TableCell className="text-right tabular-nums">{number(t.sales_count)}</TableCell>
                  <TableCell className="text-right tabular-nums">{number(t.products_count)}</TableCell>
                  <TableCell className="text-right tabular-nums">{number(t.token_balance)}</TableCell>
                  <TableCell className="whitespace-nowrap text-muted-foreground">{t.owner_last_login ? relativeDays(t.owner_last_login) : 'Belum pernah'}</TableCell>
                  <TableCell className="text-right">
                    {wa ? (
                      <Button asChild variant="outline" size="sm">
                        <a href={`https://wa.me/${wa}?text=${msg}`} target="_blank" rel="noopener noreferrer">
                          <MessageCircle className="mr-2 h-4 w-4" /> WhatsApp
                        </a>
                      </Button>
                    ) : <span className="text-muted-foreground">—</span>}
                  </TableCell>
                </TableRow>
              )
            })}
          </TableBody>
        </Table>
        <Pagination paginator={tenants} />
      </Card>
    </AdminLayout>
  )
}
