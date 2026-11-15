import { useEffect, useId, useMemo, useRef, useState } from 'react'
import { useSite } from '@/context/SiteContext'
import { useApi } from '@/hooks/useApi'
import { getActivities, getDestinations, hasJourneyBackend, submitJourneyRequest } from '@/services/api'
import { mailLink, waLink } from '@/utils/format'
import { ChoiceList, Counter, FieldError, RadioCards } from './PlannerFields'
import {
  ACCOMMODATION, AIRPORTS, BUDGETS, CONTACT_METHODS, COUNTER_MAX, EMPTY, LANGUAGES, MEAL_PLANS, NOTES_MAX, OCCASIONS,
  PACES, REFERRALS, STEP_FIELDS, STEPS, VEHICLES, budgetLabel, buildWhatsAppMessage, fieldFromServerKey, nightsBetween,
  summaryRows, toPayload, todayIso, totalGuests, validateStep,
} from './journeyOptions'

const CHILD_AGES = Array.from({ length: 18 }, (_, i) => i)
const plural = (n, one, many) => `${n} ${n === 1 ? one : many}`
const addDay = (iso) => (iso ? new Date(Date.parse(iso) + 86400000).toISOString().slice(0, 10) : '')

/**
 * "Build your own journey" popup: a 4-step request form for guests who want a tailor-made tour.
 * Native <dialog> (focus trap, Esc to close, background made inert by the browser).
 * Answers stay in memory while the page is open, so closing the popup by mistake loses nothing.
 * Sends to POST /journey-requests when that module is live, otherwise opens WhatsApp pre-filled.
 */
export default function JourneyPlanner({ open, onClose }) {
  const site = useSite()
  const uid = useId()
  const dialogRef = useRef(null)
  const bodyRef = useRef(null)
  const headingRef = useRef(null)

  const { data: destinations, loading: destLoading } = useApi(getDestinations)
  const { data: activityList, loading: actLoading } = useApi(getActivities)

  const [values, setValues] = useState(EMPTY)
  const [step, setStep] = useState(0)
  const [maxStep, setMaxStep] = useState(0)
  const [tried, setTried] = useState(() => new Set())
  const [touched, setTouched] = useState({})
  const [serverErrors, setServerErrors] = useState({})
  const [status, setStatus] = useState('')
  const [sending, setSending] = useState(false)
  const [done, setDone] = useState(null) // null | 'sent' | 'whatsapp'
  const [reference, setReference] = useState(null) // e.g. LG-2611-00042, returned by the API

  // ---- options from the API (admin-managed destination points and activities) ----
  const destOptions = useMemo(
    () => (destinations || [])
      .map((d) => ({ value: d.id, label: d.name, group: d.city?.name || d.category || 'Other places' }))
      .sort((a, b) => a.group.localeCompare(b.group) || a.label.localeCompare(b.label)),
    [destinations],
  )
  const actOptions = useMemo(() => (activityList || []).map((a) => ({ value: a.id, label: a.name })), [activityList])
  const namesOf = (options, ids) => ids.map((id) => options.find((o) => o.value === id)?.label).filter(Boolean)
  const summaryCtx = {
    destinationNames: namesOf(destOptions, values.destinations),
    activityNames: namesOf(actOptions, values.activities),
    currency: site.currency,
  }

  // ---- open / close the native dialog, then move focus to the step heading ----
  useEffect(() => {
    const d = dialogRef.current
    if (!d) return
    if (open && !d.open) {
      d.showModal()
      document.body.classList.add('no-scroll')
    } else if (!open && d.open) d.close()
    if (open) {
      headingRef.current?.focus({ preventScroll: true })
      bodyRef.current?.scrollTo({ top: 0 })
    }
  }, [open, step, done])

  function reset() {
    setValues(EMPTY)
    setStep(0)
    setMaxStep(0)
    setTried(new Set())
    setTouched({})
    setServerErrors({})
    setStatus('')
    setDone(null)
    setReference(null)
  }

  function handleClose() {
    document.body.classList.remove('no-scroll')
    if (done === 'sent') reset() // sent to the team: start fresh next time
    onClose()
  }

  // ---- values & errors ----
  const live = validateStep(step, values)
  const errors = {
    ...Object.fromEntries(Object.entries(live).filter(([k]) => tried.has(step) || touched[k])),
    ...serverErrors,
  }
  const err = (name) => errors[name]
  const id = (name) => `${uid}-${name}`

  /** Change values and clear any server error on those fields (or on `errorKeys`). */
  const update = (patch, errorKeys = Object.keys(patch)) => {
    setValues((v) => ({ ...v, ...patch }))
    setServerErrors((prev) => {
      const keys = errorKeys
      if (!keys.some((k) => prev[k])) return prev
      const next = { ...prev }
      keys.forEach((k) => delete next[k])
      return next
    })
    setStatus('')
  }
  const onField = (e) => {
    const { name, type, value, checked } = e.target
    update({ [name]: type === 'checkbox' ? checked : value })
  }
  const onBlur = (e) => setTouched((t) => ({ ...t, [e.target.name || e.target.id.replace(`${uid}-`, '')]: true }))

  const setChildren = (n) => {
    setValues((v) => ({ ...v, children: n, childAges: Array.from({ length: n }, (_, i) => v.childAges[i] ?? '') }))
  }

  /** Props shared by every input: id, name, value, error wiring. `hint` ids are added to aria-describedby. */
  const bind = (name, { hint } = {}) => ({
    id: id(name),
    name,
    value: values[name],
    onChange: onField,
    onBlur,
    'aria-invalid': err(name) ? true : undefined,
    'aria-describedby': [hint && id(`${name}-hint`), err(name) && id(`${name}-err`)].filter(Boolean).join(' ') || undefined,
  })

  const label = (name, text, required = false) => (
    <label htmlFor={id(name)}>
      {text} {required && <span className="req" aria-hidden="true">*</span>}
    </label>
  )
  const hint = (name, text) => <p className="field-hint" id={id(`${name}-hint`)}>{text}</p>
  const errLine = (name) => <FieldError id={id(`${name}-err`)}>{err(name)}</FieldError>

  // ---- navigation ----
  function focusFirstError(errs) {
    const first = Object.keys(errs)[0]
    requestAnimationFrame(() => dialogRef.current?.querySelector(`#${CSS.escape(id(first))}`)?.focus())
  }

  function goTo(target) {
    if (target > step) {
      const errs = validateStep(step, values)
      setTried((t) => new Set(t).add(step))
      if (Object.keys(errs).length) {
        setStatus('Please check the highlighted fields.')
        focusFirstError(errs)
        return
      }
    }
    setStatus('')
    setStep(target)
    setMaxStep((m) => Math.max(m, target))
  }

  async function onSubmit(e) {
    e.preventDefault()
    if (step < STEPS.length - 1) return goTo(step + 1)

    for (let s = 0; s < STEPS.length; s++) {
      const errs = validateStep(s, values)
      if (Object.keys(errs).length) {
        setTried((t) => new Set(t).add(s))
        setStep(s)
        setStatus('Please check the highlighted fields.')
        focusFirstError(errs)
        return
      }
    }
    if (values.website) return setDone('sent') // honeypot filled: quietly ignore bots

    // No backend yet → WhatsApp (opened synchronously so pop-up blockers allow it)
    if (!hasJourneyBackend) {
      window.open(waLink(site.whatsapp, buildWhatsAppMessage(values, summaryCtx)), '_blank', 'noopener')
      setDone('whatsapp')
      return
    }

    setSending(true)
    setStatus('')
    try {
      const res = await submitJourneyRequest(toPayload(values))
      if (res.ok) {
        setReference(res.reference)
        setDone('sent')
      } else if (res.tooMany) {
        setStatus('You have sent several requests in a short time. Please wait a minute and try again, or message us on WhatsApp.')
      } else if (res.errors) {
        const mapped = Object.fromEntries(Object.entries(res.errors).map(([k, v]) => [fieldFromServerKey(k), [].concat(v)[0]]))
        setServerErrors(mapped)
        const keys = Object.keys(mapped)
        const target = STEP_FIELDS.findIndex((fields) => keys.some((k) => fields.includes(k) || (k.startsWith('childAge-') && fields.includes('childAges'))))
        setStep(target >= 0 ? target : STEPS.length - 1)
        setStatus('Please check the highlighted fields.')
        focusFirstError(mapped)
      }
    } catch {
      setStatus('We could not send your request. Please try again, or message us on WhatsApp.')
    } finally {
      setSending(false)
    }
  }

  const nights = nightsBetween(values.arrivalDate, values.departureDate)
  const guests = totalGuests(values)
  const isLast = step === STEPS.length - 1

  // ---- steps ----
  const stepTrip = (
    <div className="form-grid planner-grid">
      <div className="field">
        {label('arrivalDate', 'Arrival date', true)}
        <input type="date" min={todayIso()} {...bind('arrivalDate')} required aria-required="true" />
        {errLine('arrivalDate')}
      </div>
      <div className="field">
        {label('arrivalTime', 'Arrival time')}
        <input type="time" {...bind('arrivalTime', { hint: true })} />
        {hint('arrivalTime', 'Flight landing time, if you know it.')}
      </div>
      <div className="field">
        {label('departureDate', 'Departure date', true)}
        <input type="date" min={addDay(values.arrivalDate) || todayIso()} {...bind('departureDate')} required aria-required="true" />
        {errLine('departureDate')}
      </div>
      <div className="field">
        <span className="field-label" aria-hidden="true">Trip length</span>
        <p className="trip-length" aria-live="polite">
          <i className="bi bi-moon-stars" aria-hidden="true" />
          {nights ? <><strong>{plural(nights, 'night', 'nights')}</strong> · {plural(nights + 1, 'day', 'days')}</> : <span>Pick your dates</span>}
        </p>
      </div>
      <label className="check span-2">
        <input type="checkbox" name="flexibleDates" checked={values.flexibleDates} onChange={onField} />
        <span>My dates are flexible (we can suggest better-value or better-weather dates)</span>
      </label>
      <div className="field">
        {label('arrivalAirport', 'Arriving at')}
        <select {...bind('arrivalAirport')}>
          <option value="">Choose…</option>
          {AIRPORTS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
        </select>
      </div>
      <div className="field">
        {label('occasion', 'Occasion')}
        <select {...bind('occasion')}>
          <option value="">Choose…</option>
          {OCCASIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
        </select>
      </div>
      <div className="span-2">
        <RadioCards legend="Travel pace" name="pace" options={PACES} value={values.pace} onChange={(pace) => update({ pace })} />
      </div>
    </div>
  )

  const stepTravellers = (
    <>
      <div className="planner-sub">
        <h4>Who’s travelling?</h4>
        <span className="guest-total" aria-live="polite"><i className="bi bi-people-fill" aria-hidden="true" /> {plural(guests, 'guest', 'guests')}</span>
      </div>
      <div className="counter-grid">
        <Counter id={id('men')} label="Men" hint="18+" value={values.men} max={COUNTER_MAX}
          onChange={(men) => { update({ men }); setTouched((t) => ({ ...t, men: true })) }} error={err('men')} errorId={id('men-err')} />
        <Counter id={id('women')} label="Women" hint="18+" value={values.women} max={COUNTER_MAX}
          onChange={(women) => { update({ women }); setTouched((t) => ({ ...t, men: true })) }} />
        <Counter id={id('children')} label="Children" hint="under 18" value={values.children} max={COUNTER_MAX} onChange={setChildren} />
      </div>
      {errLine('men')}

      {values.children > 0 && (
        <fieldset className="child-ages">
          <legend>Children’s ages <span className="req" aria-hidden="true">*</span></legend>
          <p className="field-hint">Ages help us plan child seats, rooms and prices.</p>
          <div className="child-age-grid">
            {values.childAges.map((age, i) => {
              const key = `childAge-${i}`
              return (
                <div className="field" key={key}>
                  <label htmlFor={id(key)}>Child {i + 1}</label>
                  <select
                    id={id(key)}
                    value={age}
                    onChange={(e) => {
                      const childAges = values.childAges.map((a, j) => (j === i ? e.target.value : a))
                      update({ childAges }, [key])
                    }}
                    onBlur={() => setTouched((t) => ({ ...t, [key]: true }))}
                    aria-invalid={err(key) ? true : undefined}
                    aria-describedby={err(key) ? id(`${key}-err`) : undefined}
                  >
                    <option value="">Age…</option>
                    {CHILD_AGES.map((a) => <option key={a} value={String(a)}>{a === 0 ? 'Under 1' : `${a} ${a === 1 ? 'year' : 'years'}`}</option>)}
                  </select>
                  {errLine(key)}
                </div>
              )
            })}
          </div>
        </fieldset>
      )}

      <div className="planner-sub"><h4>Stay & transport</h4></div>
      <div className="form-grid planner-grid">
        <div className="field">
          {label('accommodation', 'Hotel standard')}
          <select {...bind('accommodation')}>
            {ACCOMMODATION.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
          </select>
        </div>
        {values.accommodation !== 'none' ? (
          <div className="field-pair">
            <div className="field">
              {label('rooms', 'Rooms', true)}
              <input type="number" inputMode="numeric" min={1} max={COUNTER_MAX} {...bind('rooms')}
                onChange={(e) => update({ rooms: parseInt(e.target.value, 10) || 0 })} />
              {errLine('rooms')}
            </div>
            <div className="field">
              {label('mealPlan', 'Meals')}
              <select {...bind('mealPlan')}>
                {MEAL_PLANS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
              </select>
            </div>
          </div>
        ) : <div aria-hidden="true" />}
        <div className="field">
          {label('vehicle', 'Vehicle')}
          <select {...bind('vehicle')}>
            {VEHICLES.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
          </select>
        </div>
        <div className="field">
          {label('guideLanguage', 'Guide language')}
          <select {...bind('guideLanguage')}>
            {LANGUAGES.map((l) => <option key={l} value={l}>{l}</option>)}
          </select>
        </div>
        <div className="field">
          {label('budget', 'Budget per person')}
          <select {...bind('budget', { hint: true })}>
            <option value="">Not sure yet</option>
            {BUDGETS.map((b) => <option key={b.value} value={b.value}>{budgetLabel(b.value, site.currency)}</option>)}
          </select>
          {hint('budget', 'Land tour only, excluding international flights.')}
        </div>
        <div className="field">
          {label('dietary', 'Dietary needs')}
          <input type="text" maxLength={300} placeholder="e.g. vegetarian, halal, nut allergy" {...bind('dietary')} />
          {errLine('dietary')}
        </div>
        <div className="field span-2">
          {label('accessibility', 'Accessibility or medical needs')}
          <input type="text" maxLength={300} placeholder="e.g. wheelchair user, limited walking, travelling with a baby stroller" {...bind('accessibility')} />
          {errLine('accessibility')}
        </div>
      </div>
    </>
  )

  const listNote = 'Not in the list? Write it in “Your wishes & special requests” below.'
  const stepInterests = (
    <div className="planner-stack">
      <ChoiceList
        legend="Destinations you’d love to visit"
        icon="bi-geo-alt"
        options={destOptions}
        selected={values.destinations}
        onChange={(destinations) => update({ destinations })}
        searchPlaceholder="Search places or cities…"
        note={listNote}
        loading={destLoading}
      />
      <ChoiceList
        legend="Activities you’d enjoy"
        icon="bi-binoculars"
        options={actOptions}
        selected={values.activities}
        onChange={(activities) => update({ activities })}
        searchPlaceholder="Search activities…"
        note={listNote}
        loading={actLoading}
      />
      <div className="field">
        {label('notes', 'Your wishes & special requests')}
        <p className="field-hint" id={id('notes-hint')}>
          <i className="bi bi-pencil-square" aria-hidden="true" /> Add any <strong>places or activities that aren’t listed above</strong>,
          plus anything else we should know: must-sees, celebrations, things to avoid, hotel wishes…
        </p>
        <textarea rows={5} maxLength={NOTES_MAX} {...bind('notes', { hint: true })}
          placeholder="e.g. We’d love a night in a treehouse, a surfing day in Arugam Bay and a surprise birthday dinner on 14 March." />
        <div className="field-meta">
          {errLine('notes')}
          <span className="char-count" aria-hidden="true">{values.notes.length} / {NOTES_MAX}</span>
        </div>
      </div>
    </div>
  )

  const stepContact = (
    <>
      <div className="form-grid planner-grid">
        <div className="field">
          {label('name', 'Full name', true)}
          <input type="text" autoComplete="name" maxLength={120} {...bind('name')} required aria-required="true" />
          {errLine('name')}
        </div>
        <div className="field">
          {label('email', 'Email', true)}
          <input type="email" autoComplete="email" maxLength={190} {...bind('email')} required aria-required="true" />
          {errLine('email')}
        </div>
        <div className="field">
          {label('phone', 'Phone / WhatsApp', true)}
          <input type="tel" autoComplete="tel" inputMode="tel" maxLength={30} placeholder="+44 7700 900123" {...bind('phone', { hint: true })} required aria-required="true" />
          {hint('phone', 'Include your country code.')}
          {errLine('phone')}
        </div>
        <div className="field">
          {label('country', 'Country of residence', true)}
          <input type="text" autoComplete="country-name" maxLength={80} {...bind('country')} required aria-required="true" />
          {errLine('country')}
        </div>
        <div className="span-2">
          <RadioCards legend="Best way to reach you" name="contactMethod" options={CONTACT_METHODS} value={values.contactMethod}
            onChange={(contactMethod) => update({ contactMethod })} small />
        </div>
        <div className="field span-2">
          {label('referral', 'How did you hear about us?')}
          <select {...bind('referral')}>
            <option value="">Choose…</option>
            {REFERRALS.map((r) => <option key={r} value={r}>{r}</option>)}
          </select>
        </div>
        <div className="hp" aria-hidden="true">
          <label htmlFor={id('website')}>Website</label>
          <input type="text" tabIndex={-1} autoComplete="off" {...bind('website')} />
        </div>
      </div>

      <section className="planner-review" aria-labelledby={id('review')}>
        <h4 id={id('review')}><i className="bi bi-card-checklist" aria-hidden="true" /> Your journey at a glance</h4>
        <dl>
          {summaryRows(values, summaryCtx).map(([k, v]) => (
            <div key={k}><dt>{k}</dt><dd>{v}</dd></div>
          ))}
        </dl>
      </section>
    </>
  )

  const content = [stepTrip, stepTravellers, stepInterests, stepContact][step]

  return (
    <dialog ref={dialogRef} className="planner" aria-labelledby={id('title')} onClose={handleClose}>
      <div className="planner-shell">
        <header className="planner-head">
          <div>
            <p className="eyebrow"><span className="eyebrow-line" aria-hidden="true" />Tailor-made journey</p>
            <h2 className="planner-title" id={id('title')}>Build your <em>own journey</em></h2>
          </div>
          <button type="button" className="planner-close" onClick={() => dialogRef.current?.close()} aria-label="Close">
            <i className="bi bi-x-lg" aria-hidden="true" />
          </button>
        </header>

        {!done && (
          <nav aria-label="Planner steps">
            <ol className="planner-steps">
              {STEPS.map((s, i) => (
                <li key={s.key} className={i === step ? 'current' : i < step ? 'complete' : ''}>
                  <button type="button" onClick={() => goTo(i)} disabled={i > maxStep && i > step + 1} aria-current={i === step ? 'step' : undefined}>
                    <span className="ps-num" aria-hidden="true">{i < step ? <i className="bi bi-check-lg" /> : i + 1}</span>
                    <span className="ps-label">{s.title}</span>
                    <span className="sr-only">{i < step ? ' (completed)' : ''}</span>
                  </button>
                </li>
              ))}
            </ol>
          </nav>
        )}

        {done ? (
          <div className="planner-body planner-done" ref={bodyRef}>
            <span className="done-icon" aria-hidden="true"><i className={`bi ${done === 'whatsapp' ? 'bi-whatsapp' : 'bi-check2-circle'}`} /></span>
            <h3 tabIndex={-1} ref={headingRef}>
              {done === 'whatsapp' ? 'Almost there: press send in WhatsApp' : `Thank you, ${values.name.trim().split(' ')[0]}!`}
            </h3>
            {done === 'whatsapp' ? (
              <>
                <p>We opened WhatsApp with your journey details filled in. Just press <strong>send</strong> and our team will reply with a personal itinerary and quote.</p>
                <div className="done-actions">
                  <a className="btn btn-gold" href={waLink(site.whatsapp, buildWhatsAppMessage(values, summaryCtx))} target="_blank" rel="noopener noreferrer">
                    <i className="bi bi-whatsapp" aria-hidden="true" /> Open WhatsApp again
                  </a>
                  <a className="btn btn-ghost" href={mailLink(site.email, 'My own Sri Lanka journey', buildWhatsAppMessage(values, summaryCtx))}>
                    <i className="bi bi-envelope" aria-hidden="true" /> Send by email instead
                  </a>
                </div>
                <button type="button" className="link-btn" onClick={() => setDone(null)}>Go back and edit my answers</button>
              </>
            ) : (
              <>
                <p>Your request reached our travel team. We’ll study every detail and get back to you within 24 hours with a personal itinerary and quote, free and with no obligation.</p>
                {reference && (
                  <p className="done-ref">
                    Your reference <strong>{reference}</strong>
                    <small>Mention it when you contact us.</small>
                  </p>
                )}
                <div className="done-actions">
                  <button type="button" className="btn btn-gold" onClick={() => dialogRef.current?.close()}>Done</button>
                </div>
              </>
            )}
          </div>
        ) : (
          <form className="inquiry-form planner-form" onSubmit={onSubmit} noValidate>
            <div className="planner-body" ref={bodyRef}>
              <div className={`form-status${status ? ' err' : ''}`} role="status" aria-live="polite">
                {status && <><i className="bi bi-exclamation-circle" aria-hidden="true" /> {status}</>}
              </div>
              <h3 className="planner-step-title" tabIndex={-1} ref={headingRef}>
                <small>Step {step + 1} of {STEPS.length}</small>
                <span><i className={`bi ${STEPS[step].icon}`} aria-hidden="true" /> {STEPS[step].title}</span>
              </h3>
              {content}
            </div>

            <footer className="planner-foot">
              {step > 0 ? (
                <button type="button" className="btn btn-ghost" onClick={() => goTo(step - 1)}>
                  <i className="bi bi-arrow-left" aria-hidden="true" /> Back
                </button>
              ) : <span className="planner-foot-note">Takes about 3 minutes · <span className="req">*</span> required</span>}
              <button type="submit" className="btn btn-gold" disabled={sending} aria-busy={sending || undefined}>
                {sending ? <><span className="spinner sm" aria-hidden="true" /> Sending…</>
                  : !isLast ? <>Next <i className="bi bi-arrow-right" aria-hidden="true" /></>
                    : hasJourneyBackend ? <>Send my request <i className="bi bi-send" aria-hidden="true" /></>
                      : <><i className="bi bi-whatsapp" aria-hidden="true" /> Send via WhatsApp</>}
              </button>
            </footer>
          </form>
        )}
      </div>
    </dialog>
  )
}
