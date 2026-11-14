import { useEffect } from 'react'
import { useSite } from '@/context/SiteContext'

/** Sets document.title as "Page · Site name" (or the site name + tagline on the home page). */
export function usePageTitle(title) {
  const site = useSite()
  useEffect(() => {
    document.title = title ? `${title} · ${site.name}` : `${site.name} · Private Tours in Sri Lanka`
  }, [title, site.name])
}
