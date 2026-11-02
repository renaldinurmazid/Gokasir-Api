import { Head, Link } from '@inertiajs/react'
import { ArrowLeft, Boxes, Coins, Database, HardDrive, ReceiptText, Store as StoreIcon, Users as UsersIcon } from 'lucide-react'
import AdminLayout from '@/components/admin/AdminLayout'
import { StatusBadge, TenantActions } from '@/components/admin/TenantBits'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { bytes, date, dateTime, number, relativeDays, rupiah } from '@/lib/format'

const ACTION_LABEL = {
  'tenant.suspend': 'Suspend tenant',
  'tenant.activate': 'Aktifkan tenant',
  'tenant.delete': 'Hapus tenant',
  'tenant.restore': 'Pulihkan tenant',
  'tenant.impersonate': 'Impersonate owner',
}

function Stat({ title, value, sub, icon: Icon }) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
        <Icon className="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-bold tabular-nums">{value}</div>
        {sub && <div className="mt-1 text-xs text-muted-foreground">{sub}</div>}
      </CardContent>
    </Card>
  )
}

function Field({ label, children }) {
  return (
    <div>
      <dt className="text-xs text-muted-foreground">{label}</dt>
      <dd className="mt-0.5 text-sm font-medium">{children || '—'}</dd>
    </div>
  )
}

export default function Show({ tenant, stores, users, sales, topups, usage, audit, impersonate_minutes }) {
  const totalBytes = usage.db_bytes + usage.file_bytes

  return (
    <AdminLayout>
      <Head title={tenant.business_name} />

      <div className="space-y-3">
        <Link href="/admin/tenants" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
          <ArrowLeft className="h-4 w-4" /> Semua tenant
        </Link>
        <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <h1 className="text-2xl font-semibold tracking-tight">{tenant.business_name}</h1>
              <StatusBadge tenant={tenant} />
              <Badge variant="outline" className="capitalize">{tenant.subscription_plan}</Badge>
            </div>
            <p className="mt-1 text-sm text-muted-foreground">
              Terdaftar {date(tenant.created_at)} · transaksi terakhir {tenant.last_sale_at ? relativeDays(tenant.last_sale_at) : 'belum pernah'}
            </p>
          </div>
          <div className="flex flex-wrap gap-2"><TenantActions tenant={tenant} mode="buttons" /></div>
        </div>
        <p className="text-xs text-muted-foreground">Impersonate menerbitkan token owner yang berlaku {impersonate_minutes} menit.</p>
      </div>

      <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <Stat title="Toko" value={number(tenant.stores_count)} icon={StoreIcon} />
        <Stat title="User" value={number(tenant.users_count)} icon={UsersIcon} />
        <Stat title="Produk" value={number(usage.products)} icon={Boxes} sub={`${number(usage.active_products)} aktif`} />
        <Stat title="Transaksi" value={number(sales.count)} icon={ReceiptText} sub={`${number(sales.count_30d)} dalam 30 hari`} />
        <Stat title="Saldo token" value={number(tenant.token_balance)} icon={Coins} sub={`${number(tenant.token_lifetime_used)} terpakai · ${number(tenant.token_lifetime_topup)} dibeli`} />
      </section>

      <section className="grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader><CardTitle>Profil</CardTitle></CardHeader>
          <CardContent>
            <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              <Field label="Owner">{tenant.owner?.name}</Field>
              <Field label="Telepon owner">{tenant.owner?.phone}</Field>
              <Field label="Email">{tenant.email ?? tenant.owner_email}</Field>
              <Field label="Jenis usaha">{tenant.business_type}</Field>
              <Field label="Telepon usaha">{tenant.phone}</Field>
              <Field label="Berlaku sampai">{tenant.expired_at ? date(tenant.expired_at) : null}</Field>
              <Field label="Login owner terakhir">{tenant.owner?.last_login ? dateTime(tenant.owner.last_login) : 'Belum pernah'}</Field>
              <Field label="Harga token mitra">{tenant.harga_token > 0 ? rupiah(tenant.harga_token) : 'Harga master'}</Field>
              <Field label="Aktivasi">{tenant.is_activated ? 'Sudah aktif' : 'Belum aktivasi'}</Field>
            </dl>
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>Pendapatan dari tenant</CardTitle><CardDescription>Top-up token berstatus lunas</CardDescription></CardHeader>
          <CardContent className="space-y-3">
            <div className="text-2xl font-bold tabular-nums">{rupiah(topups.revenue)}</div>
            <p className="text-sm text-muted-foreground">{number(topups.count)} top-up · {number(topups.tokens)} token</p>
            <div className="border-t pt-3">
              <div className="text-xs text-muted-foreground">Omzet tenant (30 hari / total)</div>
              <div className="text-sm font-medium tabular-nums">{rupiah(sales.revenue_30d)} / {rupiah(sales.revenue)}</div>
            </div>
          </CardContent>
        </Card>
      </section>

      <Tabs defaultValue="usage">
        <TabsList className="h-auto flex-wrap">
          <TabsTrigger value="usage">Pemakaian</TabsTrigger>
          <TabsTrigger value="stores">Toko ({stores.length})</TabsTrigger>
          <TabsTrigger value="users">User ({users.length})</TabsTrigger>
          <TabsTrigger value="audit">Aktivitas admin</TabsTrigger>
        </TabsList>

        <TabsContent value="usage">
          <Card>
            <CardHeader>
              <CardTitle>Pemakaian data</CardTitle>
              <CardDescription>
                Estimasi: jumlah baris tenant × rata-rata ukuran baris tiap tabel. Akurat untuk perbandingan antar tenant,
                bukan ukuran persis di disk.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid gap-4 sm:grid-cols-3">
                <Stat title="Total" value={bytes(totalBytes)} icon={HardDrive} />
                <Stat title="Database (est.)" value={bytes(usage.db_bytes)} icon={Database} sub={`${number(usage.rows)} baris`} />
                <Stat title="Berkas upload" value={bytes(usage.file_bytes)} icon={HardDrive} sub={`${number(usage.file_count)} berkas (foto produk, logo, QRIS)`} />
              </div>
              <Table>
                <TableHeader>
                  <TableRow><TableHead>Tabel</TableHead><TableHead className="text-right">Baris</TableHead><TableHead className="text-right">Ukuran (est.)</TableHead></TableRow>
                </TableHeader>
                <TableBody>
                  {usage.tables.map((t) => (
                    <TableRow key={t.table}>
                      <TableCell className="font-mono text-xs">{t.table}</TableCell>
                      <TableCell className="text-right tabular-nums">{number(t.rows)}</TableCell>
                      <TableCell className="text-right tabular-nums">{bytes(t.bytes)}</TableCell>
                    </TableRow>
                  ))}
                  {usage.tables.length === 0 && <TableRow><TableCell colSpan={3} className="h-16 text-center text-muted-foreground">Belum ada data.</TableCell></TableRow>}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="stores">
          <Card>
            <Table>
              <TableHeader>
                <TableRow><TableHead>Toko</TableHead><TableHead>Kota</TableHead><TableHead className="text-right">User</TableHead><TableHead className="text-right">Transaksi</TableHead><TableHead>Dibuat</TableHead></TableRow>
              </TableHeader>
              <TableBody>
                {stores.map((s) => (
                  <TableRow key={s.id}>
                    <TableCell className="font-medium">{s.name}</TableCell>
                    <TableCell>{[s.city, s.province].filter(Boolean).join(', ') || '—'}</TableCell>
                    <TableCell className="text-right tabular-nums">{number(s.users_count)}</TableCell>
                    <TableCell className="text-right tabular-nums">{number(s.sales_count)}</TableCell>
                    <TableCell className="text-muted-foreground">{date(s.created_at)}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </Card>
        </TabsContent>

        <TabsContent value="users">
          <Card>
            <Table>
              <TableHeader>
                <TableRow><TableHead>Nama</TableHead><TableHead>Peran</TableHead><TableHead>Telepon</TableHead><TableHead>Status</TableHead><TableHead>Login terakhir</TableHead></TableRow>
              </TableHeader>
              <TableBody>
                {users.map((u) => (
                  <TableRow key={u.id}>
                    <TableCell className="font-medium">{u.name}</TableCell>
                    <TableCell className="capitalize">{u.role}</TableCell>
                    <TableCell>{u.phone ?? '—'}</TableCell>
                    <TableCell>{u.status === 1 ? <Badge variant="secondary">Aktif</Badge> : <Badge variant="outline">{u.status === 0 ? 'Belum verifikasi' : 'Nonaktif'}</Badge>}</TableCell>
                    <TableCell className="text-muted-foreground">{u.last_login ? dateTime(u.last_login) : 'Belum pernah'}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </Card>
        </TabsContent>

        <TabsContent value="audit">
          <Card>
            <Table>
              <TableHeader>
                <TableRow><TableHead>Waktu</TableHead><TableHead>Tindakan</TableHead><TableHead>Admin</TableHead><TableHead>Keterangan</TableHead></TableRow>
              </TableHeader>
              <TableBody>
                {audit.map((a) => (
                  <TableRow key={a.id}>
                    <TableCell className="whitespace-nowrap text-muted-foreground">{dateTime(a.created_at)}</TableCell>
                    <TableCell className="font-medium">{ACTION_LABEL[a.action] ?? a.action}</TableCell>
                    <TableCell>{a.admin ?? '—'}</TableCell>
                    <TableCell className="text-muted-foreground">
                      {a.meta?.reason ?? (a.meta?.as_user ? `sebagai ${a.meta.as_user}` : '')}
                    </TableCell>
                  </TableRow>
                ))}
                {audit.length === 0 && <TableRow><TableCell colSpan={4} className="h-16 text-center text-muted-foreground">Belum ada tindakan admin pada tenant ini.</TableCell></TableRow>}
              </TableBody>
            </Table>
          </Card>
        </TabsContent>
      </Tabs>
    </AdminLayout>
  )
}
