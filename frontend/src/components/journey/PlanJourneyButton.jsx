import { usePlanner } from '@/context/PlannerContext'

/**
 * Opens the "Build your own journey" popup. Renders a <button> (it performs an action, not navigation).
 * Default look is an inline text link; pass className="btn btn-gold" etc. for a button look.
 * `onClick` runs first (e.g. the header closes its mobile menu).
 */
export default function PlanJourneyButton({ className = 'link-btn', onClick, children = 'Let’s build your own journey together →' }) {
  const { openPlanner } = usePlanner()
  return (
    <button
      type="button"
      className={className}
      onClick={(e) => {
        onClick?.(e)
        openPlanner()
      }}
      aria-haspopup="dialog"
    >
      {children}
    </button>
  )
}
