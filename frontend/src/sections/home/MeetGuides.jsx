import { useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import SectionHeading from '@/components/ui/SectionHeading'
import { firstName, waLink } from '@/utils/format'

/** "Meet Your Guide": a featured profile plus a tab list to switch between guides. */
export default function MeetGuides({ guides = [] }) {
  const [active, setActive] = useState(0)
  const tabsRef = useRef([])
  if (!guides.length) return null
  const g = guides[active]

  // Arrow-key navigation between tabs (WAI-ARIA tabs pattern)
  const onKeyDown = (e) => {
    const dir = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key]
    let next = null
    if (dir) next = (active + dir + guides.length) % guides.length
    if (e.key === 'Home') next = 0
    if (e.key === 'End') next = guides.length - 1
    if (next === null) return
    e.preventDefault()
    setActive(next)
    tabsRef.current[next]?.focus()
  }

  return (
    <section className="section section-dark guides" id="guides">
      <div className="container">
        <SectionHeading
          center
          eyebrow="Meet your guide"
          title="The people who make the"
          highlight="journey"
          sub="Licensed, friendly and full of stories. Choose the guide who matches your travel style."
        />

        <div
          className="guide-feature fade-swap"
          role="tabpanel"
          id="guide-panel"
          aria-labelledby={`guide-tab-${g.id}`}
          key={g.id}
        >
          <div className="guide-photo">
            <img src={g.photo} alt={`Portrait of ${g.name}`} width="800" height="1000" />
            <div className="guide-rating">
              <i className="bi bi-star-fill" aria-hidden="true" /> {g.rating.toFixed(1)}
              <span className="sr-only"> out of 5</span>
            </div>
          </div>
          <div className="guide-body">
            <p className="guide-role">{g.role}</p>
            <h3 className="guide-name">{g.name}</h3>
            <p className="guide-bio">{g.bio}</p>
            <div className="guide-stats">
              <div><strong>{g.years}+</strong><span>Years guiding</span></div>
              <div><strong>{g.toursLed}+</strong><span>Tours led</span></div>
              <div><strong>{g.languages.length}</strong><span>Languages</span></div>
            </div>
            <div className="chip-row">
              <span className="chip-label">Speaks</span>
              <span className="chips">{g.languages.map((l) => <span className="chip" key={l}>{l}</span>)}</span>
            </div>
            <div className="chip-row">
              <span className="chip-label">Loves</span>
              <span className="chips">{g.specialties.map((l) => <span className="chip chip-outline" key={l}>{l}</span>)}</span>
            </div>
            <div className="guide-actions">
              <a
                className="btn btn-gold"
                href={waLink(g.whatsapp, `Hello ${g.name}, I would like to book a tour with you.`)}
                target="_blank"
                rel="noopener noreferrer"
              >
                <i className="bi bi-whatsapp" aria-hidden="true" /> Chat with {firstName(g.name)}
              </a>
              <Link className="btn btn-ghost" to="/#contact">Request this guide</Link>
            </div>
          </div>
        </div>

        {guides.length > 1 && (
          <div className="guide-list" role="tablist" aria-label="Choose a guide" onKeyDown={onKeyDown}>
            {guides.map((x, i) => (
              <button
                key={x.id}
                ref={(el) => (tabsRef.current[i] = el)}
                id={`guide-tab-${x.id}`}
                type="button"
                role="tab"
                aria-selected={i === active}
                aria-controls="guide-panel"
                tabIndex={i === active ? 0 : -1}
                className={`guide-thumb${i === active ? ' active' : ''}`}
                onClick={() => setActive(i)}
              >
                <img src={x.photo} alt="" loading="lazy" width="56" height="56" />
                <span><strong>{x.name}</strong><small>{x.role}</small></span>
              </button>
            ))}
          </div>
        )}
      </div>
    </section>
  )
}
