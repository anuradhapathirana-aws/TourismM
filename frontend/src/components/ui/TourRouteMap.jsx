import { useEffect, useId, useMemo, useRef, useState } from 'react'
import { MAP_H, MAP_W, SRI_LANKA_PATH, project } from '@/utils/geo'

const PIN_GAP = 40 // min distance between pin centres (viewBox units ≈ 26px at the map's size)
const EDGE = 20 // pin radius margin from the map edges (viewBox units)
const clamp = (v, max) => Math.min(max - EDGE, Math.max(EDGE, v))

/**
 * Every place the tour visits, in itinerary order (first visit wins), with the days it appears on.
 * Places without valid Sri Lankan coordinates are skipped.
 */
function routeStops(itinerary = []) {
  const stops = new Map()
  itinerary.forEach((day, i) => {
    const dayNo = day.day ?? i + 1
    ;(day.destinations || []).forEach((p) => {
      const key = p.id ?? p.slug ?? p.name
      if (stops.has(key)) {
        const days = stops.get(key).days
        if (!days.includes(dayNo)) days.push(dayNo)
        return
      }
      const pos = project(p.lat, p.lng)
      if (pos) stops.set(key, { ...p, key, pos, days: [dayNo] })
    })
  })
  return [...stops.values()].map((s, i) => ({ ...s, n: i + 1 }))
}

/** Push pins that sit on top of each other apart (places a few km apart overlap at this scale). */
function spreadPins(stops) {
  const pins = stops.map((s) => ({ x: s.pos.x, y: s.pos.y }))
  for (let iter = 0; iter < 80; iter++) {
    let moved = false
    for (let a = 0; a < pins.length; a++) {
      for (let b = a + 1; b < pins.length; b++) {
        let dx = pins[b].x - pins[a].x
        let dy = pins[b].y - pins[a].y
        let dist = Math.hypot(dx, dy)
        if (dist >= PIN_GAP) continue
        if (dist < 0.01) {
          // identical spot: fan out at a fixed angle per pin
          const angle = (b * 2.4) % (Math.PI * 2)
          dx = Math.cos(angle)
          dy = Math.sin(angle)
          dist = 1
        }
        const push = (PIN_GAP - dist) / 2
        pins[a].x -= (dx / dist) * push
        pins[a].y -= (dy / dist) * push
        pins[b].x += (dx / dist) * push
        pins[b].y += (dy / dist) * push
        moved = true
      }
    }
    // keep pins inside the map on every pass, so crowded coastal places spread along the coast instead
    pins.forEach((p) => {
      p.x = clamp(p.x, MAP_W)
      p.y = clamp(p.y, MAP_H)
    })
    if (!moved) break
  }
  return stops.map((s, i) => ({ ...s, pin: pins[i] }))
}

const pct = (v, total) => `${(v / total) * 100}%`
const dayText = (days) => (days.length > 1 ? `Days ${days.join(', ')}` : `Day ${days[0]}`)

/** Which side the popup opens on, so it stays inside the map column. */
function popupPlacement(pin) {
  const h = pin.x < MAP_W * 0.38 ? 'h-start' : pin.x > MAP_W * 0.62 ? 'h-end' : 'h-mid'
  const v = pin.y < MAP_H * 0.42 ? 'below' : 'above'
  return `${h} ${v}`
}

/**
 * Illustrated Sri Lanka map for one tour: every itinerary place as a numbered pin, joined in visiting order.
 * Hover, keyboard focus or tap on a pin opens a small popup with the place's photo and details.
 * Renders nothing when no itinerary place has coordinates.
 */
export default function TourRouteMap({ itinerary, className = '' }) {
  const uid = useId().replace(/:/g, '')
  const rootRef = useRef(null)
  const stops = useMemo(() => spreadPins(routeStops(itinerary)), [itinerary])
  const [active, setActive] = useState(null)

  // touch: tapping anywhere outside the map closes the popup; Escape closes it too
  useEffect(() => {
    if (active == null) return
    const onDown = (e) => !rootRef.current?.contains(e.target) && setActive(null)
    const onKey = (e) => e.key === 'Escape' && setActive(null)
    document.addEventListener('pointerdown', onDown)
    document.addEventListener('keydown', onKey)
    return () => {
      document.removeEventListener('pointerdown', onDown)
      document.removeEventListener('keydown', onKey)
    }
  }, [active])

  if (!stops.length) return null

  // drawn through the (spread) pins so the route stays visible inside clusters of nearby places
  const route = stops.map((s) => `${s.pin.x.toFixed(1)},${s.pin.y.toFixed(1)}`).join(' ')
  const ids = { land: `${uid}-land`, clip: `${uid}-clip`, grain: `${uid}-grain` }

  return (
    <figure ref={rootRef} className={`route-map ${className}`}>
      <figcaption className="sr-only">
        Map of the {stops.length} places on this tour. Hover, focus or tap a numbered pin for details.
      </figcaption>

      <svg className="route-svg" viewBox={`0 0 ${MAP_W} ${MAP_H}`} aria-hidden="true" focusable="false">
        <defs>
          <linearGradient id={ids.land} x1="0" y1="0" x2="0.7" y2="1">
            <stop offset="0" className="rm-land-a" />
            <stop offset="1" className="rm-land-b" />
          </linearGradient>
          <filter id={ids.grain}>
            <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" seed="3" />
            <feColorMatrix values="0 0 0 0 0.78  0 0 0 0 0.64  0 0 0 0 0.42  0 0 0 0.5 0" />
            <feComposite in2="SourceGraphic" operator="in" />
          </filter>
          <clipPath id={ids.clip}><path d={SRI_LANKA_PATH} /></clipPath>
        </defs>

        <path d={SRI_LANKA_PATH} className="rm-contour c3" />
        <path d={SRI_LANKA_PATH} className="rm-contour c2" />
        <path d={SRI_LANKA_PATH} className="rm-contour c1" />
        <path d={SRI_LANKA_PATH} className="rm-shadow" />
        <path d={SRI_LANKA_PATH} className="rm-land" fill={`url(#${ids.land})`} />
        <g clipPath={`url(#${ids.clip})`}>
          <rect width={MAP_W} height={MAP_H} filter={`url(#${ids.grain})`} opacity="0.22" />
        </g>
        <path d={SRI_LANKA_PATH} className="rm-coast" />

        <g className="rm-compass" transform="translate(352 60)">
          <circle r="17" />
          <path d="M0 -13 L4 0 L0 13 L-4 0 Z" />
          <text y="-22" textAnchor="middle">N</text>
        </g>

        {stops.length > 1 && <polyline className="rm-route" points={route} />}

        {stops.map((s) => (
          <g key={s.key}>
            {Math.hypot(s.pin.x - s.pos.x, s.pin.y - s.pos.y) > 3 && (
              <line className="rm-leader" x1={s.pos.x} y1={s.pos.y} x2={s.pin.x} y2={s.pin.y} />
            )}
            <circle className="rm-dot" cx={s.pos.x} cy={s.pos.y} r="4" />
          </g>
        ))}
      </svg>

      {stops.map((s, i) => {
        const open = active === s.key
        const popupId = `${uid}-pop-${i}`
        const meta = [s.category, s.city].filter(Boolean).join(' · ')
        return (
          <div
            key={s.key}
            className={`route-stop${open ? ' open' : ''}`}
            style={{ left: pct(s.pin.x, MAP_W), top: pct(s.pin.y, MAP_H), '--i': i }}
            onMouseEnter={() => setActive(s.key)}
            onMouseLeave={() => setActive(null)}
          >
            <button
              type="button"
              className="route-pin"
              aria-label={`${s.n}. ${s.name}, ${dayText(s.days)}`}
              aria-expanded={open}
              aria-describedby={open ? popupId : undefined}
              onFocus={() => setActive(s.key)}
              onBlur={() => setActive(null)}
              onClick={() => setActive(s.key)}
            >
              {s.n}
            </button>

            {open && (
              <div className={`route-pop ${popupPlacement(s.pin)}`} id={popupId} role="tooltip">
                {s.image && <img src={s.image} alt="" width="480" height="300" loading="lazy" decoding="async" />}
                <div className="route-pop-body">
                  <span className="route-pop-day">{dayText(s.days)}</span>
                  <strong>{s.name}</strong>
                  {meta && <small>{meta}</small>}
                  {s.description && <p>{s.description}</p>}
                </div>
              </div>
            )}
          </div>
        )
      })}
    </figure>
  )
}
