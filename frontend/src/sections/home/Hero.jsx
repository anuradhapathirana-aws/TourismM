import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { useSite } from '@/context/SiteContext'

/** Full-screen background video hero with pause control (respects reduced motion). */
export default function Hero({ testimonials = [] }) {
  const site = useSite()
  const { hero, stats } = site
  const videoRef = useRef(null)
  const [playing, setPlaying] = useState(true)
  const [videoFailed, setVideoFailed] = useState(false)

  useEffect(() => {
    const v = videoRef.current
    if (!v) return
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      v.pause()
      setPlaying(false)
      return
    }
    v.play().catch(() => setPlaying(false))
  }, [])

  const toggle = () => {
    const v = videoRef.current
    if (!v) return
    if (v.paused) {
      v.play()
      setPlaying(true)
    } else {
      v.pause()
      setPlaying(false)
    }
  }

  return (
    <section className="hero" aria-label="Welcome">
      <div className="hero-media">
        {videoFailed || !hero.videos?.length ? (
          <img src={hero.poster} alt="" />
        ) : (
          <video
            ref={videoRef}
            autoPlay
            muted
            loop
            playsInline
            preload="metadata"
            poster={hero.poster}
            aria-hidden="true"
            onError={() => setVideoFailed(true)}
          >
            {hero.videos.map((src) => (
              <source key={src} src={src} type={src.endsWith('.webm') ? 'video/webm' : 'video/mp4'} onError={(e) => {
                // when the last <source> fails, fall back to the poster image
                if (!e.currentTarget.nextElementSibling) setVideoFailed(true)
              }} />
            ))}
          </video>
        )}
      </div>
      <div className="hero-overlay" aria-hidden="true" />

      <div className="container hero-content">
        <p className="eyebrow hero-in" style={{ '--d': '0.1s' }}>
          <span className="eyebrow-line" aria-hidden="true" />{hero.eyebrow}
        </p>
        <h1 className="hero-title hero-in" style={{ '--d': '0.25s' }}>
          {hero.title}<br /><em>{hero.highlight}</em>
        </h1>
        <p className="hero-sub hero-in" style={{ '--d': '0.4s' }}>{hero.subtitle}</p>
        <div className="hero-actions hero-in" style={{ '--d': '0.55s' }}>
          <Link className="btn btn-gold" to="/#tours">Explore Tours <i className="bi bi-arrow-right" aria-hidden="true" /></Link>
          <Link className="btn btn-ghost" to="/#guides"><i className="bi bi-person-badge" aria-hidden="true" /> Meet Your Guide</Link>
        </div>
      </div>

      <div className="hero-bottom container">
        <div className="hero-proof">
          <div className="avatar-stack" aria-hidden="true">
            {testimonials.slice(0, 4).map((t) => <img key={t.id} src={t.photo} alt="" width="42" height="42" />)}
          </div>
          <div>
            <strong>{stats.travellers.toLocaleString()}+</strong>
            <span>Happy travellers</span>
          </div>
        </div>
        <ul className="hero-badges">
          <li><i className="bi bi-car-front" aria-hidden="true" /> Private tours &amp; chauffeurs</li>
          <li><i className="bi bi-translate" aria-hidden="true" /> Multilingual guides</li>
          <li><i className="bi bi-star-fill" aria-hidden="true" /> {stats.rating} average rating</li>
        </ul>
        {!videoFailed && (
          <button type="button" className="video-toggle" onClick={toggle} aria-label={playing ? 'Pause background video' : 'Play background video'}>
            <i className={`bi ${playing ? 'bi-pause-fill' : 'bi-play-fill'}`} aria-hidden="true" />
          </button>
        )}
      </div>
      <Link to="/#about" className="scroll-cue" aria-label="Scroll to the next section"><span /></Link>
    </section>
  )
}
