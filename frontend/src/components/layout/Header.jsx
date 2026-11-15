import { useEffect, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { useSite } from '@/context/SiteContext'
import { telLink } from '@/utils/format'
import logo from '@/assets/images/LG-crop.jpg'

const NAV = [
  { to: '/#about', label: 'About' },
  { to: '/#destinations', label: 'Destinations' },
  { to: '/tours', label: 'Tours' },
  { to: '/#guides', label: 'Our Guides' },
  { to: '/#reviews', label: 'Reviews' },
  { to: '/#faq', label: 'FAQ', section: 'faq' },
]

export default function Header({ transparent }) {
  const site = useSite()
  const location = useLocation()
  const [scrolled, setScrolled] = useState(false)
  const [open, setOpen] = useState(false)

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 40)
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  // Close the mobile menu on navigation; lock page scroll while it is open
  useEffect(() => setOpen(false), [location.key])
  useEffect(() => {
    document.body.classList.toggle('no-scroll', open)
    const onKey = (e) => e.key === 'Escape' && setOpen(false)
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [open])

  const solid = !transparent || scrolled
  return (
    <header className={`site-header${solid ? ' solid' : ''}${open ? ' open' : ''}`} id="top">
      <div className="container nav-wrap">
        <Link className="brand" to="/" aria-label={`${site.name}, home`}>
          <img src={logo} alt={site.name} width="720" height="660" />
        </Link>

        <nav className="main-nav" id="mainNav" aria-label="Main">
          {NAV.filter((n) => !n.section || site.sections?.[n.section] !== false).map((n) => (
            <Link key={n.to} to={n.to}>{n.label}</Link>
          ))}
          <Link className="btn btn-gold nav-cta-mobile" to="/#contact">Plan My Trip</Link>
        </nav>

        <div className="nav-actions">
          <a className="nav-phone" href={telLink(site.phone)}>
            <i className="bi bi-telephone" aria-hidden="true" />
            <span>{site.phone}</span>
          </a>
          <Link className="btn btn-gold btn-sm" to="/#contact">Plan My Trip</Link>
          <button
            type="button"
            className="nav-toggle"
            aria-label={open ? 'Close menu' : 'Open menu'}
            aria-expanded={open}
            aria-controls="mainNav"
            onClick={() => setOpen((o) => !o)}
          >
            <span /><span /><span />
          </button>
        </div>
      </div>
    </header>
  )
}
