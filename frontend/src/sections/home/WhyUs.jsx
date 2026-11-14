import { useSite } from '@/context/SiteContext'
import { useCountUp } from '@/hooks/useCountUp'

const FEATURES = [
  { icon: 'bi-gem', title: 'Tailor-made', text: 'Every itinerary designed around your interests, pace and budget.' },
  { icon: 'bi-translate', title: 'Multilingual guides', text: 'English, Hindi, Arabic, French, German, Russian and Malay.' },
  { icon: 'bi-shield-check', title: 'Safe & licensed', text: 'Government-licensed guides and fully insured vehicles.' },
  { icon: 'bi-headset', title: '24/7 support', text: 'Someone is always a WhatsApp message away, day or night.' },
]

function Stat({ value, label }) {
  const [ref, n] = useCountUp(value)
  return (
    <div>
      <strong ref={ref} aria-hidden="true">{n.toLocaleString()}+</strong>
      <span className="sr-only">{value.toLocaleString()}+ </span>
      <span>{label}</span>
    </div>
  )
}

export default function WhyUs() {
  const { stats, founded } = useSite()
  const years = Math.max(1, new Date().getFullYear() - founded)

  return (
    <section className="section section-light why">
      <div className="container why-grid">
        <div className="reveal">
          <p className="eyebrow dark"><span className="eyebrow-line" aria-hidden="true" />Why travel with us</p>
          <h2 className="section-title">Luxury is in the <em>details</em></h2>
          <p className="lead-muted">
            We are a small, passionate team. That means real people answering your messages,
            guides who become friends and journeys that never feel like a package.
          </p>
          <div className="stats">
            <Stat value={stats.travellers} label="Happy travellers" />
            <Stat value={stats.packages} label="Tour packages" />
            <Stat value={stats.destinations} label="Destinations" />
            <Stat value={years} label="Years of service" />
          </div>
        </div>
        <div className="feature-cards">
          {FEATURES.map((f) => (
            <div className="feature reveal" key={f.title}>
              <i className={`bi ${f.icon}`} aria-hidden="true" />
              <h3>{f.title}</h3>
              <p>{f.text}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
