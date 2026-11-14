import { Link } from 'react-router-dom'
import { useSite } from '@/context/SiteContext'
import { useApi } from '@/hooks/useApi'
import { getDestinations, getTours } from '@/services/api'
import { mailLink, telLink, waLink } from '@/utils/format'
import logo from '@/assets/images/LG-crop.jpg'

const SOCIAL_ICONS = { facebook: 'facebook', instagram: 'instagram', tiktok: 'tiktok', youtube: 'youtube', tripadvisor: 'globe2' }

export default function Footer() {
  const site = useSite()
  const { data: tours } = useApi(getTours)
  const { data: dests } = useApi(getDestinations)

  return (
    <footer className="site-footer">
      <div className="container footer-grid">
        <div className="footer-brand">
          <img src={logo} alt={site.name} width="720" height="660" loading="lazy" />
          <p>Private tours and tailor-made journeys across Sri Lanka, designed with care and guided with heart.</p>
          <div className="socials">
            {Object.entries(site.social || {})
              .filter(([, url]) => url)
              .map(([key, url]) => (
                <a key={key} href={url} target="_blank" rel="noopener noreferrer" aria-label={key}>
                  <i className={`bi bi-${SOCIAL_ICONS[key] || 'link-45deg'}`} aria-hidden="true" />
                </a>
              ))}
          </div>
        </div>

        <div>
          <h2 className="footer-title">Explore</h2>
          <ul>
            <li><Link to="/#about">About Us</Link></li>
            <li><Link to="/#destinations">Destinations</Link></li>
            <li><Link to="/#tours">Tour Packages</Link></li>
            <li><Link to="/#guides">Meet Your Guide</Link></li>
            {site.sections?.faq !== false && <li><Link to="/#faq">FAQ</Link></li>}
          </ul>
        </div>

        <div>
          <h2 className="footer-title">Popular Tours</h2>
          <ul>
            {(tours || []).slice(0, 5).map((t) => (
              <li key={t.slug}><Link to={`/tours/${t.slug}`}>{t.title}</Link></li>
            ))}
          </ul>
        </div>

        <div>
          <h2 className="footer-title">Get in Touch</h2>
          <ul className="contact-list">
            <li><i className="bi bi-telephone" aria-hidden="true" /><a href={telLink(site.phone)}>{site.phone}</a></li>
            <li><i className="bi bi-whatsapp" aria-hidden="true" /><a href={waLink(site.whatsapp, 'Hello! I would like to plan a trip.')} target="_blank" rel="noopener noreferrer">WhatsApp us</a></li>
            <li><i className="bi bi-envelope" aria-hidden="true" /><a href={mailLink(site.email)}>{site.email}</a></li>
            <li><i className="bi bi-geo-alt" aria-hidden="true" /><span>{site.address}</span></li>
            <li><i className="bi bi-clock" aria-hidden="true" /><span>{site.hours}</span></li>
          </ul>
        </div>
      </div>

      <div className="container footer-bottom">
        <span>© {new Date().getFullYear()} {site.name}. All rights reserved.</span>
        <span>{(dests || []).slice(0, 6).map((d) => d.name).join(' · ')}</span>
      </div>
    </footer>
  )
}
