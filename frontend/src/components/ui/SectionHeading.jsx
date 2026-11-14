/**
 * Eyebrow + serif title with a gold italic highlight.
 * <SectionHeading eyebrow="Tours" title="Discover Sri Lanka" highlight="your way" center />
 */
export default function SectionHeading({ eyebrow, title, highlight, sub, center = false, dark = false, as = 'h2' }) {
  const Tag = as
  return (
    <div className={`section-head reveal${center ? ' center' : ' left'}`}>
      {eyebrow && (
        <p className={`eyebrow${dark ? ' dark' : ''}`}>
          <span className="eyebrow-line" aria-hidden="true" />
          {eyebrow}
          {center && <span className="eyebrow-line" aria-hidden="true" />}
        </p>
      )}
      <Tag className="section-title">
        {title} {highlight && <em>{highlight}</em>}
      </Tag>
      {sub && <p className="section-sub">{sub}</p>}
    </div>
  )
}
