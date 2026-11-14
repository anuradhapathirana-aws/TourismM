import { useEffect, useMemo, useState } from 'react'
import { MAP_H, MAP_W, SRI_LANKA_PATH, project } from '@/utils/geo'

const LABEL_GAP = 27 // min vertical distance between labels on one side (viewBox units)

/** Decorative hill/ridge strokes over the central highlands (viewBox units). */
const RIDGES = [
  [150, 405], [182, 395], [214, 412], [168, 440], [205, 448], [238, 436], [190, 474],
  [226, 482], [258, 470], [175, 510], [212, 520], [248, 512], [140, 470], [270, 540],
  [120, 300], [210, 300], [260, 360], [110, 560], [300, 420], [160, 590],
]

/**
 * Place labels left/right of their point and push them apart vertically so they never overlap.
 * Returns points with { side, ly } (label y in viewBox units).
 */
function layoutLabels(points) {
  const out = []
  for (const side of ['left', 'right']) {
    const group = points
      .filter((p) => (p.pos.x < MAP_W * 0.5 ? 'left' : 'right') === side)
      .sort((a, b) => a.pos.y - b.pos.y)
    let prev = -Infinity
    const placed = group.map((p) => {
      const ly = Math.max(p.pos.y, prev + LABEL_GAP)
      prev = ly
      return { ...p, side, ly }
    })
    // if the stack ran off the bottom, push it back up
    let overflow = (placed.at(-1)?.ly ?? 0) - (MAP_H - 8)
    for (let i = placed.length - 1; i >= 0 && overflow > 0; i--) {
      placed[i].ly -= overflow
      const next = placed[i - 1]
      overflow = next ? next.ly - (placed[i].ly - LABEL_GAP) : 0
    }
    out.push(...placed)
  }
  return out
}

const pct = (v, total) => `${(v / total) * 100}%`

/**
 * "Discover your perfect destination": theme cards around an illustrated Sri Lanka map.
 * Hovering, focusing or tapping a card reveals that theme's destination points.
 * `selected` ({ id, n }) comes from the destination mosaic and spotlights a single place.
 */
export default function DestinationMap({ themes = [], destinations = [], selected }) {
  const [active, setActive] = useState(null)
  const [hover, setHover] = useState(null)
  const [focusId, setFocusId] = useState(null)

  useEffect(() => {
    if (!active && themes.length) setActive(themes[0].slug)
  }, [themes, active])

  useEffect(() => {
    if (!selected) return
    setFocusId(selected.id)
    setHover(null)
  }, [selected])

  const points = useMemo(
    () => destinations.map((d) => ({ ...d, pos: project(d.lat, d.lng) })).filter((d) => d.pos),
    [destinations],
  )
  const counts = useMemo(() => {
    const c = {}
    points.forEach((p) => (p.themes || []).forEach((t) => (c[t] = (c[t] || 0) + 1)))
    return c
  }, [points])

  const current = hover ?? active
  const focusPoint = focusId ? points.find((p) => p.id === focusId) : null
  const shown = focusPoint ? [focusPoint] : points.filter((p) => p.themes?.includes(current))
  const labelled = layoutLabels(shown)
  const shownIds = new Set(shown.map((p) => p.id))
  const currentTheme = themes.find((t) => t.slug === current)
  const groupKey = focusPoint ? `f-${focusPoint.id}-${selected?.n}` : current

  const preview = (slug) => {
    setHover(slug)
    setFocusId(null)
  }

  const card = (t) => (
    <button
      key={t.slug}
      type="button"
      className={`theme-card side-${t.side === 'right' ? 'right' : 'left'}${!focusPoint && current === t.slug ? ' active' : ''}`}
      aria-pressed={!focusPoint && active === t.slug}
      aria-controls="lk-map"
      onMouseEnter={() => preview(t.slug)}
      onMouseLeave={() => setHover(null)}
      onFocus={() => preview(t.slug)}
      onBlur={() => setHover(null)}
      onClick={() => {
        setActive(t.slug)
        setFocusId(null)
      }}
    >
      <span className="theme-img"><img src={t.image} alt="" loading="lazy" width="150" height="150" /></span>
      <span className="theme-text">
        <span className="theme-title">{t.title}</span>
        <small>{counts[t.slug] || 0} places</small>
      </span>
    </button>
  )

  const left = themes.filter((t) => t.side !== 'right')
  const right = themes.filter((t) => t.side === 'right')

  return (
    <section className="section section-light explore" id="map">
      <div className="container">
        <div className="section-head center reveal">
          <p className="eyebrow dark"><span className="eyebrow-line" aria-hidden="true" />Explore Sri Lanka<span className="eyebrow-line" aria-hidden="true" /></p>
          <h2 className="section-title">Discover your perfect <em>destination</em></h2>
          <p className="lead-muted">Hover or tap a travel style to see where it takes you on the island.</p>
        </div>

        <div className="explore-grid">
          <div className="theme-col theme-col-left">{left.map(card)}</div>

          <div className="lk-map-wrap reveal">
            <div className="lk-map" id="lk-map">
              <svg className="lk-svg" viewBox={`0 0 ${MAP_W} ${MAP_H}`} aria-hidden="true" focusable="false">
                <defs>
                  <linearGradient id="lkLand" x1="0" y1="0" x2="0.6" y2="1">
                    <stop offset="0" stopColor="#d3dd92" />
                    <stop offset="0.45" stopColor="#a9c86f" />
                    <stop offset="1" stopColor="#6fa35a" />
                  </linearGradient>
                  <filter id="lkGlow" x="-30%" y="-30%" width="160%" height="160%">
                    <feGaussianBlur stdDeviation="16" />
                  </filter>
                  <filter id="lkGrain">
                    <feTurbulence type="fractalNoise" baseFrequency="0.85" numOctaves="2" seed="7" />
                    <feColorMatrix values="0 0 0 0 0.15  0 0 0 0 0.25  0 0 0 0 0.05  0 0 0 0.55 0" />
                    <feComposite in2="SourceGraphic" operator="in" />
                  </filter>
                  <clipPath id="lkClip"><path d={SRI_LANKA_PATH} /></clipPath>
                </defs>

                {/* sea glow + coastal contour rings */}
                <path d={SRI_LANKA_PATH} className="lk-sea-glow" filter="url(#lkGlow)" />
                <path d={SRI_LANKA_PATH} className="lk-contour c3" />
                <path d={SRI_LANKA_PATH} className="lk-contour c2" />
                <path d={SRI_LANKA_PATH} className="lk-contour c1" />

                {/* land */}
                <path d={SRI_LANKA_PATH} fill="url(#lkLand)" />
                <g clipPath="url(#lkClip)">
                  <rect width={MAP_W} height={MAP_H} filter="url(#lkGrain)" opacity="0.35" />
                  {RIDGES.map(([x, y], i) => (
                    <path key={i} className="lk-ridge" d={`M${x - 14} ${y + 4} q7 -8 14 -4 q7 4 14 -2`} />
                  ))}
                </g>
                <path d={SRI_LANKA_PATH} className="lk-coast" />

                {/* compass */}
                <g className="lk-compass" transform="translate(352 60)">
                  <circle r="17" />
                  <path d="M0 -13 L4 0 L0 13 L-4 0 Z" />
                  <text y="-21" textAnchor="middle">N</text>
                </g>

                {/* leader lines for labels nudged away from their point */}
                <g key={`lines-${groupKey}`}>
                  {labelled.filter((p) => Math.abs(p.ly - p.pos.y) > 2).map((p) => (
                    <line
                      key={p.id}
                      className="lk-leader"
                      x1={p.pos.x}
                      y1={p.pos.y}
                      x2={p.pos.x + (p.side === 'left' ? -10 : 10)}
                      y2={p.ly}
                    />
                  ))}
                </g>
              </svg>

              {/* every point as a faint dot for context */}
              {points.filter((p) => !shownIds.has(p.id)).map((p) => (
                <span key={p.id} className="lk-dot" style={{ left: pct(p.pos.x, MAP_W), top: pct(p.pos.y, MAP_H) }} aria-hidden="true" />
              ))}

              {/* highlighted points + labels (remounted per theme so they animate in) */}
              <div key={groupKey} className="lk-points">
                {labelled.map((p, i) => (
                  <div key={p.id} style={{ '--i': i }}>
                    <span className="lk-pin" style={{ left: pct(p.pos.x, MAP_W), top: pct(p.pos.y, MAP_H) }} title={p.name} />
                    <span
                      className={`lk-label ${p.side}${focusPoint ? ' focus' : ''}`}
                      style={{ left: pct(p.pos.x + (p.side === 'left' ? -10 : 10), MAP_W), top: pct(p.ly, MAP_H) }}
                    >
                      {p.name}
                    </span>
                  </div>
                ))}
              </div>
            </div>

            <p className="sr-only" aria-live="polite">
              {focusPoint
                ? `Showing ${focusPoint.name} on the map.`
                : currentTheme && `${currentTheme.title}: ${shown.map((p) => p.name).join(', ')}.`}
            </p>
            {focusPoint && (
              <button type="button" className="lk-reset" onClick={() => setFocusId(null)}>
                <i className="bi bi-x-lg" aria-hidden="true" /> Back to {themes.find((t) => t.slug === active)?.title || 'themes'}
              </button>
            )}
          </div>

          <div className="theme-col theme-col-right">{right.map(card)}</div>
        </div>
      </div>
    </section>
  )
}
