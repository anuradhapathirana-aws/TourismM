import SectionHeading from '@/components/ui/SectionHeading'

/** Destination mosaic. Clicking a card shows that place on the map below. */
export default function Destinations({ items = [], onSelect }) {
  return (
    <section className="section section-dark" id="destinations">
      <div className="container">
        <SectionHeading
          center
          eyebrow="Destinations"
          title="Explore our beautiful"
          highlight="island"
          sub="Ancient cities, misty mountains, wild national parks and endless beaches, all within a few hours’ drive."
        />
        <div className="dest-grid">
          {items.map((d, i) => (
            <button
              key={d.id}
              type="button"
              className={`dest-card reveal${i === 0 ? ' dest-wide' : ''}`}
              onClick={() => onSelect?.(d.id)}
              aria-label={`${d.name}, ${d.category}. Show on map`}
            >
              <img src={d.image} alt="" loading="lazy" width="800" height="600" />
              <span className="tag">{d.category}</span>
              <span className="dest-info">
                <span className="dest-name">{d.name}</span>
                <span className="dest-desc">{d.description}</span>
              </span>
              <span className="dest-arrow" aria-hidden="true"><i className="bi bi-geo-alt" /></span>
            </button>
          ))}
        </div>
      </div>
    </section>
  )
}
