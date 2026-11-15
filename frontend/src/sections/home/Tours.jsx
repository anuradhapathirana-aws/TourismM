import { useMemo } from 'react'
import { Link } from 'react-router-dom'
import SectionHeading from '@/components/ui/SectionHeading'
import TourBrowser from '@/components/ui/TourBrowser'
import PlanJourneyButton from '@/components/journey/PlanJourneyButton'

/** Home: featured tours only. "Show more tours" opens the full list at /tours. */
const FALLBACK_COUNT = 6

export default function Tours({ tours = [], categories = [] }) {
  // No tour marked featured yet → show the first few so the section is never empty.
  const featured = useMemo(() => {
    const picked = tours.filter((t) => t.featured)
    return picked.length ? picked : tours.slice(0, FALLBACK_COUNT)
  }, [tours])

  return (
    <section className="section section-dark" id="tours">
      <div className="container">
        <SectionHeading center eyebrow="Tour packages" title="Discover Sri Lanka" highlight="your way" />
        <TourBrowser tours={featured} categories={categories} />
        {tours.length > 0 && (
          <div className="center tours-more">
            <Link className="btn btn-ghost" to="/tours">
              Show more tours <i className="bi bi-arrow-right" aria-hidden="true" />
            </Link>
          </div>
        )}
        <p className="center muted-note">
          Don’t see quite what you want? <PlanJourneyButton />
        </p>
      </div>
    </section>
  )
}
