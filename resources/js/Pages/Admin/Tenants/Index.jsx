import { useEffect, useRef, useState } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import { ArrowDown, ArrowUp, ArrowUpDown, Search } from 'lucide-react'
import AdminLayout, { PageHeader } from '@/components/admin/AdminLayout'
import { Pagination, StatusBadge, TenantActions } from '@/components/admin/TenantBits'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { bytes, date, number, relativeDays } from '@/lib/format'

const STATUS_TABS = [
  ['all', 'Semua'], ['active', 'Aktif'], ['suspended', 'Suspended'], ['expired', 'Expired'], ['deleted', 'Terhapus'],
]

export default function Index({ tenants, filters, counts }) {
  const [q, setQ] = useState(filters.q)
  const first = useRef(true)

  const visit = (params) =>
    router.get('/admin/tenants', { ...filters, ...params }, { preserveState: true, preserveScroll: true, replace: true })

  // pencarian dengan jeda agar tidak request di tiap ketikan
  useEffect(() => {
    if (first.current) { first.current = false; return }
    const t = setTimeout(() => visit({ q }), 350)
    return () => clearTimeout(t)
  }, [q])

  const sortBy = (key) =>
    visit({ sort: key, dir: filters.sort === key && filters.dir === 'desc' ? 'asc' : 'desc' })

  const SortHead = ({ k, children, className }) => {
    const Icon = filters.sort !== k ? ArrowUpDown : filters.dir === 'asc' ? ArrowUp : ArrowDown
    return (
      <TableHead className={className}>
        <button type="button" onClick={() => sortBy(k)} className="inline-flex items-center gap-1 hover:text-foreground">
          {children} <Icon className="h-3 w-3 opacity-60" />
        </button>
      </TableHead>
    )
  }

  return (
    <AdminLayout>
      <Head title="Tenant" />
      <PageHeader title="Manajemen Tenant" description="Seluruh pelanggan SaaS beserta penggunaannya." />

      <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <Tabs value={filters.status} onValueChange={(v) => visit({ status: v })}>
          <TabsList className="h-auto flex-wrap">
            {STATUS_TABS.map(([v, l]) => (
              <TabsTrigger key={v} value={v}>{l} <span className="ml-1.5 text-xs text-muted-foreground">{number(counts[v])}</span></TabsTrigger>
            ))}
          </TabsList>
        </Tabs>
        <div className="relative w-full lg:w-72">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input className="pl-8" placeholder="Cari tenant, owner, telepon…" value={q} onChange={(e) => setQ(e.target.value)} aria-label="Cari tenant" />
        </div>
      </div>

      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <SortHead k="business_name">Tenant</SortHead>
              <TableHead>Status</TableHead>
              <SortHead k="stores" className="text-right">Toko</SortHead>
              <SortHead k="users" className="text-right">User</SortHead>
              <SortHead k="products" className="text-right">Produk</SortHead>
              <SortHead k="sales" className="text-right">Transaksi</SortHead>
              <TableHead className="text-right">Data (est.)</TableHead>
              <SortHead k="last_sale">Transaksi terakhir</SortHead>
              <SortHead k="created_at">Terdaftar</SortHead>
              <TableHead className="w-10"><span className="sr-only">Aksi</span></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {tenants.data.length === 0 && (
              <TableRow><TableCell colSpan={10} className="h-24 text-center text-muted-foreground">Tidak ada tenant yang cocok.</TableCell></TableRow>
            )}
            {tenants.data.map((t) => (
              <TableRow key={t.id}>
                <TableCell>
                  <Link href={`/admin/tenants/${t.id}`} className="font-medium hover:underline">{t.business_name}</Link>
                  <div className="text-xs text-muted-foreground">{t.owner?.name ?? '—'}{t.owner?.phone ? ` · ${t.owner.phone}` : ''}</div>
                </TableCell>
                <TableCell><StatusBadge tenant={t} /></TableCell>
                <TableCell className="text-right tabular-nums">{number(t.stores_count)}</TableCell>
                <TableCell className="text-right tabular-nums">{number(t.users_count)}</TableCell>
                <TableCell className="text-right tabular-nums">{number(t.products_count)}</TableCell>
                <TableCell className="text-right tabular-nums">{number(t.sales_count)}</TableCell>
                <TableCell className="text-right tabular-nums" title={`${number(t.usage.rows)} baris`}>{bytes(t.usage.db_bytes)}</TableCell>
                <TableCell className="whitespace-nowrap text-muted-foreground">{t.last_sale_at ? relativeDays(t.last_sale_at) : 'Belum pernah'}</TableCell>
                <TableCell className="whitespace-nowrap text-muted-foreground">{date(t.created_at)}</TableCell>
                <TableCell><TenantActions tenant={t} /></TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
        <Pagination paginator={tenants} />
      </Card>
    </AdminLayout>
  )
}
