import { Head, Link, router } from '@inertiajs/react'
import { Area, AreaChart, Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts'
import { Building2, Store, Users, Wallet, TrendingUp, TrendingDown, UserPlus, Activity, UserX, PauseCircle } from 'lucide-react'
import AdminLayout, { PageHeader } from '@/components/admin/AdminLayout'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import {
  ChartContainer, ChartLegend, ChartLegendContent, ChartTooltip, ChartTooltipContent,
} from '@/components/ui/chart'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { cn } from '@/lib/utils'
import { compactNumber, number, rupiah } from '@/lib/format'
import { useState } from 'react'

function Growth({ pct, thisMonth }) {
  if (pct === null || pct === undefined) {
    return <span>{thisMonth > 0 ? `+${number(thisMonth)} bulan ini` : 'Belum ada tambahan bulan ini'}</span>
  }
  const up = pct >= 0
  const Icon = up ? TrendingUp : TrendingDown
  return (
    <span className="flex items-center gap-1">
      <Icon className={cn('h-3.5 w-3.5', up ? 'text-emerald-600' : 'text-destructive')} />
      <span className={up ? 'text-emerald-700' : 'text-destructive'}>{up ? '+' : ''}{pct}%</span>
      <span>· +{number(thisMonth)} bulan ini</span>
    </span>
  )
}

function StatCard({ title, value, icon: Icon, children, href }) {
  const body = (
    <Card className={cn(href && 'transition-colors hover:bg-muted/50')}>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
        <Icon className="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-bold tabular-nums">{value}</div>
        <div className="mt-1 text-xs text-muted-foreground">{children}</div>
      </CardContent>
    </Card>
  )
  return href ? <Link href={href}>{body}</Link> : body
}

function PeriodCard({ label, count, revenue }) {
  return (
    <Card>
      <CardHeader className="pb-2">
        <CardDescription>{label}</CardDescription>
        <CardTitle className="text-2xl tabular-nums">{rupiah(revenue)}</CardTitle>
      </CardHeader>
      <CardContent className="text-sm text-muted-foreground">{number(count)} transaksi</CardContent>
    </Card>
  )
}

const shortDate = (d) => {
  const [, m, day] = d.split('-')
  return `${Number(day)}/${Number(m)}`
}
const longDate = (d) =>
  new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' })

function TrendCard({ title, description, data, metrics }) {
  const [key, setKey] = useState(metrics[0].key)
  const metric = metrics.find((m) => m.key === key)
  const config = { [key]: { label: metric.label, color: 'hsl(var(--chart-1))' } }
  const Chart = metric.type === 'area' ? AreaChart : BarChart

  return (
    <Card>
      <CardHeader className="flex flex-col gap-3 space-y-0 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <CardTitle>{title}</CardTitle>
          <CardDescription>{description}</CardDescription>
        </div>
        <Tabs value={key} onValueChange={setKey}>
          <TabsList>
            {metrics.map((m) => <TabsTrigger key={m.key} value={m.key}>{m.label}</TabsTrigger>)}
          </TabsList>
        </Tabs>
      </CardHeader>
      <CardContent>
        <ChartContainer config={config} className="aspect-auto h-[260px] w-full">
          <Chart data={data} margin={{ left: 4, right: 8, top: 8 }}>
            <CartesianGrid vertical={false} />
            <XAxis dataKey="date" tickLine={false} axisLine={false} tickMargin={8} minTickGap={24} tickFormatter={shortDate} />
            <YAxis tickLine={false} axisLine={false} width={48} tickFormatter={compactNumber} />
            <ChartTooltip
              cursor={metric.type === 'area'}
              content={
                <ChartTooltipContent
                  indicator="line"
                  labelFormatter={(_, p) => longDate(p[0].payload.date)}
                  formatter={(v) => (
                    <div className="flex w-full items-center justify-between gap-6">
                      <span className="text-muted-foreground">{metric.label}</span>
                      <span className="font-mono font-medium tabular-nums">{metric.format(v)}</span>
                    </div>
                  )}
                />
              }
            />
            {metric.type === 'area' ? (
              <Area dataKey={key} type="monotone" stroke={`var(--color-${key})`} strokeWidth={2}
                fill={`var(--color-${key})`} fillOpacity={0.15} dot={false} activeDot={{ r: 4, strokeWidth: 2, stroke: 'hsl(var(--background))' }} />
            ) : (
              <Bar dataKey={key} fill={`var(--color-${key})`} radius={[4, 4, 0, 0]} maxBarSize={24} />
            )}
          </Chart>
        </ChartContainer>
      </CardContent>
    </Card>
  )
}

const growthConfig = {
  tenants_new: { label: 'Tenant', color: 'hsl(var(--chart-1))' },
  stores_new:  { label: 'Toko',   color: 'hsl(var(--chart-2))' },
  users_new:   { label: 'User',   color: 'hsl(var(--chart-3))' },
}

export default function Dashboard({ stats }) {
  const { totals, tenants, sales, topups, daily, monthly, range } = stats

  const changeRange = (v) => router.get('/admin', { range: v }, { preserveState: true, preserveScroll: true, replace: true })

  return (
    <AdminLayout>
      <Head title="Dashboard" />
      <PageHeader
        title="Dashboard"
        description="Ringkasan seluruh platform. Hari, minggu, dan bulan dihitung dalam WIB."
        actions={
          <Select value={String(range)} onValueChange={changeRange}>
            <SelectTrigger className="w-[170px]" aria-label="Rentang grafik"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="7">7 hari terakhir</SelectItem>
              <SelectItem value="30">30 hari terakhir</SelectItem>
              <SelectItem value="90">90 hari terakhir</SelectItem>
            </SelectContent>
          </Select>
        }
      />

      {/* Total & pertumbuhan */}
      <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard title="Total Tenant" value={number(totals.tenants.total)} icon={Building2} href="/admin/tenants">
          <Growth pct={totals.tenants.growth_pct} thisMonth={totals.tenants.this_month} />
        </StatCard>
        <StatCard title="Total Toko" value={number(totals.stores.total)} icon={Store}>
          <Growth pct={totals.stores.growth_pct} thisMonth={totals.stores.this_month} />
        </StatCard>
        <StatCard title="Total User" value={number(totals.users.total)} icon={Users}>
          <Growth pct={totals.users.growth_pct} thisMonth={totals.users.this_month} />
        </StatCard>
        <StatCard title="Pendapatan Top-up (Bulan Ini)" value={rupiah(topups.month.revenue)} icon={Wallet}>
          Sepanjang waktu {rupiah(topups.all.revenue)}
        </StatCard>
      </section>

      {/* Transaksi & omzet platform */}
      <section className="space-y-3">
        <h2 className="text-sm font-semibold text-muted-foreground">Transaksi & omzet seluruh platform</h2>
        <div className="grid gap-4 md:grid-cols-3">
          <PeriodCard label="Hari ini" {...sales.today} />
          <PeriodCard label="Minggu ini" {...sales.week} />
          <PeriodCard label="Bulan ini" {...sales.month} />
        </div>
      </section>

      {/* Kesehatan tenant */}
      <section className="space-y-3">
        <h2 className="text-sm font-semibold text-muted-foreground">Kesehatan tenant</h2>
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <StatCard title="Tenant Baru (30 hari)" value={number(tenants.new_30d)} icon={UserPlus}>
            Mendaftar dalam 30 hari terakhir
          </StatCard>
          <StatCard title="Tenant Aktif" value={number(tenants.active)} icon={Activity}>
            Ada transaksi dalam {tenants.active_days} hari terakhir
          </StatCard>
          <StatCard title="Tenant Tidak Aktif" value={number(tenants.inactive)} icon={UserX} href="/admin/retention">
            Tanpa transaksi {tenants.active_days} hari · lihat daftar follow-up
          </StatCard>
          <StatCard title="Tenant Suspended" value={number(tenants.suspended)} icon={PauseCircle} href="/admin/tenants?status=suspended">
            Akses dinonaktifkan
          </StatCard>
        </div>
      </section>

      {/* Grafik */}
      <section className="grid gap-4 xl:grid-cols-2">
        <TrendCard
          title="Tren transaksi"
          description={`Seluruh tenant, ${range} hari terakhir`}
          data={daily}
          metrics={[
            { key: 'revenue', label: 'Omzet', type: 'area', format: rupiah },
            { key: 'transactions', label: 'Jumlah transaksi', type: 'bar', format: number },
          ]}
        />
        <TrendCard
          title="Tren top-up token"
          description={`Top-up berstatus lunas, ${range} hari terakhir`}
          data={daily}
          metrics={[
            { key: 'topup_revenue', label: 'Pendapatan', type: 'bar', format: rupiah },
            { key: 'topup_count', label: 'Jumlah top-up', type: 'bar', format: number },
          ]}
        />
      </section>

      <section className="grid gap-4 xl:grid-cols-3">
        <Card className="xl:col-span-2">
          <CardHeader>
            <CardTitle>Pertumbuhan per bulan</CardTitle>
            <CardDescription>Tenant, toko, dan user baru dalam 12 bulan terakhir</CardDescription>
          </CardHeader>
          <CardContent>
            <ChartContainer config={growthConfig} className="aspect-auto h-[280px] w-full">
              <BarChart data={monthly} margin={{ left: 4, right: 8, top: 8 }}>
                <CartesianGrid vertical={false} />
                <XAxis dataKey="label" tickLine={false} axisLine={false} tickMargin={8} />
                <YAxis tickLine={false} axisLine={false} width={32} allowDecimals={false} />
                <ChartTooltip content={<ChartTooltipContent />} />
                <ChartLegend content={<ChartLegendContent />} />
                <Bar dataKey="tenants_new" fill="var(--color-tenants_new)" radius={[4, 4, 0, 0]} maxBarSize={14} />
                <Bar dataKey="stores_new" fill="var(--color-stores_new)" radius={[4, 4, 0, 0]} maxBarSize={14} />
                <Bar dataKey="users_new" fill="var(--color-users_new)" radius={[4, 4, 0, 0]} maxBarSize={14} />
              </BarChart>
            </ChartContainer>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Pendapatan top-up</CardTitle>
            <CardDescription>
              {topups.pending > 0 ? `${number(topups.pending)} top-up menunggu pembayaran` : 'Top-up berstatus lunas'}
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Periode</TableHead>
                  <TableHead className="text-right">Top-up</TableHead>
                  <TableHead className="text-right">Pendapatan</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {[['Hari ini', topups.today], ['Minggu ini', topups.week], ['Bulan ini', topups.month], ['Semua', topups.all]].map(([l, t]) => (
                  <TableRow key={l}>
                    <TableCell className="font-medium">{l}</TableCell>
                    <TableCell className="text-right tabular-nums">{number(t.count)}</TableCell>
                    <TableCell className="text-right tabular-nums">{rupiah(t.revenue)}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
            <p className="mt-3 text-xs text-muted-foreground">
              {number(topups.all.tokens)} token terjual sepanjang waktu · {number(totals.sales_partners)} mitra sales tidak dihitung sebagai tenant.
            </p>
          </CardContent>
        </Card>
      </section>
    </AdminLayout>
  )
}
