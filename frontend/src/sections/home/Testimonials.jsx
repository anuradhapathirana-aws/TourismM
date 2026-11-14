import { useCallback, useEffect, useState } from 'react'
import { useSite } from '@/context/SiteContext'
import SectionHeading from '@/components/ui/SectionHeading'

const AUTOPLAY_MS = 7000

/** Review carousel: auto-rotates, pauses on hover/focus, has a pause button and respects reduced motion. */
export default function Testimonials({ items = [] }) {
  const { stats, founded } = useSite()
  const [index, setIndex] = useState(0)
  const [paused, setPaused] = useState(
    () => typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches,
  )
  const [hover, setHover] = useState(false)
  const count = items.length

  const go = useCallback((i) => setIndex(((i % count) + count) % count), [count])

  useEffect(() => {
    if (paused || hover || count < 2) return
    const t = setInterval(() => setIndex((i) => (i + 1) % count), AUTOPLAY_MS)
    return () => clearInterval(t)
  }, [paused, hover, count])

  if (!count) return null

  return (
    <section className="section section-light" id="reviews">
      <div className="container">
        <SectionHeading center dark eyebrow="Testimonials" title="What our travellers" highlight="say" />
        <div className="reviews-grid">
          <div
            className="review-slider reveal"
            role="region"
            aria-roledescription="carousel"
            aria-label="Traveller reviews"
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            onFocus={() => setHover(true)}
            onBlur={() => setHover(false)}
          >
            <div className="review-viewport">
              <div className="review-track" style={{ transform: `translateX(-${index * 100}%)` }}>
                {items.map((t, i) => (
                  <figure
                    className="review"
                    key={t.id}
                    role="group"
                    aria-roledescription="slide"
                    aria-label={`${i + 1} of ${count}`}
                    aria-hidden={i !== index}
                  >
                    <div className="stars" aria-label={`${t.rating} out of 5 stars`} role="img">
                      {Array.from({ length: t.rating }, (_, k) => <i key={k} className="bi bi-star-fill" aria-hidden="true" />)}
                    </div>
                    <blockquote>“{t.message}”</blockquote>
                    <figcaption>
                      {t.photo && <img src={t.photo} alt="" loading="lazy" width="54" height="54" />}
                      <span><strong>{t.name}</strong><small>{t.country}</small></span>
                      <i className="bi bi-quote quote-mark" aria-hidden="true" />
                    </figcaption>
                  </figure>
                ))}
              </div>
            </div>
            <div className="slider-nav">
              <button type="button" onClick={() => go(index - 1)} aria-label="Previous review"><i className="bi bi-arrow-left" aria-hidden="true" /></button>
              <div className="dots">
                {items.map((t, i) => (
                  <button
                    key={t.id}
                    type="button"
                    className={i === index ? 'active' : ''}
                    aria-label={`Show review ${i + 1}`}
                    aria-current={i === index}
                    onClick={() => go(i)}
                  />
                ))}
              </div>
              <button type="button" onClick={() => setPaused((p) => !p)} aria-label={paused ? 'Start automatic rotation' : 'Pause automatic rotation'}>
                <i className={`bi ${paused ? 'bi-play' : 'bi-pause'}`} aria-hidden="true" />
              </button>
              <button type="button" onClick={() => go(index + 1)} aria-label="Next review"><i className="bi bi-arrow-right" aria-hidden="true" /></button>
            </div>
          </div>

          <aside className="rating-card reveal" aria-label="Review summary">
            <div><strong>{stats.reviews}+</strong><span>Verified 5-star reviews</span></div>
            <div><strong>{stats.travellers.toLocaleString()}+</strong><span>Happy travellers since {founded}</span></div>
            <div><strong>{stats.rating} <i className="bi bi-star-fill" aria-hidden="true" /></strong><span>Average rating</span></div>
          </aside>
        </div>
      </div>
    </section>
  )
}
