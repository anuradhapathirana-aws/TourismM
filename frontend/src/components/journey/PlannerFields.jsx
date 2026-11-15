import { useId, useMemo, useState } from 'react'

/** Error line under a field, linked with aria-describedby via its id. */
export const FieldError = ({ id, children }) => (children ? <p className="field-err" id={id}>{children}</p> : null)

/** Number with −/+ buttons (44px targets). The input can also be typed into. */
export function Counter({ id, label, hint, value, min = 0, max = 30, onChange, error, errorId }) {
  const labelId = `${id}-label`
  const set = (n) => onChange(Math.min(max, Math.max(min, Number.isFinite(n) ? n : min)))
  return (
    <div className="counter-field">
      <span className="counter-label" id={labelId}>
        {label} {hint && <small>{hint}</small>}
      </span>
      <div className="counter" role="group" aria-labelledby={labelId}>
        <button type="button" onClick={() => set(value - 1)} disabled={value <= min} aria-label={`Fewer: ${label}`}>
          <i className="bi bi-dash-lg" aria-hidden="true" />
        </button>
        <input
          id={id}
          type="number"
          inputMode="numeric"
          min={min}
          max={max}
          value={value}
          onChange={(e) => set(parseInt(e.target.value, 10))}
          aria-labelledby={labelId}
          aria-invalid={error ? true : undefined}
          aria-describedby={error ? errorId : undefined}
        />
        <button type="button" onClick={() => set(value + 1)} disabled={value >= max} aria-label={`More: ${label}`}>
          <i className="bi bi-plus-lg" aria-hidden="true" />
        </button>
      </div>
    </div>
  )
}

/** Radio buttons drawn as cards. options: [{ value, label, icon?, text? }] */
export function RadioCards({ legend, name, options, value, onChange, small = false }) {
  return (
    <fieldset className={`radio-cards${small ? ' small' : ''}`}>
      <legend>{legend}</legend>
      <div className="rc-grid">
        {options.map((o) => (
          <label key={o.value} className="rc">
            <input type="radio" name={name} value={o.value} checked={value === o.value} onChange={() => onChange(o.value)} />
            <span className="rc-body">
              {o.icon && <i className={`bi ${o.icon}`} aria-hidden="true" />}
              <strong>{o.label}</strong>
              {o.text && <small>{o.text}</small>}
            </span>
          </label>
        ))}
      </div>
    </fieldset>
  )
}

/**
 * Searchable multi-select drawn as checkbox pills, optionally grouped.
 * options: [{ value, label, group? }] · selected: array of values.
 * `note` sits right under the legend (e.g. "Not in the list? Add it below").
 */
export function ChoiceList({ legend, icon, options, selected, onChange, searchPlaceholder, note, loading, error }) {
  const uid = useId()
  const [query, setQuery] = useState('')
  const picked = new Set(selected)

  const groups = useMemo(() => {
    const q = query.trim().toLowerCase()
    const map = new Map()
    options
      .filter((o) => !q || o.label.toLowerCase().includes(q) || (o.group || '').toLowerCase().includes(q))
      .forEach((o) => {
        const key = o.group || ''
        if (!map.has(key)) map.set(key, [])
        map.get(key).push(o)
      })
    return [...map.entries()]
  }, [options, query])

  const matches = groups.reduce((n, [, list]) => n + list.length, 0)
  const toggle = (value) => onChange(picked.has(value) ? selected.filter((v) => v !== value) : [...selected, value])
  const byValue = new Map(options.map((o) => [o.value, o]))

  return (
    <fieldset className="choice" aria-describedby={note ? `${uid}-note` : undefined}>
      <legend>
        {icon && <i className={`bi ${icon}`} aria-hidden="true" />} {legend}
        {selected.length > 0 && <span className="choice-count">{selected.length} selected</span>}
      </legend>
      {note && <p className="field-hint" id={`${uid}-note`}><i className="bi bi-info-circle" aria-hidden="true" /> {note}</p>}

      {selected.length > 0 && (
        <ul className="choice-picked" aria-label={`Selected: ${legend}`}>
          {selected.map((v) => byValue.get(v) && (
            <li key={v}>
              <button type="button" onClick={() => toggle(v)} aria-label={`Remove ${byValue.get(v).label}`}>
                {byValue.get(v).label} <i className="bi bi-x" aria-hidden="true" />
              </button>
            </li>
          ))}
        </ul>
      )}

      <div className="choice-search">
        <i className="bi bi-search" aria-hidden="true" />
        <input
          type="search"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder={searchPlaceholder}
          aria-label={searchPlaceholder}
          aria-controls={`${uid}-list`}
        />
      </div>
      <p className="sr-only" aria-live="polite">{query ? `${matches} match${matches === 1 ? '' : 'es'}` : ''}</p>

      <div className="choice-list" id={`${uid}-list`}>
        {loading && <p className="choice-empty"><span className="spinner sm" aria-hidden="true" /> Loading…</p>}
        {!loading && matches === 0 && (
          <p className="choice-empty">{query ? `No match for “${query}”. Add it in the wishes box below.` : 'Nothing to choose from yet.'}</p>
        )}
        {groups.map(([group, list]) => (
          <div key={group || 'all'} className="choice-group">
            {group && <p className="choice-group-title">{group}</p>}
            <div className="choice-opts">
              {list.map((o) => (
                <label key={o.value} className="choice-opt">
                  <input type="checkbox" checked={picked.has(o.value)} onChange={() => toggle(o.value)} />
                  <span><i className="bi bi-check-lg" aria-hidden="true" />{o.label}</span>
                </label>
              ))}
            </div>
          </div>
        ))}
      </div>
      {error && <p className="field-err">{error}</p>}
    </fieldset>
  )
}
