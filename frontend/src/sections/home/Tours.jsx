import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import SectionHeading from '@/components/ui/SectionHeading'
import TourCard from '@/components/ui/TourCard'
import { unique } from '@/utils/format'

export default function Tours({ tours = [] }) {
  const [cat, setCat] = useState('')
  const cats = useMemo(() => unique(tours.map((t) => t.category)), [tours])
  const sorted = useMemo(() => [...tours].sort((a, b) => Number(b.featured) - Number(a.featured)), [tours])
  const shown = sorted.filter((t) => !cat || t.category === cat)

  return (
    <section className="section section-dark" id="tours">
      <div className="container">
        <SectionHeading center eyebrow="Tour packages" title="Discover Sri Lanka" highlight="your way" />
        <div className="filter-tabs reveal" role="group" aria-label="Filter tours by category">
          {['', ...cats].map((c) => (
            <button key={c || 'all'} type="button" className={cat === c ? 'active' : ''} aria-pressed={cat === c} onClick={() => setCat(c)}>
              {c || 'All Packages'}
            </button>
          ))}
        </div>
        <p className="sr-only" aria-live="polite">{shown.length} tours shown</p>
        <div className="tour-grid">
          {shown.map((t) => <TourCard key={t.slug} tour={t} className="fade-swap" />)}
        </div>
        <p className="center muted-note">
          Don’t see quite what you want? <Link to="/#contact">Let’s build your own journey together →</Link>
        </p>
      </div>
    </section>
  )
}
