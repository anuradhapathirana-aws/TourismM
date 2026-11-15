import { useState } from 'react'
import { useApi } from '@/hooks/useApi'
import { usePageTitle } from '@/hooks/usePageTitle'
import { useSite } from '@/context/SiteContext'
import { getDestinations, getFaqs, getGuides, getTestimonials, getThemes, getTourCategories, getTours } from '@/services/api'
import Hero from '@/sections/home/Hero'
import About from '@/sections/home/About'
import Destinations from '@/sections/home/Destinations'
import WhyUs from '@/sections/home/WhyUs'
import MeetGuides from '@/sections/home/MeetGuides'
import DestinationMap from '@/sections/home/DestinationMap'
import Tours from '@/sections/home/Tours'
import Testimonials from '@/sections/home/Testimonials'
import Faq from '@/sections/home/Faq'
import Contact from '@/sections/home/Contact'

export default function Home() {
  usePageTitle()
  const site = useSite()
  const { data: destinations } = useApi(getDestinations)
  const { data: themes } = useApi(getThemes)
  const { data: tours } = useApi(getTours)
  const { data: tourCategories } = useApi(getTourCategories)
  const { data: guides } = useApi(getGuides)
  const { data: testimonials } = useApi(getTestimonials)
  const { data: faqs } = useApi(getFaqs)

  // { id, n } so clicking the same card twice still re-focuses the map
  const [selected, setSelected] = useState(null)
  const showOnMap = (id) => {
    setSelected((s) => ({ id, n: (s?.n || 0) + 1 }))
    document.getElementById('map')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }

  return (
    <>
      <Hero testimonials={testimonials || []} />
      <About />
      <Destinations items={(destinations || []).filter((d) => d.featured && d.image)} onSelect={showOnMap} />
      <WhyUs />
      <MeetGuides guides={guides || []} />
      <DestinationMap themes={themes || []} destinations={destinations || []} selected={selected} />
      <Tours tours={tours || []} categories={tourCategories || []} />
      <Testimonials items={testimonials || []} />
      {site.sections?.faq !== false && <Faq items={faqs || []} />}
      <Contact tours={tours || []} />
    </>
  )
}
