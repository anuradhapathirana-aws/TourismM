import { useSite } from '@/context/SiteContext'
import InquiryForm from '@/components/ui/InquiryForm'
import { mailLink, telLink, unsplash, waLink } from '@/utils/format'

export default function Contact({ tours = [] }) {
  const site = useSite()
  return (
    <section className="section cta" id="contact" style={{ '--cta-bg': `url('${unsplash('photo-1580910527739-556eb89f9d65', 1920)}')` }}>
      <div className="container cta-grid">
        <div className="cta-text reveal">
          <p className="eyebrow"><span className="eyebrow-line" aria-hidden="true" />Start planning</p>
          <h2 className="section-title">Ready to explore Sri Lanka? <em>Let’s talk.</em></h2>
          <p className="section-sub left">
            Tell us a little about your trip and we will send you a personalised itinerary and quote,
            free and with no obligation.
          </p>
          <ul className="cta-contacts">
            <li><i className="bi bi-whatsapp" aria-hidden="true" /><a href={waLink(site.whatsapp, 'Hello! I would like to plan a trip.')} target="_blank" rel="noopener noreferrer">Chat on WhatsApp</a></li>
            <li><i className="bi bi-telephone" aria-hidden="true" /><a href={telLink(site.phone)}>{site.phone}</a></li>
            <li><i className="bi bi-envelope" aria-hidden="true" /><a href={mailLink(site.email)}>{site.email}</a></li>
          </ul>
        </div>
        <div className="cta-form reveal">
          <InquiryForm tours={tours} />
        </div>
      </div>
    </section>
  )
}
