import { Link } from 'react-router-dom'
import { usePageTitle } from '@/hooks/usePageTitle'

export default function NotFound({ title = 'Page not found' }) {
  usePageTitle(title)
  return (
    <section className="page-hero small">
      <div className="container">
        <p className="eyebrow"><span className="eyebrow-line" aria-hidden="true" />404</p>
        <h1>{title}</h1>
        <p className="section-sub left">The page you are looking for may have moved. Let’s get you back on the road.</p>
        <div className="hero-actions">
          <Link className="btn btn-gold" to="/">Back to home</Link>
          <Link className="btn btn-ghost" to="/#tours">Browse tours</Link>
        </div>
      </div>
    </section>
  )
}
