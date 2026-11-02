import { useState } from 'react'
import { Head, useForm } from '@inertiajs/react'
import { Eye, EyeOff, Receipt, TrendingUp, Users, Wallet } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const HEX = 'polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%)'

/** Heksagon dekoratif berisi ikon — pengganti foto pada ilustrasi. */
function Hex({ className = '', style, children }) {
  return (
    <div
      className={`absolute flex items-center justify-center drop-shadow-xl ${className}`}
      style={{ ...style, clipPath: HEX }}
    >
      {children}
    </div>
  )
}

function Illustration() {
  return (
    <div className="relative mx-auto aspect-[4/3] w-full max-w-[560px]" aria-hidden="true">
      {/* heksagon outline tipis sebagai aksen latar */}
      <svg className="absolute left-[2%] top-[6%] h-14 w-14 text-slate-300" viewBox="0 0 100 100" fill="none">
        <polygon points="50,2 98,26 98,74 50,98 2,74 2,26" stroke="currentColor" strokeWidth="2" />
      </svg>
      <svg className="absolute right-[0%] top-[26%] h-28 w-28 text-slate-200" viewBox="0 0 100 100" fill="none">
        <polygon points="50,2 98,26 98,74 50,98 2,74 2,26" stroke="currentColor" strokeWidth="1.5" />
      </svg>

      {/* heksagon utama: ilustrasi kasir */}
      <div
        className="absolute left-[14%] top-[2%] h-[96%] w-[68%] bg-[#ffc9c2]"
        style={{ clipPath: HEX }}
      >
        <img
          src="/illustration/illustration_cashier_welcome.png"
          alt=""
          className="absolute inset-x-0 bottom-[14%] mx-auto w-[96%] mix-blend-multiply"
        />
      </div>

      <Hex className="right-[6%] top-[4%] h-[26%] w-[23%] bg-[#ff6b5e] text-white">
        <TrendingUp className="h-1/2 w-1/2" strokeWidth={2.25} />
      </Hex>
      <Hex className="right-[-2%] top-[34%] h-[26%] w-[23%] bg-[#2fb6e8] text-white">
        <Wallet className="h-1/2 w-1/2" strokeWidth={2.25} />
      </Hex>
      <Hex className="bottom-[10%] left-[0%] h-[30%] w-[26%] bg-[#ffcc3d] text-[#6b4b00]">
        <Users className="h-1/2 w-1/2" strokeWidth={2.25} />
      </Hex>
      <Hex className="bottom-[0%] left-[19%] h-[20%] w-[17%] bg-[#7a5ce0] text-white">
        <Receipt className="h-1/2 w-1/2" strokeWidth={2.25} />
      </Hex>
    </div>
  )
}

export default function Login() {
  const { data, setData, post, processing, errors } = useForm({ email: '', password: '', remember: false })
  const [show, setShow] = useState(false)

  const submit = (e) => {
    e.preventDefault()
    post('/admin/login', { onFinish: () => setData('password', '') })
  }

  return (
    <div className="grid min-h-screen lg:grid-cols-[1.55fr_1fr]">
      <Head title="Masuk" />

      {/* Panel kiri — hanya di layar lebar */}
      <section className="hidden flex-col justify-between gap-10 bg-[#eef2f6] px-12 py-14 lg:flex xl:px-20">
        <h2 className="max-w-xl text-4xl font-bold leading-tight tracking-tight text-[#0f2447] xl:text-[2.6rem]">
          Pantau Bisnis SaaS Anda dalam Satu Dashboard
        </h2>
        <Illustration />
        <p className="max-w-md text-sm text-slate-500">
          Lihat pertumbuhan tenant, omzet transaksi, pendapatan top-up token, dan tenant yang perlu di-follow-up — semua di satu tempat.
        </p>
      </section>

      {/* Panel kanan — form */}
      <section className="flex flex-col bg-white px-6 py-8 sm:px-12 xl:px-20">
        <img src="/gokasir.png" alt="GoKasir" className="h-24 w-24 object-contain" />

        <div className="flex flex-1 items-center">
          <div className="mx-auto w-full max-w-md">
            <h1 className="text-2xl font-bold text-brand-500">Login</h1>

            <h2 className="mt-8 text-base font-semibold text-[#0f2447]">Masuk ke akun admin</h2>
            <p className="mt-2 text-xs leading-relaxed text-slate-500">
              Selamat datang kembali. Masuk untuk memantau perkembangan bisnis GoKasir.
            </p>

            <form onSubmit={submit} className="mt-6 space-y-5">
              <div className="space-y-2">
                <Label htmlFor="email" className="text-xs font-normal text-slate-600">Email</Label>
                <Input
                  id="email" type="email" autoComplete="username" autoFocus
                  placeholder="admin@gokasir.net"
                  className="h-12 shadow-sm"
                  value={data.email} onChange={(e) => setData('email', e.target.value)}
                  aria-invalid={!!errors.email}
                />
                {errors.email && <p className="text-sm text-destructive">{errors.email}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="password" className="text-xs font-normal text-slate-600">Password</Label>
                <div className="relative">
                  <Input
                    id="password" type={show ? 'text' : 'password'} autoComplete="current-password"
                    placeholder="Password"
                    className="h-12 pr-11 shadow-sm"
                    value={data.password} onChange={(e) => setData('password', e.target.value)}
                  />
                  <button
                    type="button" onClick={() => setShow((s) => !s)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    aria-label={show ? 'Sembunyikan password' : 'Tampilkan password'}
                  >
                    {show ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
              </div>

              <div className="flex items-center gap-2">
                <Checkbox
                  id="remember" checked={data.remember}
                  onCheckedChange={(v) => setData('remember', v === true)}
                  className="data-[state=checked]:border-brand-500 data-[state=checked]:bg-brand-500"
                />
                <Label htmlFor="remember" className="text-xs font-normal text-slate-500">Ingat saya</Label>
              </div>

              <Button
                type="submit" disabled={processing}
                className="h-12 w-full rounded-none bg-brand-500 text-sm font-bold tracking-wide text-white hover:bg-brand-600"
              >
                {processing ? 'MEMPROSES…' : 'MASUK'}
              </Button>
            </form>
          </div>
        </div>
      </section>
    </div>
  )
}
