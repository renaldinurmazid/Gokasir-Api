const idr = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 })
const num = new Intl.NumberFormat('id-ID')
const compact = new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 })

export const rupiah = (v) => idr.format(Number(v) || 0)
export const number = (v) => num.format(Number(v) || 0)
export const compactNumber = (v) => compact.format(Number(v) || 0)

export function bytes(n) {
  n = Number(n) || 0
  if (n < 1024) return `${n} B`
  const units = ['KB', 'MB', 'GB', 'TB']
  let i = -1
  do { n /= 1024; i++ } while (n >= 1024 && i < units.length - 1)
  return `${n.toFixed(n >= 100 ? 0 : 1)} ${units[i]}`
}

const dateFmt = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'Asia/Jakarta' })
const dateTimeFmt = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' })

export const date = (v) => (v ? dateFmt.format(new Date(v)) : '—')
export const dateTime = (v) => (v ? dateTimeFmt.format(new Date(v)) : '—')

export function relativeDays(v) {
  if (!v) return '—'
  const d = Math.floor((Date.now() - new Date(v).getTime()) / 86400000)
  if (d <= 0) return 'Hari ini'
  if (d === 1) return 'Kemarin'
  return `${d} hari lalu`
}

/** 0812… / +62812… → 62812… untuk tautan wa.me */
export function waNumber(phone) {
  const digits = String(phone || '').replace(/\D/g, '')
  if (!digits) return null
  if (digits.startsWith('0')) return '62' + digits.slice(1)
  if (digits.startsWith('62')) return digits
  return digits
}
