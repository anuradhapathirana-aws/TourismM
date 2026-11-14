import { Link } from 'react-router-dom'
import { useSite } from '@/context/SiteContext'
import { money, resized } from '@/utils/format'

export default function TourCard({ tour, className = '' }) {
  const { currency } = useSite()
  const href = `/tours/${tour.slug}`

  return (
    <article className={`tour-card ${className}`}>
      <Link className="tour-img" to={href} tabIndex={-1} aria-hidden="true">
        <img src={resized(tour.image, 800)} alt="" loading="lazy" width="800" height="600" />
        <span className="tag">{tour.category}</span>
        {tour.featured && (
          <span className="tag tag-gold"><i className="bi bi-star-fill" aria-hidden="true" /> Popular</span>
        )}
      </Link>
      <div className="tour-body">
        <p className="tour-meta"><i className="bi bi-clock" aria-hidden="true" /> {tour.duration}</p>
        <h3><Link to={href}>{tour.title}</Link></h3>
        <p className="tour-summary">{tour.summary}</p>
        <div className="tour-foot">
          <div className="price">
            <small>From</small>
            <strong>{money(tour.price, currency)}</strong>
            <small>per person</small>
          </div>
          <Link className="btn-circle" to={href} aria-label={`View ${tour.title}`}>
            <i className="bi bi-arrow-right" aria-hidden="true" />
          </Link>
        </div>
      </div>
    </article>
  )
}
