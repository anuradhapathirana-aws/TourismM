/**
 * "Build your own journey" planner: field options, defaults, per-step validation,
 * the API payload (snake_case, like /inquiries) and the WhatsApp fallback message.
 * Keep option values stable: the backend will validate against the same lists.
 */
import { money } from '@/utils/format'

export const STEPS = [
  { key: 'trip', title: 'Your trip', icon: 'bi-calendar-heart' },
  { key: 'travellers', title: 'Travellers & stay', icon: 'bi-people' },
  { key: 'interests', title: 'Places & activities', icon: 'bi-compass' },
  { key: 'contact', title: 'Contact details', icon: 'bi-person-lines-fill' },
]

export const AIRPORTS = [
  { value: 'cmb', label: 'Bandaranaike International, Colombo (CMB)' },
  { value: 'hri', label: 'Mattala Rajapaksa International (HRI)' },
  { value: 'in-country', label: 'I’m already in Sri Lanka' },
  { value: 'not-booked', label: 'Flights not booked yet' },
]

export const OCCASIONS = [
  { value: 'holiday', label: 'Holiday' },
  { value: 'honeymoon', label: 'Honeymoon' },
  { value: 'family', label: 'Family trip' },
  { value: 'anniversary', label: 'Anniversary' },
  { value: 'birthday', label: 'Birthday' },
  { value: 'friends', label: 'Trip with friends' },
  { value: 'solo', label: 'Solo travel' },
  { value: 'business', label: 'Business + leisure' },
  { value: 'other', label: 'Other' },
]

export const PACES = [
  { value: 'relaxed', label: 'Relaxed', icon: 'bi-cup-hot', text: 'Late starts, fewer stops' },
  { value: 'moderate', label: 'Moderate', icon: 'bi-sun', text: 'A mix of sights and rest' },
  { value: 'active', label: 'Active', icon: 'bi-lightning-charge', text: 'Early starts, see it all' },
]

export const ACCOMMODATION = [
  { value: 'budget', label: 'Budget · 2–3★ & guesthouses' },
  { value: 'comfort', label: 'Comfort · 3–4★' },
  { value: 'luxury', label: 'Luxury · 5★' },
  { value: 'boutique', label: 'Boutique hotels & villas' },
  { value: 'mixed', label: 'Mix it up' },
  { value: 'none', label: 'Not needed, I’ll book my own' },
]

export const MEAL_PLANS = [
  { value: 'bb', label: 'Breakfast only' },
  { value: 'hb', label: 'Half board (breakfast + dinner)' },
  { value: 'fb', label: 'Full board (all meals)' },
  { value: 'none', label: 'No meals' },
]

export const VEHICLES = [
  { value: 'recommend', label: 'Recommend one for us' },
  { value: 'car', label: 'Car (up to 3 guests)' },
  { value: 'van', label: 'Van (up to 8 guests)' },
  { value: 'luxury-suv', label: 'Luxury SUV' },
  { value: 'coach', label: 'Mini-coach (larger groups)' },
]

export const LANGUAGES = ['English', 'German', 'French', 'Spanish', 'Italian', 'Russian', 'Chinese', 'Japanese', 'Hindi', 'Arabic', 'Other']

export const BUDGETS = [
  { value: 'under-500', max: 500 },
  { value: '500-1000', min: 500, max: 1000 },
  { value: '1000-2000', min: 1000, max: 2000 },
  { value: '2000-3500', min: 2000, max: 3500 },
  { value: '3500-plus', min: 3500 },
]

export const budgetLabel = (value, currency) => {
  const b = BUDGETS.find((x) => x.value === value)
  if (!b) return 'Not sure yet'
  if (!b.min) return `Under ${money(b.max, currency)}`
  if (!b.max) return `${money(b.min, currency)}+`
  return `${money(b.min, currency)} – ${money(b.max, currency)}`
}

export const CONTACT_METHODS = [
  { value: 'whatsapp', label: 'WhatsApp', icon: 'bi-whatsapp' },
  { value: 'email', label: 'Email', icon: 'bi-envelope' },
  { value: 'phone', label: 'Phone call', icon: 'bi-telephone' },
]

export const REFERRALS = ['Google search', 'Instagram', 'Facebook', 'TikTok', 'TripAdvisor', 'Friend or family', 'Travelled with you before', 'Other']

export const COUNTER_MAX = 30
export const NOTES_MAX = 3000

export const EMPTY = {
  arrivalDate: '', arrivalTime: '', departureDate: '', flexibleDates: false, arrivalAirport: '', occasion: '', pace: 'moderate',
  men: 0, women: 0, children: 0, childAges: [],
  accommodation: 'comfort', rooms: 1, mealPlan: 'bb', vehicle: 'recommend', guideLanguage: 'English', budget: '',
  dietary: '', accessibility: '',
  destinations: [], activities: [], notes: '',
  name: '', email: '', phone: '', country: '', contactMethod: 'whatsapp', referral: '',
  website: '', // honeypot: real people never see or fill it
}

/** Fields shown on each step (used to jump to the step of a server-side error). */
export const STEP_FIELDS = [
  ['arrivalDate', 'arrivalTime', 'departureDate', 'flexibleDates', 'arrivalAirport', 'occasion', 'pace'],
  ['men', 'women', 'children', 'childAges', 'accommodation', 'rooms', 'mealPlan', 'vehicle', 'guideLanguage', 'budget', 'dietary', 'accessibility'],
  ['destinations', 'activities', 'notes'],
  ['name', 'email', 'phone', 'country', 'contactMethod', 'referral'],
]

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

export const todayIso = () => {
  const d = new Date()
  return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10)
}

/** Nights between two yyyy-mm-dd dates (null when either is missing or the order is wrong). */
export function nightsBetween(from, to) {
  if (!from || !to) return null
  const n = Math.round((Date.parse(to) - Date.parse(from)) / 86400000)
  return n > 0 ? n : null
}

export const adults = (v) => v.men + v.women
export const totalGuests = (v) => v.men + v.women + v.children

/** Errors for one step: { field: message }. childAges errors are keyed childAge-<index>. */
export function validateStep(step, v) {
  const e = {}
  if (step === 0) {
    if (!v.arrivalDate) e.arrivalDate = 'Please choose the day you arrive in Sri Lanka.'
    else if (v.arrivalDate < todayIso()) e.arrivalDate = 'The arrival date is in the past.'
    if (!v.departureDate) e.departureDate = 'Please choose the day you leave.'
    else if (v.arrivalDate && v.departureDate <= v.arrivalDate) e.departureDate = 'The departure must be at least one day after arrival.'
  }
  if (step === 1) {
    if (adults(v) < 1) e.men = 'Please add at least one adult (men or women).'
    v.childAges.forEach((age, i) => { if (age === '') e[`childAge-${i}`] = `Please choose the age of child ${i + 1}.` })
    if (v.accommodation !== 'none' && (!Number.isInteger(v.rooms) || v.rooms < 1)) e.rooms = 'Please enter how many rooms you need.'
    if (v.dietary.length > 300) e.dietary = 'Please keep this under 300 characters.'
    if (v.accessibility.length > 300) e.accessibility = 'Please keep this under 300 characters.'
  }
  if (step === 2) {
    if (v.notes.length > NOTES_MAX) e.notes = `Please keep this under ${NOTES_MAX} characters.`
  }
  if (step === 3) {
    if (!v.name.trim()) e.name = 'Please enter your name.'
    if (!v.email.trim()) e.email = 'Please enter your email so we can send your itinerary.'
    else if (!EMAIL_RE.test(v.email.trim())) e.email = 'This email looks incomplete. Check it has an @ and a domain.'
    const digits = v.phone.replace(/\D/g, '')
    if (!digits) e.phone = 'Please enter a phone or WhatsApp number.'
    else if (digits.length < 7 || digits.length > 15) e.phone = 'Please enter the full number with country code, e.g. +44 7700 900123.'
    if (!v.country.trim()) e.country = 'Please tell us which country you live in.'
  }
  return e
}

/** Body for POST /journey-requests. Destinations and activities are sent as ids. */
export const toPayload = (v) => ({
  arrival_date: v.arrivalDate,
  arrival_time: v.arrivalTime || null,
  departure_date: v.departureDate,
  flexible_dates: v.flexibleDates,
  arrival_airport: v.arrivalAirport || null,
  occasion: v.occasion || null,
  pace: v.pace,
  men: v.men,
  women: v.women,
  children: v.children,
  child_ages: v.childAges.map(Number),
  accommodation: v.accommodation,
  rooms: v.accommodation === 'none' ? null : v.rooms,
  meal_plan: v.mealPlan,
  vehicle: v.vehicle,
  guide_language: v.guideLanguage,
  budget: v.budget || null,
  dietary: v.dietary.trim() || null,
  accessibility: v.accessibility.trim() || null,
  destination_ids: v.destinations,
  activity_ids: v.activities,
  notes: v.notes.trim() || null,
  name: v.name.trim(),
  email: v.email.trim(),
  phone: v.phone.trim(),
  country: v.country.trim(),
  contact_method: v.contactMethod,
  referral: v.referral || null,
  website: v.website,
})

/** Map a Laravel 422 error key (snake_case, maybe "child_ages.1") back to a form field key. */
export function fieldFromServerKey(key) {
  const [base, index] = key.split('.')
  if (base === 'child_ages' && index !== undefined) return `childAge-${index}`
  if (base === 'destination_ids') return 'destinations'
  if (base === 'activity_ids') return 'activities'
  return base.replace(/_([a-z])/g, (_, c) => c.toUpperCase())
}

const label = (list, value) => list.find((o) => o.value === value)?.label || ''
const ageText = (a) => (a === '0' ? 'under 1' : a)

export function formatDate(iso) {
  if (!iso) return ''
  return new Date(`${iso}T00:00:00`).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

/** Readable summary rows, shared by the review box and the WhatsApp message. */
export function summaryRows(v, { destinationNames, activityNames, currency }) {
  const nights = nightsBetween(v.arrivalDate, v.departureDate)
  const guests = [
    v.men && `${v.men} ${v.men === 1 ? 'man' : 'men'}`,
    v.women && `${v.women} ${v.women === 1 ? 'woman' : 'women'}`,
    v.children && `${v.children} ${v.children === 1 ? 'child' : 'children'}${v.childAges.length ? ` (age ${v.childAges.map(ageText).join(', ')})` : ''}`,
  ].filter(Boolean).join(', ')

  return [
    ['Dates', `${formatDate(v.arrivalDate)} → ${formatDate(v.departureDate)}${nights ? ` · ${nights} night${nights > 1 ? 's' : ''}` : ''}${v.flexibleDates ? ' (flexible)' : ''}`],
    ['Arrival', [label(AIRPORTS, v.arrivalAirport), v.arrivalTime && `at ${v.arrivalTime}`].filter(Boolean).join(' ')],
    ['Occasion', label(OCCASIONS, v.occasion)],
    ['Pace', label(PACES, v.pace)],
    ['Travellers', `${totalGuests(v)} guest${totalGuests(v) === 1 ? '' : 's'}${guests ? `: ${guests}` : ''}`],
    ['Hotels', v.accommodation === 'none' ? label(ACCOMMODATION, 'none') : `${label(ACCOMMODATION, v.accommodation)} · ${v.rooms} room${v.rooms === 1 ? '' : 's'} · ${label(MEAL_PLANS, v.mealPlan)}`],
    ['Vehicle', label(VEHICLES, v.vehicle)],
    ['Guide language', v.guideLanguage],
    ['Budget per person', v.budget ? `${budgetLabel(v.budget, currency)} (excl. flights)` : ''],
    ['Dietary needs', v.dietary.trim()],
    ['Accessibility', v.accessibility.trim()],
    ['Destinations', destinationNames.join(', ')],
    ['Activities', activityNames.join(', ')],
    ['Wishes', v.notes.trim()],
  ].filter(([, value]) => value)
}

export function buildWhatsAppMessage(v, ctx) {
  return [
    'Hello Lean Global! I would like you to build my own Sri Lanka journey.',
    '',
    ...summaryRows(v, ctx).map(([k, value]) => `${k}: ${value}`),
    '',
    `Name: ${v.name.trim()}`,
    `Email: ${v.email.trim()}`,
    `Phone: ${v.phone.trim()}`,
    `Country: ${v.country.trim()}`,
    `Contact me by: ${label(CONTACT_METHODS, v.contactMethod)}`,
    ...(v.referral ? [`Found you via: ${v.referral}`] : []),
  ].join('\n')
}
