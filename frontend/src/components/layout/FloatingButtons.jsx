import { useEffect, useState } from 'react'
import { useSite } from '@/context/SiteContext'
import { waLink } from '@/utils/format'

export default function FloatingButtons() {
  const site = useSite()
  const [showTop, setShowTop] = useState(false)

  useEffect(() => {
    const onScroll = () => setShowTop(window.scrollY > 700)
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  return (
    <>
      <a
        className="wa-float"
        href={waLink(site.whatsapp, 'Hello! I would like to plan a trip to Sri Lanka.')}
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Chat with us on WhatsApp"
      >
        <i className="bi bi-whatsapp" aria-hidden="true" />
      </a>
      <button
        type="button"
        className={`to-top${showTop ? ' show' : ''}`}
        aria-label="Back to top"
        tabIndex={showTop ? 0 : -1}
        onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
      >
        <i className="bi bi-arrow-up" aria-hidden="true" />
      </button>
    </>
  )
}
