/**
 * Site-wide business details. Will later come from the Laravel `settings` endpoint
 * (see services/api.js → getSite). Keep keys stable; components read these names.
 */
import { unsplash } from '@/utils/format'

export const site = {
  name: 'Lean Global Travel & Tourism',
  shortName: 'Lean Global',
  phone: '+94 77 123 4567',
  whatsapp: '94771234567', // digits only, with country code
  email: 'hello@leanglobal.lk',
  address: 'Colombo, Sri Lanka',
  hours: 'Mon – Sun · 8:00 AM – 9:00 PM',
  currency: 'USD',
  founded: 2021,
  stats: { travellers: 1200, packages: 18, destinations: 30, rating: 4.9, reviews: 80 },
  social: {
    facebook: '#',
    instagram: '#',
    tiktok: '#',
    tripadvisor: '#',
  },
  // Toggle optional home-page sections (also hides their nav/footer links)
  sections: {
    faq: false,
  },
  hero: {
    eyebrow: 'Private Tours · Chauffeur Guides · Sri Lanka',
    title: 'Journeys crafted in',
    highlight: 'gold & green',
    subtitle:
      'From misty tea country to sunlit southern shores, travel Sri Lanka with a private guide who knows every hidden road.',
    // Put your own clip at public/video/hero.mp4 and list '/video/hero.mp4' first.
    videos: ['https://videos.pexels.com/video-files/2169880/2169880-hd_1920_1080_30fps.mp4'],
    poster: unsplash('photo-1566296314736-6eaac1ca0cb9', 1920),
  },
}
