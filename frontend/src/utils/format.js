/** Small, framework-free helpers shared across components. */

export const unsplash = (id, w = 1400) =>
  `https://images.unsplash.com/${id}?auto=format&fit=crop&w=${w}&q=80`

/** Request a smaller rendition of an Unsplash image (other URLs are returned unchanged). */
export const resized = (url = '', w = 800) =>
  url.includes('images.unsplash.com') ? url.replace(/([?&])w=\d+/, `$1w=${w}`) : url

const digits = (s = '') => String(s).replace(/\D+/g, '')

export const waLink = (number, message = '') =>
  `https://wa.me/${digits(number)}${message ? `?text=${encodeURIComponent(message)}` : ''}`

export const telLink = (phone) => `tel:${String(phone).replace(/[^\d+]/g, '')}`

export const mailLink = (email, subject = '') =>
  `mailto:${email}${subject ? `?subject=${encodeURIComponent(subject)}` : ''}`

export const money = (value, currency = 'USD') =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency, maximumFractionDigits: 0 }).format(value)

export const firstName = (name = '') => name.split(' ')[0]

export const unique = (arr) => [...new Set(arr.filter(Boolean))]
