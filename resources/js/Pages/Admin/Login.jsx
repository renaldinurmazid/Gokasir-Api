import { Head, useForm } from '@inertiajs/react'
import { Store } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

export default function Login() {
  const { data, setData, post, processing, errors } = useForm({ email: '', password: '', remember: false })

  const submit = (e) => {
    e.preventDefault()
    post('/admin/login', { onFinish: () => setData('password', '') })
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-muted/30 p-4">
      <Head title="Masuk" />
      <Card className="w-full max-w-sm">
        <CardHeader className="space-y-3 text-center">
          <div className="mx-auto flex h-10 w-10 items-center justify-center rounded-md bg-primary text-primary-foreground">
            <Store className="h-5 w-5" />
          </div>
          <div>
            <CardTitle>Admin GoKasir</CardTitle>
            <CardDescription>Masuk untuk memantau bisnis SaaS Anda</CardDescription>
          </div>
        </CardHeader>
        <CardContent>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <Input id="email" type="email" autoComplete="username" autoFocus value={data.email}
                onChange={(e) => setData('email', e.target.value)} aria-invalid={!!errors.email} />
              {errors.email && <p className="text-sm text-destructive">{errors.email}</p>}
            </div>
            <div className="space-y-2">
              <Label htmlFor="password">Password</Label>
              <Input id="password" type="password" autoComplete="current-password" value={data.password}
                onChange={(e) => setData('password', e.target.value)} />
            </div>
            <Button type="submit" className="w-full" disabled={processing}>
              {processing ? 'Memproses…' : 'Masuk'}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
