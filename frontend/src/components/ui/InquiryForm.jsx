import { useId, useRef, useState } from 'react'
import { useSite } from '@/context/SiteContext'
import { hasBackend, submitInquiry } from '@/services/api'
import { mailLink, waLink } from '@/utils/format'

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const EMPTY = { name: '', email: '', phone: '', country: '', date: '', travellers: '2', tour: '', message: '' }

function validate(v) {
  const errors = {}
  if (!v.name.trim()) errors.name = 'Please enter your name.'
  if (!v.email.trim()) errors.email = 'Please enter your email so we can reply.'
  else if (!EMAIL_RE.test(v.email.trim())) errors.email = 'This email looks incomplete. Check it has an @ and a domain.'
  return errors
}

function buildMessage(v, tourTitle) {
  return [
    'Hello Lean Global! I would like to plan a trip.',
    `Name: ${v.name}`,
    `Email: ${v.email}`,
    v.phone && `Phone: ${v.phone}`,
    v.country && `Country: ${v.country}`,
    v.date && `Travel date: ${v.date}`,
    v.travellers && `Travellers: ${v.travellers}`,
    `Interested in: ${tourTitle || 'Tailor-made journey'}`,
    v.message && `Message: ${v.message}`,
  ].filter(Boolean).join('\n')
}

/**
 * Enquiry form.
 * - With a backend (VITE_API_URL) it POSTs to /inquiries.
 * - Without one, it opens WhatsApp with the enquiry pre-filled.
 * Props: tours (for the select) OR fixedTour (detail page), compact (single column).
 */
export default function InquiryForm({ tours = [], fixedTour = null, compact = false }) {
  const site = useSite()
  const uid = useId()
  const formRef = useRef(null)
  const [values, setValues] = useState({ ...EMPTY, tour: fixedTour ? fixedTour.slug : '' })
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState({ type: '', text: '' })
  const [sending, setSending] = useState(false)

  const id = (name) => `${uid}-${name}`
  const set = (e) => setValues((v) => ({ ...v, [e.target.name]: e.target.value }))
  const onBlur = (e) => {
    const field = e.target.name
    if (field !== 'name' && field !== 'email') return
    setErrors((prev) => ({ ...prev, [field]: validate(values)[field] }))
  }

  async function onSubmit(e) {
    e.preventDefault()
    const errs = validate(values)
    setErrors(errs)
    if (Object.keys(errs).length) {
      setStatus({ type: 'err', text: 'Please fix the highlighted fields.' })
      formRef.current?.querySelector(`#${CSS.escape(id(Object.keys(errs)[0]))}`)?.focus()
      return
    }
    const tourTitle = fixedTour?.title || tours.find((t) => t.slug === values.tour)?.title

    // No backend yet → hand over to WhatsApp (opened synchronously so pop-up blockers allow it)
    if (!hasBackend) {
      window.open(waLink(site.whatsapp, buildMessage(values, tourTitle)), '_blank', 'noopener')
      setStatus({ type: 'ok', text: 'WhatsApp opened with your enquiry. Just press send and we will reply shortly.' })
      return
    }

    setSending(true)
    setStatus({ type: '', text: '' })
    try {
      const res = await submitInquiry({ ...values, tour_slug: values.tour || null })
      if (res.ok) {
        setValues({ ...EMPTY, tour: fixedTour ? fixedTour.slug : '' })
        setStatus({ type: 'ok', text: `Thank you, ${values.name}! Your enquiry was received. We will contact you shortly.` })
      } else if (res.errors) {
        setErrors(Object.fromEntries(Object.entries(res.errors).map(([k, v]) => [k, [].concat(v)[0]])))
        setStatus({ type: 'err', text: 'Please fix the highlighted fields.' })
      }
    } catch {
      setStatus({ type: 'err', text: 'We could not send your enquiry. Please try again, or message us on WhatsApp.' })
    } finally {
      setSending(false)
    }
  }

  const field = (name, label, props = {}, required = false) => (
    <div className={`field${props.span ? ' span-2' : ''}`}>
      <label htmlFor={id(name)}>
        {label} {required && <span className="req" aria-hidden="true">*</span>}
      </label>
      {props.as === 'textarea' ? (
        <textarea id={id(name)} name={name} value={values[name]} onChange={set} rows={props.rows || 4} maxLength={2000} placeholder={props.placeholder} />
      ) : (
        <input
          id={id(name)}
          name={name}
          value={values[name]}
          onChange={set}
          onBlur={onBlur}
          type={props.type || 'text'}
          autoComplete={props.autoComplete}
          inputMode={props.inputMode}
          min={props.min}
          max={props.max}
          required={required}
          aria-required={required || undefined}
          aria-invalid={errors[name] ? true : undefined}
          aria-describedby={errors[name] ? id(`${name}-err`) : undefined}
        />
      )}
      {errors[name] && <p className="field-err" id={id(`${name}-err`)}>{errors[name]}</p>}
    </div>
  )

  return (
    <form ref={formRef} className="inquiry-form" id="inquiry" onSubmit={onSubmit} noValidate>
      <div className={`form-status ${status.type}`} role="status" aria-live="polite">
        {status.text && (
          <>
            <i className={`bi ${status.type === 'ok' ? 'bi-check-circle' : 'bi-exclamation-circle'}`} aria-hidden="true" /> {status.text}
          </>
        )}
      </div>

      <div className={`form-grid${compact ? ' single' : ''}`}>
        {field('name', 'Full name', { autoComplete: 'name' }, true)}
        {field('email', 'Email', { type: 'email', autoComplete: 'email' }, true)}
        {field('phone', 'Phone / WhatsApp', { type: 'tel', autoComplete: 'tel' })}
        {!compact && field('country', 'Country', { autoComplete: 'country-name' })}
        {field('date', 'Travel date', { type: 'date' })}
        {field('travellers', 'Travellers', { type: 'number', min: 1, max: 99, inputMode: 'numeric' })}
        {!fixedTour && (
          <div className="field span-2">
            <label htmlFor={id('tour')}>Interested in</label>
            <select id={id('tour')} name="tour" value={values.tour} onChange={set}>
              <option value="">Tailor-made journey</option>
              {tours.map((t) => <option key={t.slug} value={t.slug}>{t.title}</option>)}
            </select>
          </div>
        )}
        {field('message', 'Message', { as: 'textarea', span: true, rows: compact ? 3 : 4, placeholder: 'Tell us about your dream trip…' })}
      </div>

      <button className="btn btn-gold btn-block" type="submit" disabled={sending} aria-busy={sending}>
        {sending ? (
          <><span className="spinner sm" aria-hidden="true" /> Sending…</>
        ) : hasBackend ? (
          <>Send Enquiry <i className="bi bi-send" aria-hidden="true" /></>
        ) : (
          <><i className="bi bi-whatsapp" aria-hidden="true" /> Send Enquiry via WhatsApp</>
        )}
      </button>
      <p className="form-note">
        We usually reply within a few hours. Prefer email? <a href={mailLink(site.email, 'Trip enquiry')}>Email us</a>.
      </p>
    </form>
  )
}
