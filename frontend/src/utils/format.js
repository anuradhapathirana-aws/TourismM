/** Small, framework-free helpers shared across components. */

export const unsplash = (id, w = 1400) =>
  `https://images.unsplash.com/${id}?auto=format&fit=crop&w=${w}&q=80`

/** Request a smaller rendition of an Unsplash image (other URLs are returned unchanged). */
export const resized = (url, w = 800) =>
  url && url.includes('images.unsplash.com') ? url.replace(/([?&])w=\d+/, `$1w=${w}`) : url || ''

export const slugify = (s = '') =>
  String(s).toLowerCase().normalize('NFKD').replace(/[^\w\s-]/g, '').trim().replace(/[\s_-]+/g, '-')

/** Short category name shown on tabs and card tags ("Cultural & Heritage Tours" → "Cultural & Heritage"). */
export const tourCategoryLabel = (tour) => tour?.categoryLabel || tour?.category || ''

/** Stable key for filtering by category (API slug, or derived from the mock name). */
export const tourCategorySlug = (tour) => tour?.categorySlug || slugify(tour?.category)

const digits = (s = '') => String(s).replace(/\D+/g, '')

export const waLink = (number, message = '') =>
  `https://wa.me/${digits(number)}${message ? `?text=${encodeURIComponent(message)}` : ''}`

export const telLink = (phone) => `tel:${String(phone).replace(/[^\d+]/g, '')}`

export const mailLink = (email, subject = '') =>
  `mailto:${email}${subject ? `?subject=${encodeURIComponent(subject)}` : ''}`

export const money = (value, currency = 'USD') => {
  try {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: currency || 'USD', maximumFractionDigits: 0 }).format(value ?? 0)
  } catch {
    return `${currency} ${Math.round(value ?? 0)}` // unknown currency code from the API
  }
}

export const firstName = (name = '') => name.split(' ')[0]

export const unique = (arr) => [...new Set(arr.filter(Boolean))]
