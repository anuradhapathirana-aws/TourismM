import { useEffect } from 'react'
import { useLocation } from 'react-router-dom'

/**
 * Scrolls to `#hash` targets after navigation (content may render a moment later,
 * so it retries for ~1s), otherwise scrolls to top on route change.
 */
export default function ScrollManager() {
  const { pathname, hash, key } = useLocation()

  useEffect(() => {
    if (!hash) {
      window.scrollTo({ top: 0, behavior: 'instant' })
      return
    }
    let tries = 0
    let timer
    const go = () => {
      const el = document.getElementById(decodeURIComponent(hash.slice(1)))
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
      else if (tries++ < 20) timer = setTimeout(go, 50)
    }
    go()
    return () => clearTimeout(timer)
  }, [pathname, hash, key])

  return null
}
