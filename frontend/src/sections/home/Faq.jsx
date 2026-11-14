import { useSite } from '@/context/SiteContext'
import { telLink, unsplash } from '@/utils/format'

export default function Faq({ items = [] }) {
  const site = useSite()
  if (!items.length) return null

  return (
    <section className="section section-dark" id="faq">
      <div className="container faq-grid">
        <div className="faq-media reveal">
          <img src={unsplash('photo-1488646953014-85cb44e25828', 900)} alt="Map, notebook and camera laid out for trip planning" loading="lazy" width="900" height="1125" />
          <a className="faq-call" href={telLink(site.phone)}>
            <i className="bi bi-telephone" aria-hidden="true" />
            <span><small>Need help?</small><strong>{site.phone}</strong></span>
          </a>
        </div>
        <div className="reveal">
          <p className="eyebrow"><span className="eyebrow-line" aria-hidden="true" />FAQ</p>
          <h2 className="section-title">Travel answers to your <em>questions</em></h2>
          <div className="accordion">
            {items.map((f, i) => (
              <details key={f.id} open={i === 0}>
                <summary>{f.question}<i className="bi bi-plus-lg" aria-hidden="true" /></summary>
                <p>{f.answer}</p>
              </details>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}
