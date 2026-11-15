import { Outlet, useLocation } from 'react-router-dom'
import Header from './Header'
import Footer from './Footer'
import FloatingButtons from './FloatingButtons'
import ScrollManager from './ScrollManager'
import { useReveal } from '@/hooks/useReveal'
import { PlannerProvider } from '@/context/PlannerContext'

export default function Layout() {
  const { pathname } = useLocation()
  useReveal()

  return (
    <PlannerProvider>
      <a className="skip-link" href="#main">Skip to main content</a>
      <ScrollManager />
      <Header transparent={pathname === '/'} />
      <main id="main" tabIndex={-1}>
        <Outlet />
      </main>
      <Footer />
      <FloatingButtons />
    </PlannerProvider>
  )
}
