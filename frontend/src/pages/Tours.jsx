import { Link, useSearchParams } from 'react-router-dom'
import { useApi } from '@/hooks/useApi'
import { usePageTitle } from '@/hooks/usePageTitle'
import { getTourCategories, getTours } from '@/services/api'
import TourBrowser from '@/components/ui/TourBrowser'
import PageLoader from '@/components/ui/PageLoader'

/** /tours: every published tour, filterable by category. ?category=beach-tours links straight to a tab. */
export default function ToursPage() {
  usePageTitle('Tour packages')
  const [params, setParams] = useSearchParams()
  const { data: tours, loading: toursLoading, error } = useApi(getTours)
  // wait for the admin's category order too, so the tabs don't jump once it arrives
  const { data: categories, loading: categoriesLoading } = useApi(getTourCategories)
  const loading = toursLoading || categoriesLoading
  const category = params.get('category') || ''

  const setCategory = (slug) =>
    setParams((p) => {
      const next = new URLSearchParams(p)
      if (slug) next.set('category', slug)
      else next.delete('category')
      return next
    }, { replace: true, preventScrollReset: true })

  const cover = (tours || []).find((t) => t.featured && t.image)?.image || tours?.[0]?.image

  return (
    <>
      <section className="page-hero small" style={cover ? { '--hero-img': `url('${cover}')` } : undefined}>
        <div className="container">
          <nav className="crumbs" aria-label="Breadcrumb">
            <Link to="/">Home</Link> / <span aria-current="page">Tours</span>
          </nav>
          <p className="eyebrow"><span className="eyebrow-line" aria-hidden="true" />Tour packages</p>
          <h1>Discover Sri Lanka <em>your way</em></h1>
          <p className="page-hero-lead">
            Private, fully guided journeys with your own chauffeur-guide. Pick a package as it is, or let us tailor it to you.
          </p>
        </div>
      </section>

      <section className="section section-dark" id="tours">
        <div className="container">
          {loading && <PageLoader />}
          {error && (
            <p className="center muted-note" role="alert">
              We could not load the tours. Please refresh the page or <Link to="/#contact">contact us</Link>.
            </p>
          )}
          {!loading && !error && (tours?.length ? (
            <TourBrowser tours={tours} categories={categories || []} value={category} onChange={setCategory} />
          ) : (
            <p className="center muted-note">New tours are coming soon. <Link to="/#contact">Tell us what you’d like to see →</Link></p>
          ))}
          <p className="center muted-note">
            Don’t see quite what you want? <Link to="/#contact">Let’s build your own journey together →</Link>
          </p>
        </div>
      </section>
    </>
  )
}
