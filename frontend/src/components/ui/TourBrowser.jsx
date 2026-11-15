import { useMemo, useState } from 'react'
import TourCard from '@/components/ui/TourCard'
import { tourCategoryLabel, tourCategorySlug } from '@/utils/format'

/**
 * Category filter tabs + tour grid (home "Discover Sri Lanka" section and the /tours page).
 * Tabs follow the admin's category order (`categories` from GET /tour-categories) and only list
 * categories that have a tour in `tours`. Pass `value` + `onChange` to control the active tab
 * (e.g. from the URL); otherwise it keeps its own state.
 */
export default function TourBrowser({ tours = [], categories = [], value, onChange }) {
  const [own, setOwn] = useState('')
  const active = value ?? own
  const select = onChange ?? setOwn

  const tabs = useMemo(() => {
    const present = new Map()
    tours.forEach((t) => {
      const slug = tourCategorySlug(t)
      if (slug && !present.has(slug)) present.set(slug, tourCategoryLabel(t))
    })
    const ordered = categories.filter((c) => present.has(c.slug)).map((c) => ({ slug: c.slug, label: c.label || c.name }))
    const rest = [...present].filter(([slug]) => !ordered.some((c) => c.slug === slug)).map(([slug, label]) => ({ slug, label }))
    return [...ordered, ...rest]
  }, [tours, categories])

  const current = tabs.some((t) => t.slug === active) ? active : ''
  const shown = tours.filter((t) => !current || tourCategorySlug(t) === current)

  return (
    <>
      {tabs.length > 1 && (
        <div className="filter-tabs reveal" role="group" aria-label="Filter tours by category">
          {[{ slug: '', label: 'All Packages' }, ...tabs].map((c) => (
            <button key={c.slug || 'all'} type="button" className={current === c.slug ? 'active' : ''} aria-pressed={current === c.slug} onClick={() => select(c.slug)}>
              {c.label}
            </button>
          ))}
        </div>
      )}
      <p className="sr-only" aria-live="polite">{shown.length} tours shown</p>
      <div className="tour-grid">
        {shown.map((t) => <TourCard key={t.slug} tour={t} className="fade-swap" />)}
      </div>
    </>
  )
}
