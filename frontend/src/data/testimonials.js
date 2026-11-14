import { unsplash } from '@/utils/format'

/** Mock data. Shape must match the API contract in CLAUDE.md. */
export const testimonials = [
  { id: 1, name: 'Matt Joseph', country: 'United Kingdom', rating: 5, photo: unsplash('photo-1472099645785-5658abf4ff4e', 160),
    message: 'Booked a last-minute tour to Sigiriya and it was flawless. Our guide was punctual, friendly and knew every shortcut. Everything I wanted, and more.' },
  { id: 2, name: 'Waqas Asif', country: 'UAE', rating: 5, photo: unsplash('photo-1500648767791-00dcc994a43e', 160),
    message: 'The experience during my recent trip was amazing. Ruwan speaks Arabic, which made my parents feel completely at home. Highly recommended!' },
  { id: 3, name: 'Sophie Laurent', country: 'France', rating: 5, photo: unsplash('photo-1544005313-94ddf0286df2', 160),
    message: 'Ten days, zero stress. The hotels were beautiful, the itinerary perfectly paced and the leopard safari was the highlight of our year.' },
  { id: 4, name: 'Aisha Rahman', country: 'Malaysia', rating: 5, photo: unsplash('photo-1494790108377-be9c29b29330', 160),
    message: 'Professional from the first message to the airport goodbye. The tea country train ride and Ella were magical.' },
]
