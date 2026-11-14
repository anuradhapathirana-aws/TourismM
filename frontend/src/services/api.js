/**
 * Data access layer — the ONLY place components get content from.
 *
 * Today: returns local mock data from src/data/.
 * Later: set VITE_API_URL (see .env.example) and these functions call the Laravel API.
 * Laravel API Resources wrap payloads as { data: ... }; `unwrap` handles both forms.
 * Endpoint contract is documented in CLAUDE.md → "API contract".
 */
import { site } from '@/config/site'
import { destinations } from '@/data/destinations'
import { tours } from '@/data/tours'
import { guides } from '@/data/guides'
import { testimonials } from '@/data/testimonials'
import { faqs } from '@/data/faqs'
import { themes } from '@/data/themes'

const API_URL = (import.meta.env.VITE_API_URL || '').replace(/\/$/, '')
export const hasBackend = API_URL !== ''

const unwrap = (json) => (json && typeof json === 'object' && 'data' in json ? json.data : json)

async function get(path, fallback) {
  if (!hasBackend) return structuredClone(fallback)
  const res = await fetch(`${API_URL}${path}`, { headers: { Accept: 'application/json' } })
  if (res.status === 404) return null
  if (!res.ok) throw new Error(`Request failed (${res.status})`)
  return unwrap(await res.json())
}

export const getSite = () => get('/settings', site)
export const getDestinations = () => get('/destinations', destinations)
export const getThemes = () => get('/themes', themes)
export const getTours = () => get('/tours', tours)
export const getTour = (slug) =>
  get(`/tours/${encodeURIComponent(slug)}`, tours.find((t) => t.slug === slug) ?? null)
export const getGuides = () => get('/guides', guides)
export const getTestimonials = () => get('/testimonials', testimonials)
export const getFaqs = () => get('/faqs', faqs)

/**
 * Submit an enquiry. Returns { ok: true } on success.
 * Without a backend this resolves with { ok: false, fallback: 'whatsapp' } so the
 * form can hand the enquiry to WhatsApp instead.
 */
export async function submitInquiry(payload) {
  if (!hasBackend) return { ok: false, fallback: 'whatsapp' }
  const res = await fetch(`${API_URL}/inquiries`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(payload),
  })
  if (res.status === 422) {
    const body = await res.json()
    return { ok: false, errors: body.errors || {} } // Laravel validation shape
  }
  if (!res.ok) throw new Error(`Could not send enquiry (${res.status})`)
  return { ok: true }
}
