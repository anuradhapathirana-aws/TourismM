import { Link } from 'react-router-dom'
import { useSite } from '@/context/SiteContext'
import { telLink, unsplash } from '@/utils/format'

const POINTS = [
  'Tailor-made itineraries designed around you',
  'Licensed, multilingual chauffeur-guides',
  'Hand-picked hotels from boutique to luxury',
  '24/7 on-trip support and airport transfers',
]

export default function About() {
  const site = useSite()
  return (
    <section className="section section-light" id="about">
      <div className="container about-grid">
        <div className="about-media reveal">
          <img className="am-1" src={unsplash('photo-1539635278303-d4002c07eae3', 1000)} alt="A group of friends hiking together" loading="lazy" width="1000" height="560" />
          <img className="am-2" src={unsplash('photo-1544015759-237f87d55ef3', 600)} alt="Rolling green tea plantations" loading="lazy" width="600" height="450" />
          <img className="am-3" src={unsplash('photo-1530789253388-582c481c54b0', 600)} alt="Travellers photographing a sunrise" loading="lazy" width="600" height="450" />
          <div className="since-badge"><strong>{site.founded}</strong><span>Established</span></div>
        </div>

        <div className="about-text reveal">
          <p className="eyebrow dark"><span className="eyebrow-line" aria-hidden="true" />About {site.shortName}</p>
          <h2 className="section-title">Your trusted partner for <em>unforgettable</em> journeys</h2>
          <p className="lead-muted">
            {site.name} creates private, personal and beautifully paced journeys across Sri Lanka.
            Every itinerary is designed around you: your pace, your interests and your budget.
          </p>
          <p className="lead-muted">
            Our multilingual chauffeur-guides, comfortable air-conditioned vehicles and 24/7 support
            mean you can simply relax and enjoy the island.
          </p>
          <ul className="check-list">
            {POINTS.map((p) => <li key={p}><i className="bi bi-check2-circle" aria-hidden="true" />{p}</li>)}
          </ul>
          <div className="about-cta">
            <Link className="btn btn-dark" to="/#contact">Plan Your Journey <i className="bi bi-arrow-right" aria-hidden="true" /></Link>
            <a className="call-chip" href={telLink(site.phone)}>
              <span className="call-icon"><i className="bi bi-telephone" aria-hidden="true" /></span>
              <span><small>Call us 24/7</small><strong>{site.phone}</strong></span>
            </a>
          </div>
        </div>
      </div>
    </section>
  )
}
