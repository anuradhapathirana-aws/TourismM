import { createContext, lazy, Suspense, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'

// Loaded on first open only, so the popup adds nothing to the first page load.
const JourneyPlanner = lazy(() => import('@/components/journey/JourneyPlanner'))

const PlannerContext = createContext({ openPlanner: () => {} })

/** Deep link that opens the planner on any page, e.g. https://site/?plan=journey (handy for social posts). */
const PARAM = 'plan'
const VALUE = 'journey'

/**
 * Provides openPlanner() for the "Build your own journey" popup.
 * The popup stays mounted after the first open, so a guest's answers survive closing it.
 */
export function PlannerProvider({ children }) {
  const [mounted, setMounted] = useState(false)
  const [open, setOpen] = useState(false)
  const location = useLocation()
  const navigate = useNavigate()

  const openPlanner = useCallback(() => {
    setMounted(true)
    setOpen(true)
  }, [])

  useEffect(() => {
    if (new URLSearchParams(location.search).get(PARAM) === VALUE) openPlanner()
  }, [location.search, openPlanner])

  const close = useCallback(() => {
    setOpen(false)
    const params = new URLSearchParams(location.search)
    if (params.get(PARAM) === VALUE) {
      params.delete(PARAM)
      const qs = params.toString()
      navigate({ pathname: location.pathname, search: qs ? `?${qs}` : '', hash: location.hash }, { replace: true, preventScrollReset: true })
    }
  }, [location, navigate])

  const value = useMemo(() => ({ openPlanner }), [openPlanner])

  return (
    <PlannerContext.Provider value={value}>
      {children}
      {mounted && (
        <Suspense fallback={null}>
          <JourneyPlanner open={open} onClose={close} />
        </Suspense>
      )}
    </PlannerContext.Provider>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const usePlanner = () => useContext(PlannerContext)
