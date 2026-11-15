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
import { activities } from '@/data/activities'
import { tourCategoryLabel, tourCategorySlug, unique } from '@/utils/format'

const API_URL = (import.meta.env.VITE_API_URL || '').replace(/\/$/, '')
const apiConfigured = API_URL !== ''

/**
 * Modules the Laravel backend already serves. Everything else keeps using mock data,
 * so the site works while the backend is built module by module.
 * Add a name here when its endpoint goes live (see docs/backend-architecture.md → Roadmap).
 */
const LIVE_MODULES = new Set(['destinations', 'themes', 'tours', 'tour-categories', 'activities', 'journey-requests'])

/** True when enquiries are POSTed to the API (otherwise the form hands them to WhatsApp). */
export const hasBackend = apiConfigured && LIVE_MODULES.has('inquiries')

const unwrap = (json) => (json && typeof json === 'object' && 'data' in json ? json.data : json)

/**
 * Several components ask for the same list on one page (e.g. header/footer + section),
 * so identical GETs share one request for a short time. Failed requests are not kept.
 */
const SHARE_MS = 60_000
const inflight = new Map()

async function request(path) {
  const res = await fetch(`${API_URL}${path}`, { headers: { Accept: 'application/json' } })
  if (res.status === 404) return null
  if (!res.ok) throw new Error(`Request failed (${res.status})`)
  return unwrap(await res.json())
}

function get(module, path, fallback) {
  if (!apiConfigured || !LIVE_MODULES.has(module)) return Promise.resolve(structuredClone(fallback))
  const hit = inflight.get(path)
  if (hit && Date.now() - hit.at < SHARE_MS) return hit.promise.then((data) => structuredClone(data))
  const promise = request(path)
  inflight.set(path, { promise, at: Date.now() })
  promise.catch(() => inflight.delete(path))
  return promise.then((data) => structuredClone(data))
}

/** Mock categories in the same shape as GET /tour-categories (order of first appearance). */
const mockTourCategories = () =>
  unique(tours.map(tourCategorySlug)).map((slug, i) => {
    const t = tours.find((x) => tourCategorySlug(x) === slug)
    return { id: i + 1, slug, name: t.category, label: tourCategoryLabel(t), toursCount: tours.filter((x) => tourCategorySlug(x) === slug).length }
  })

export const getSite = () => get('settings', '/settings', site)
export const getDestinations = () => get('destinations', '/destinations', destinations)
export const getThemes = () => get('themes', '/themes', themes)
export const getTours = () => get('tours', '/tours', tours)
export const getTourCategories = () => get('tour-categories', '/tour-categories', mockTourCategories())
export const getTour = (slug) =>
  get('tours', `/tours/${encodeURIComponent(slug)}`, tours.find((t) => t.slug === slug) ?? null)
export const getGuides = () => get('guides', '/guides', guides)
export const getTestimonials = () => get('testimonials', '/testimonials', testimonials)
export const getFaqs = () => get('faqs', '/faqs', faqs)
export const getActivities = () => get('activities', '/activities', activities)

/** True when "Build your own journey" requests are POSTed to the API (otherwise they go to WhatsApp). */
export const hasJourneyBackend = apiConfigured && LIVE_MODULES.has('journey-requests')

/**
 * Submit a "Build your own journey" request (POST /journey-requests).
 * Returns { ok: true, reference } | { ok: false, errors } (422) | { ok: false, tooMany: true } (429)
 * | { ok: false, fallback: 'whatsapp' } (module not live yet).
 */
export async function submitJourneyRequest(payload) {
  if (!hasJourneyBackend) return { ok: false, fallback: 'whatsapp' }
  const res = await fetch(`${API_URL}/journey-requests`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(payload),
  })
  if (res.status === 422) {
    const body = await res.json()
    return { ok: false, errors: body.errors || {} }
  }
  if (res.status === 429) return { ok: false, tooMany: true }
  if (!res.ok) throw new Error(`Could not send your request (${res.status})`)
  const body = await res.json().catch(() => ({}))
  return { ok: true, reference: unwrap(body)?.reference ?? null }
}

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
