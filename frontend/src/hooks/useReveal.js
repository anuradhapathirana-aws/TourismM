import { useEffect } from 'react'

/**
 * Adds `.in` to every `.reveal` element as it scrolls into view.
 * Uses a MutationObserver so content rendered later (after data loads,
 * after route changes) is picked up automatically. Mounted once in Layout.
 */
export function useReveal() {
  useEffect(() => {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    const io = new IntersectionObserver(
      (entries) =>
        entries.forEach((e) => {
          if (e.isIntersecting) {
            e.target.classList.add('in')
            io.unobserve(e.target)
          }
        }),
      { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
    )

    const scan = (root = document) =>
      root.querySelectorAll?.('.reveal:not(.in)').forEach((el) => (reduce ? el.classList.add('in') : io.observe(el)))

    scan()
    const mo = new MutationObserver((muts) =>
      muts.forEach((m) => m.addedNodes.forEach((n) => n.nodeType === 1 && (n.matches?.('.reveal') ? scan(n.parentNode) : scan(n)))),
    )
    mo.observe(document.body, { childList: true, subtree: true })
    return () => {
      io.disconnect()
      mo.disconnect()
    }
  }, [])
}
