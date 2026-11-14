import { unsplash } from '@/utils/format'

/** Mock data. Shape must match the API contract in CLAUDE.md. */
export const guides = [
  {
    id: 1, name: 'Ruwan Perera', role: 'Founder & Lead Chauffeur-Guide', years: 15, toursLed: 640, rating: 5.0,
    whatsapp: '94771234567', photo: unsplash('photo-1507003211169-0a1dd7228f2d', 800),
    languages: ['English', 'Hindi', 'Arabic'], specialties: ['Culture', 'History', 'Hill Country'],
    bio: 'Born in Kandy and raised among the tea hills, Ruwan has spent fifteen years showing travellers the real Sri Lanka, from secret waterfalls to family-run kitchens. Calm, knowledgeable and always smiling.',
  },
  {
    id: 2, name: 'Nadeesha Silva', role: 'Cultural & Heritage Guide', years: 8, toursLed: 310, rating: 4.9,
    whatsapp: '94771234567', photo: unsplash('photo-1494790108377-be9c29b29330', 800),
    languages: ['English', 'French', 'German'], specialties: ['Heritage', 'Temples', 'Art'],
    bio: 'A history graduate and natural storyteller, Nadeesha brings ancient kingdoms to life. She specialises in temples, art and the island’s 2,500-year heritage.',
  },
  {
    id: 3, name: 'Kasun Fernando', role: 'Wildlife & Safari Specialist', years: 10, toursLed: 420, rating: 4.9,
    whatsapp: '94771234567', photo: unsplash('photo-1500648767791-00dcc994a43e', 800),
    languages: ['English', 'Malay'], specialties: ['Wildlife', 'Birding', 'Photography'],
    bio: 'A trained naturalist with an eagle eye for leopards, Kasun has led hundreds of safaris in Yala, Wilpattu and Udawalawe.',
  },
  {
    id: 4, name: 'Ishara Jayasuriya', role: 'Family & Coastal Tours Guide', years: 6, toursLed: 220, rating: 4.8,
    whatsapp: '94771234567', photo: unsplash('photo-1438761681033-6461ffad8d80', 800),
    languages: ['English', 'Russian'], specialties: ['Families', 'Beaches', 'Food'],
    bio: 'Warm, patient and full of fun facts, Ishara is a favourite with families exploring the southern beaches and coastal towns.',
  },
]
