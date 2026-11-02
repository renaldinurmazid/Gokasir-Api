import { useId, useState } from 'react'
import { Head, Link, router, usePage } from '@inertiajs/react'
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts'
import { CalendarDays, Download, TrendingDown, TrendingUp } from 'lucide-react'
import AdminLayout, { PageHeader } from '@/components/admin/AdminLayout'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import {
  ChartContainer, ChartLegend, ChartLegendContent, ChartTooltip, ChartTooltipContent,
} from '@/components/ui/chart'
import { cn } from '@/lib/utils'
import { compactNumber, number, rupiah } from '@/lib/format'

// Warna penanda kategori (slot kategorikal tervalidasi + warna merek)
const C = {
  brand: 'hsl(var(--primary))',
  yellow: '#eda100',
  violet: '#6250d6',
  aqua: '#1baf7a',
}

function Panel({ title, action, children, className }) {
  return (
    <Card className={cn('rounded-3xl p-6', className)}>
      <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h2 className="text-lg font-semibold tracking-tight">{title}</h2>
        {action}
      </div>
      {children}
    </Card>
  )
}

function Pill({ children, tone = 'neutral', className }) {
  const tones = {
    neutral: 'border-border bg-background text-foreground/70',
    up: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    down: 'border-red-200 bg-red-50 text-red-700',
  }
  return (
    <span className={cn('inline-flex items-center gap-1 whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-medium', tones[tone], className)}>
      {children}
    </span>
  )
}

function GrowthPill({ pct, thisMonth }) {
  if (pct === null || pct === undefined) {
    return <Pill>{thisMonth > 0 ? `+${number(thisMonth)} bulan ini` : 'Belum ada tambahan'}</Pill>
  }
  const up = pct >= 0
  const Icon = up ? TrendingUp : TrendingDown
  return (
    <Pill tone={up ? 'up' : 'down'}>
      <Icon className="h-3.5 w-3.5" /> {up ? '+' : ''}{pct}%
    </Pill>
  )
}

function Tile({ color, label, value, unit, aside, sub, href }) {
  const body = (
    <div className={cn('rounded-2xl border bg-muted/40 p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)]', href && 'transition-colors hover:bg-muted/70')}>
      <div className="flex items-center gap-2 text-[15px] text-foreground/80">
        <span className="h-3 w-3 rounded-[3px]" style={{ background: color }} />
        {label}
      </div>
      <div className="mt-4 flex flex-wrap items-end justify-between gap-2">
        <div className="flex items-baseline gap-1.5">
          <span className="text-[32px] font-semibold leading-none tracking-tight tabular-nums">{value}</span>
          {unit && <span className="text-sm text-muted-foreground">{unit}</span>}
        </div>
        {aside}
      </div>
      {sub && <div className="mt-2 text-xs text-muted-foreground">{sub}</div>}
    </div>
  )
  return href ? <Link href={href} className="block">{body}</Link> : body
}

/** Batang proporsi bergaris-garis dengan label persen di atas tiap segmen. */
function SegmentedBar({ segments }) {
  const total = segments.reduce((s, x) => s + x.value, 0)
  const visible = segments.filter((s) => s.value > 0)
  if (total === 0) return <div className="h-14 rounded-xl bg-muted" />

  return (
    <div className="flex gap-1.5" role="img" aria-label={segments.map((s) => `${s.label} ${s.value}`).join(', ')}>
      {visible.map((s) => {
        const pct = (s.value / total) * 100
        return (
          <div key={s.key} style={{ flexGrow: s.value, flexBasis: 0, minWidth: 6 }}>
            <div className="mb-1 h-5 truncate text-sm tabular-nums text-foreground/80">
              {pct >= 9 ? `${Math.round(pct)}%` : ''}
            </div>
            <div
              className="h-11"
              style={{
                borderTop: `2px solid ${s.color}`,
                backgroundImage: `repeating-linear-gradient(90deg, ${s.color} 0 2.5px, transparent 2.5px 6px)`,
                backgroundPosition: 'left bottom',
                backgroundSize: '100% calc(100% - 2px)',
                backgroundRepeat: 'no-repeat',
              }}
            />
          </div>
        )
      })}
    </div>
  )
}

const shortDate = (d) => {
  const [, m, day] = d.split('-')
  return `${Number(day)}/${Number(m)}`
}
const longDate = (d) =>
  new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' })

/** Grafik batang seri tunggal dengan gradasi merek. */
function BarTrend({ data, dataKey, label, format, height = 260 }) {
  const gid = useId().replace(/:/g, '')
  const config = { [dataKey]: { label, color: C.brand } }

  return (
    <ChartContainer config={config} className="aspect-auto w-full" style={{ height }}>
      <BarChart data={data} margin={{ left: 0, right: 4, top: 8 }} barCategoryGap="22%">
        <defs>
          <linearGradient id={gid} x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stopColor="hsl(var(--bar-from))" />
            <stop offset="100%" stopColor="hsl(var(--bar-to))" />
          </linearGradient>
        </defs>
        <CartesianGrid vertical={false} strokeDasharray="3 3" />
        <XAxis dataKey="date" tickLine={false} axisLine={false} tickMargin={10} minTickGap={22} tickFormatter={shortDate} />
        <YAxis tickLine={false} axisLine={false} width={44} tickFormatter={compactNumber} />
        <ChartTooltip
          cursor={{ fill: 'hsl(var(--muted))', opacity: 0.6 }}
          content={
            <ChartTooltipContent
              indicator="line"
              labelFormatter={(_, p) => longDate(p[0].payload.date)}
              formatter={(v) => (
                <div className="flex w-full items-center justify-between gap-6">
                  <span className="text-muted-foreground">{label}</span>
                  <span className="font-mono font-medium tabular-nums">{format(v)}</span>
                </div>
              )}
            />
          }
        />
        <Bar dataKey={dataKey} fill={`url(#${gid})`} radius={[8, 8, 3, 3]} maxBarSize={22} />
      </BarChart>
    </ChartContainer>
  )
}

function TrendPanel({ title, headline, headlineSub, data, metrics }) {
  const [key, setKey] = useState(metrics[0].key)
  const metric = metrics.find((m) => m.key === key)

  return (
    <Panel
      title={title}
      action={
        <Tabs value={key} onValueChange={setKey}>
          <TabsList>
            {metrics.map((m) => <TabsTrigger key={m.key} value={m.key}>{m.label}</TabsTrigger>)}
          </TabsList>
        </Tabs>
      }
    >
      <div className="mb-4">
        <div className="text-[34px] font-semibold leading-none tracking-tight tabular-nums">{headline(key)}</div>
        <p className="mt-2 text-sm text-muted-foreground">{headlineSub}</p>
      </div>
      <BarTrend data={data} dataKey={key} label={metric.label} format={metric.format} />
    </Panel>
  )
}

const growthConfig = {
  tenants_new: { label: 'Tenant', color: 'hsl(var(--chart-1))' },
  stores_new:  { label: 'Toko',   color: 'hsl(var(--chart-2))' },
  users_new:   { label: 'User',   color: 'hsl(var(--chart-3))' },
}

function exportCsv(daily, range) {
  const head = ['tanggal', 'transaksi', 'omzet', 'topup_lunas', 'pendapatan_topup']
  const rows = daily.map((d) => [d.date, d.transactions, d.revenue, d.topup_count, d.topup_revenue])
  const csv = [head, ...rows].map((r) => r.join(',')).join('\n')
  const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }))
  const a = document.createElement('a')
  a.href = url
  a.download = `gokasir-platform-${range}-hari.csv`
  a.click()
  URL.revokeObjectURL(url)
}

export default function Dashboard({ stats }) {
  const { totals, tenants, sales, topups, daily, monthly, range } = stats
  const admin = usePage().props.auth?.admin
  const firstName = (admin?.name ?? '').split(' ')[0]

  const changeRange = (v) => router.get('/admin', { range: v }, { preserveState: true, preserveScroll: true, replace: true })

  const segments = [
    { key: 'active',    label: 'Aktif',               value: tenants.active,    color: C.aqua,   href: '/admin/tenants' },
    { key: 'inactive',  label: 'Tidak aktif',         value: tenants.inactive,  color: C.yellow, href: '/admin/retention' },
    { key: 'fresh',     label: 'Baru, belum transaksi', value: tenants.fresh,   color: C.violet },
    { key: 'suspended', label: 'Suspended',           value: tenants.suspended, color: C.brand,  href: '/admin/tenants?status=suspended' },
  ]

  const topupRows = [
    ['Hari ini', topups.today], ['Minggu ini', topups.week], ['Bulan ini', topups.month], ['Sepanjang waktu', topups.all],
  ]

  return (
    <AdminLayout>
      <Head title="Dashboard" />
      <PageHeader
        title={firstName ? `Halo, ${firstName}` : 'Dashboard'}
        description="Pantau, evaluasi, dan kembangkan bisnis SaaS GoKasir Anda."
        actions={
          <>
            <Select value={String(range)} onValueChange={changeRange}>
              <SelectTrigger className="h-11 w-[200px] gap-2 rounded-xl shadow-sm" aria-label="Rentang grafik">
                <CalendarDays className="h-4 w-4 shrink-0 text-muted-foreground" />
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="7">7 hari terakhir</SelectItem>
                <SelectItem value="30">30 hari terakhir</SelectItem>
                <SelectItem value="90">90 hari terakhir</SelectItem>
              </SelectContent>
            </Select>
            {/* <Button className="h-11 rounded-xl px-5 shadow-sm" onClick={() => exportCsv(daily, range)}>
              <Download className="h-4 w-4" /> Export CSV
            </Button> */}
          </>
        }
      />

      <div className="grid items-start gap-6 xl:grid-cols-[1.1fr_1fr]">
        {/* ── Kolom kiri ── */}
        <div className="space-y-6">
          <Panel title="Analitik Platform">
            <div className="grid gap-4 sm:grid-cols-2">
              <Tile color={C.brand} label="Total Tenant" value={number(totals.tenants.total)} unit="tenant" href="/admin/tenants"
                aside={<GrowthPill pct={totals.tenants.growth_pct} thisMonth={totals.tenants.this_month} />}
                sub={`+${number(totals.tenants.this_month)} bulan ini`} />
              <Tile color={C.yellow} label="Total Toko" value={number(totals.stores.total)} unit="toko"
                aside={<GrowthPill pct={totals.stores.growth_pct} thisMonth={totals.stores.this_month} />}
                sub={`+${number(totals.stores.this_month)} bulan ini`} />
              <Tile color={C.violet} label="Total User" value={number(totals.users.total)} unit="user"
                aside={<GrowthPill pct={totals.users.growth_pct} thisMonth={totals.users.this_month} />}
                sub={`+${number(totals.users.this_month)} bulan ini · ${number(totals.sales_partners)} mitra sales tidak dihitung`} />
              <Tile color={C.aqua} label="Top-up Bulan Ini" value={rupiah(topups.month.revenue)}
                sub={`${number(topups.month.count)} top-up lunas · total ${rupiah(topups.all.revenue)}`} />
            </div>

            <div className="mt-7 flex flex-wrap items-baseline gap-x-3 gap-y-1">
              <span className="text-[32px] font-semibold leading-none tabular-nums">{number(tenants.total)}</span>
              <span className="text-sm text-muted-foreground">
                Total tenant · {number(tenants.new_30d)} baru dalam 30 hari
              </span>
            </div>
            <div className="mt-4"><SegmentedBar segments={segments} /></div>
            <ul className="mt-4 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
              {segments.map((s) => {
                const row = (
                  <span className="flex items-center gap-2">
                    <span className="h-2.5 w-2.5 rounded-[3px]" style={{ background: s.color }} />
                    <span className="text-foreground/80">{s.label}</span>
                    <span className="ml-auto font-medium tabular-nums">{number(s.value)}</span>
                  </span>
                )
                return (
                  <li key={s.key}>
                    {s.href ? <Link href={s.href} className="block rounded-md hover:bg-muted/60">{row}</Link> : row}
                  </li>
                )
              })}
            </ul>
            <p className="mt-3 text-xs text-muted-foreground">
              Aktif = ada transaksi dalam {tenants.active_days} hari terakhir. Tidak aktif = tanpa transaksi {tenants.active_days} hari dan sudah terdaftar lebih lama dari itu.
            </p>
          </Panel>

          <Panel title="Transaksi Seluruh Platform">
            <div className="space-y-3">
              {[['Hari ini', sales.today], ['Minggu ini', sales.week], ['Bulan ini', sales.month]].map(([label, s]) => (
                <div key={label} className="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-2xl border bg-background px-5 py-4 shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
                  <div className="w-28 text-[15px] text-muted-foreground">{label}</div>
                  <div className="flex-1 text-2xl font-semibold tabular-nums tracking-tight">{rupiah(s.revenue)}</div>
                  <Pill>{number(s.count)} transaksi</Pill>
                </div>
              ))}
            </div>
            <p className="mt-3 text-xs text-muted-foreground">Hari, minggu (mulai Senin), dan bulan dihitung dalam WIB.</p>
          </Panel>
        </div>

        {/* ── Kolom kanan ── */}
        <div className="space-y-6">
          <TrendPanel
            title="Tren Transaksi"
            data={daily}
            headline={(k) => (k === 'revenue' ? rupiah(sales.month.revenue) : number(sales.month.count))}
            headlineSub={`Bulan ini · grafik ${range} hari terakhir`}
            metrics={[
              { key: 'revenue', label: 'Omzet', format: rupiah },
              { key: 'transactions', label: 'Transaksi', format: number },
            ]}
          />

          <TrendPanel
            title="Tren Top-up Token"
            data={daily}
            headline={(k) => (k === 'topup_revenue' ? rupiah(topups.month.revenue) : number(topups.month.count))}
            headlineSub={
              topups.pending > 0
                ? `Bulan ini · ${number(topups.pending)} top-up menunggu pembayaran`
                : `Bulan ini · grafik ${range} hari terakhir`
            }
            metrics={[
              { key: 'topup_revenue', label: 'Pendapatan', format: rupiah },
              { key: 'topup_count', label: 'Jumlah', format: number },
            ]}
          />
        </div>
      </div>

      <div className="grid items-start gap-6 xl:grid-cols-[1.1fr_1fr]">
        <Panel title="Pertumbuhan per Bulan">
          <ChartContainer config={growthConfig} className="aspect-auto h-[300px] w-full">
            <BarChart data={monthly} margin={{ left: 0, right: 4, top: 8 }} barCategoryGap="24%">
              <CartesianGrid vertical={false} strokeDasharray="3 3" />
              <XAxis dataKey="label" tickLine={false} axisLine={false} tickMargin={10} />
              <YAxis tickLine={false} axisLine={false} width={32} allowDecimals={false} />
              <ChartTooltip cursor={{ fill: 'hsl(var(--muted))', opacity: 0.6 }} content={<ChartTooltipContent />} />
              <ChartLegend content={<ChartLegendContent />} />
              <Bar dataKey="tenants_new" fill="var(--color-tenants_new)" radius={[6, 6, 2, 2]} maxBarSize={14} />
              <Bar dataKey="stores_new" fill="var(--color-stores_new)" radius={[6, 6, 2, 2]} maxBarSize={14} />
              <Bar dataKey="users_new" fill="var(--color-users_new)" radius={[6, 6, 2, 2]} maxBarSize={14} />
            </BarChart>
          </ChartContainer>
        </Panel>

        <Panel title="Pendapatan Top-up">
          <div className="space-y-3">
            {topupRows.map(([label, t]) => (
              <div key={label} className="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-2xl border bg-background px-5 py-4 shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
                <div className="w-32 text-[15px] text-muted-foreground">{label}</div>
                <div className="flex-1 text-xl font-semibold tabular-nums tracking-tight">{rupiah(t.revenue)}</div>
                <Pill>{number(t.count)} top-up</Pill>
              </div>
            ))}
          </div>
          <p className="mt-3 text-xs text-muted-foreground">
            Hanya top-up berstatus lunas · {number(topups.all.tokens)} token terjual sepanjang waktu.
          </p>
        </Panel>
      </div>
    </AdminLayout>
  )
}
