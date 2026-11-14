import { lazy, Suspense } from 'react'
import { Route, Routes } from 'react-router-dom'
import Layout from '@/components/layout/Layout'
import PageLoader from '@/components/ui/PageLoader'

// Route-level code splitting
const Home = lazy(() => import('@/pages/Home'))
const TourDetail = lazy(() => import('@/pages/TourDetail'))
const NotFound = lazy(() => import('@/pages/NotFound'))

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route index element={<Suspense fallback={<PageLoader />}><Home /></Suspense>} />
        <Route path="tours/:slug" element={<Suspense fallback={<PageLoader />}><TourDetail /></Suspense>} />
        <Route path="*" element={<Suspense fallback={<PageLoader />}><NotFound /></Suspense>} />
      </Route>
    </Routes>
  )
}
