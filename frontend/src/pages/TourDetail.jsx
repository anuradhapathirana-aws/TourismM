import { Link, useParams } from 'react-router-dom'
import { useApi } from '@/hooks/useApi'
import { usePageTitle } from '@/hooks/usePageTitle'
import { useSite } from '@/context/SiteContext'
import { getTour, getTours } from '@/services/api'
import { money, tourCategoryLabel, tourCategorySlug } from '@/utils/format'
import InquiryForm from '@/components/ui/InquiryForm'
import TourCard from '@/components/ui/TourCard'
import TourRouteMap from '@/components/ui/TourRouteMap'
import PageLoader from '@/components/ui/PageLoader'
import NotFound from './NotFound'

/**
 * Keeps the enquiry card straddling the hero edge: publishes half the card's height as `--lift`
 * on `.tour-top`, which the CSS uses to pull the card up by exactly that much.
 */
function liftCard(node) {
  const top = node?.closest('.tour-top')
  if (!top || typeof ResizeObserver === 'undefined') return
  const observer = new ResizeObserver(() => top.style.setProperty('--lift', `${Math.round(node.offsetHeight / 2)}px`))
  observer.observe(node)
  return () => observer.disconnect()
}

export default function TourDetail() {
  const { slug } = useParams()
  const site = useSite()
  const { data: tour, loading, error } = useApi(() => getTour(slug), [slug])
  const { data: all } = useApi(getTours)
  usePageTitle(tour?.title)

  if (loading) return <PageLoader />
  if (error) {
    return (
      <section className="page-hero small">
        <div className="container">
          <h1>Something went wrong</h1>
          <p>We could not load this tour. Please refresh the page or <Link to="/#contact">contact us</Link>.</p>
        </div>
      </section>
    )
  }
  if (!tour) return <NotFound title="Tour not found" />

  const currency = tour.currency || site.currency
  const from = tour.price != null ? money(tour.price, currency) : 'On request'
  const sameCategory = (t) => Number(tourCategorySlug(t) === tourCategorySlug(tour))

  const related = (all || [])
    .filter((t) => t.slug !== tour.slug)
    .sort((a, b) => sameCategory(b) - sameCategory(a))
    .slice(0, 3)

  return (
    <>
      <div className="tour-top">
        <section className="page-hero tour-hero" style={{ '--hero-img': `url('${tour.imageLarge || tour.image}')` }}>
          <div className="container">
            <nav className="crumbs" aria-label="Breadcrumb">
              <Link to="/">Home</Link> / <Link to="/tours">Tours</Link> / <span aria-current="page">{tour.title}</span>
            </nav>
            <span className="tag static">{tourCategoryLabel(tour)}</span>
            <h1>{tour.title}</h1>
            <ul className="tour-facts">
              <li><i className="bi bi-clock" aria-hidden="true" />{tour.duration}</li>
              <li><i className="bi bi-car-front" aria-hidden="true" />Private vehicle</li>
              <li><i className="bi bi-person-badge" aria-hidden="true" />Chauffeur-guide</li>
              <li><i className="bi bi-tag" aria-hidden="true" />From {from}{tour.price != null && ' pp'}</li>
            </ul>
          </div>
        </section>

        <section className="section section-light tour-details">
          <div className="container tour-layout">
            <div className="tour-main">
              <h2 className="section-title sm">Overview</h2>
              <p className="lead-muted">
                {tour.summary} This private tour is fully customisable: change the pace, upgrade hotels or add
                extra days. Your chauffeur-guide stays with you throughout, handling every detail so you can
                simply enjoy the journey.
              </p>

              {tour.highlights?.length > 0 && (
                <>
                  <h2 className="section-title sm">Highlights</h2>
                  <ul className="check-list two-col">
                    {tour.highlights.map((h) => <li key={h}><i className="bi bi-check2-circle" aria-hidden="true" />{h}</li>)}
                  </ul>
                </>
              )}

              {tour.itinerary?.length > 0 && (
                <>
                  <h2 className="section-title sm">Itinerary</h2>
                  <ol className="itinerary">
                    {tour.itinerary.map((d, i) => (
                      <li key={i}>
                        <span className="day">Day {i + 1}</span>
                        <div><h3>{d.title}</h3>{d.details && <p>{d.details}</p>}</div>
                      </li>
                    ))}
                  </ol>
                </>
              )}

              {tour.inclusions?.length > 0 && (
                <>
                  <h2 className="section-title sm">What’s included</h2>
                  <ul className="check-list">
                    {tour.inclusions.map((h) => <li key={h}><i className="bi bi-check-lg" aria-hidden="true" />{h}</li>)}
                  </ul>
                </>
              )}
            </div>

            <aside className="tour-side" aria-labelledby="enquire-title">
              <div className="side-card" ref={liftCard}>
                <div className="side-head">
                  <div>
                    <span className="eyebrow">Private tour</span>
                    <h2 className="side-title" id="enquire-title">Enquire about this tour</h2>
                  </div>
                  <div className="side-price">
                    <small>From</small>
                    <strong>{from}</strong>
                    {tour.price != null && <small>per person</small>}
                  </div>
                </div>
                <InquiryForm fixedTour={tour} compact />
              </div>
            </aside>

            <TourRouteMap itinerary={tour.itinerary} className="tour-route" />
          </div>
        </section>
      </div>

      {related.length > 0 && (
        <section className="section section-dark">
          <div className="container">
            <div className="section-head center"><h2 className="section-title">You may also <em>love</em></h2></div>
            <div className="tour-grid">
              {related.map((t) => <TourCard key={t.slug} tour={t} />)}
            </div>
          </div>
        </section>
      )}
    </>
  )
}
