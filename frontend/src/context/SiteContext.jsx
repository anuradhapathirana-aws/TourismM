import { createContext, useContext } from 'react'
import { site as defaults } from '@/config/site'
import { getSite } from '@/services/api'
import { useApi } from '@/hooks/useApi'

const SiteContext = createContext(defaults)

/** Provides business details (phone, email, stats…) to every component. */
export function SiteProvider({ children }) {
  const { data } = useApi(getSite)
  return <SiteContext.Provider value={data ?? defaults}>{children}</SiteContext.Provider>
}

// eslint-disable-next-line react-refresh/only-export-components
export const useSite = () => useContext(SiteContext)
