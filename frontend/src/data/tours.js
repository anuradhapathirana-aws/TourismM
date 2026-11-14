import { unsplash } from '@/utils/format'

/** Mock data. Shape must match the API contract in CLAUDE.md. */
export const tours = [
  {
    id: 1, slug: 'cultural-triangle-heritage', title: 'Cultural Triangle Heritage', category: 'Cultural',
    duration: '5 Days / 4 Nights', price: 690, featured: true, image: unsplash('photo-1588598198321-9735fd52455b'),
    summary: 'Ancient kingdoms, cave temples and the Lion Rock: Sri Lanka’s cultural heart.',
    highlights: ['Sigiriya Rock Fortress at sunrise', 'Dambulla Golden Cave Temple', 'Polonnaruwa ancient city by bicycle', 'Village experience & traditional lunch', 'Temple of the Tooth, Kandy'],
    itinerary: [
      { title: 'Arrival & Negombo', details: 'Airport pick-up, relaxing evening by the lagoon.' },
      { title: 'Sigiriya', details: 'Transfer to the Cultural Triangle, village tour and sunset at Pidurangala.' },
      { title: 'Sigiriya & Dambulla', details: 'Climb Lion Rock, then visit the golden cave temples.' },
      { title: 'Polonnaruwa', details: 'Cycle through the medieval capital, evening jeep safari at Minneriya.' },
      { title: 'Kandy', details: 'Spice garden, Temple of the Tooth and a cultural dance show.' },
    ],
    inclusions: ['Private air-conditioned vehicle', 'English-speaking chauffeur-guide', '4 nights accommodation with breakfast', 'All fuel, parking & highway tolls', 'Airport transfers'],
  },
  {
    id: 2, slug: 'hill-country-tea-trails', title: 'Hill Country & Tea Trails', category: 'Hill Country',
    duration: '4 Days / 3 Nights', price: 540, featured: true, image: unsplash('photo-1578519050142-afb511e518de'),
    summary: 'Ride the famous blue train through tea estates to Ella’s waterfalls and viewpoints.',
    highlights: ['Scenic train ride Kandy → Ella', 'Tea factory tour & tasting', 'Nine Arch Bridge & Little Adam’s Peak', 'Ravana Falls', 'Gregory Lake, Nuwara Eliya'],
    itinerary: [
      { title: 'Kandy → Nuwara Eliya', details: 'Botanical gardens, tea factory and town tour.' },
      { title: 'Nuwara Eliya → Ella', details: 'The iconic scenic train ride through the highlands.' },
      { title: 'Ella', details: 'Sunrise hike, Nine Arch Bridge and Ravana Falls.' },
      { title: 'Departure', details: 'Drive to the south coast or Colombo.' },
    ],
    inclusions: ['Private vehicle & chauffeur-guide', 'Reserved train tickets', '3 nights accommodation with breakfast', 'Tea factory entrance'],
  },
  {
    id: 3, slug: 'southern-coast-escape', title: 'Southern Coast Escape', category: 'Beach',
    duration: '5 Days / 4 Nights', price: 620, featured: true, image: unsplash('photo-1580910527739-556eb89f9d65'),
    summary: 'Galle Fort, turtle hatcheries, whale watching and lazy days on golden beaches.',
    highlights: ['Galle Dutch Fort walking tour', 'Whale & dolphin watching in Mirissa', 'Coconut Tree Hill sunset', 'Madu River boat safari', 'Turtle hatchery visit'],
    itinerary: [
      { title: 'Colombo → Bentota', details: 'Madu River safari and turtle hatchery.' },
      { title: 'Galle', details: 'Explore the UNESCO fort, ramparts and boutiques.' },
      { title: 'Mirissa', details: 'Morning whale watching, afternoon on the beach.' },
      { title: 'Free day', details: 'Snorkelling, surfing lessons or a spa day.' },
      { title: 'Departure', details: 'Transfer to the airport via the expressway.' },
    ],
    inclusions: ['Private vehicle & chauffeur-guide', '4 nights beach hotel with breakfast', 'Whale watching boat ticket', 'All transfers'],
  },
  {
    id: 4, slug: 'wild-sri-lanka-safari', title: 'Wild Sri Lanka Safari', category: 'Wildlife',
    duration: '4 Days / 3 Nights', price: 760, featured: true, image: unsplash('photo-1549366021-9f761d450615'),
    summary: 'Leopards in Yala, elephants in Udawalawe and birdlife in wild wetlands.',
    highlights: ['Two jeep safaris in Yala National Park', 'Udawalawe elephant safari', 'Elephant Transit Home feeding', 'Bundala bird sanctuary', 'Luxury tented camp night'],
    itinerary: [
      { title: 'Arrival → Udawalawe', details: 'Afternoon elephant safari.' },
      { title: 'Udawalawe → Yala', details: 'Elephant Transit Home, evening at the camp.' },
      { title: 'Yala', details: 'Full-day leopard safari with packed lunch.' },
      { title: 'Departure', details: 'Morning birding in Bundala, then transfer.' },
    ],
    inclusions: ['Private safari jeeps & trackers', 'Park entrance fees', '3 nights (1 night glamping)', 'All meals on safari days'],
  },
  {
    id: 5, slug: 'colombo-city-explorer', title: 'Colombo City Explorer', category: 'City',
    duration: '1 Day', price: 95, featured: false, image: unsplash('photo-1546708973-b339540b5162'),
    summary: 'Colonial landmarks, bustling markets, temples and the best street food in the capital.',
    highlights: ['Gangaramaya Temple', 'Pettah market tuk-tuk ride', 'Galle Face Green sunset', 'Independence Square', 'Street-food tasting'],
    itinerary: [{ title: 'Colombo', details: 'Full-day guided city tour with pick-up from your hotel.' }],
    inclusions: ['Private vehicle & guide', 'Tuk-tuk ride', 'Street-food tasting', 'Bottled water'],
  },
  {
    id: 6, slug: 'adventure-sri-lanka', title: 'Adventure Sri Lanka', category: 'Adventure',
    duration: '6 Days / 5 Nights', price: 880, featured: false, image: unsplash('photo-1581791534721-e599df4417f7'),
    summary: 'White-water rafting, jungle hikes, waterfalls and canyoning for the bold traveller.',
    highlights: ['White-water rafting in Kitulgala', 'Canyoning & waterfall jumps', 'Adam’s Peak night climb', 'Knuckles mountain trek', 'Zip-lining in Ella'],
    itinerary: [
      { title: 'Colombo → Kitulgala', details: 'Rafting the Kelani River.' },
      { title: 'Kitulgala', details: 'Canyoning and a jungle trek.' },
      { title: 'Adam’s Peak', details: 'Night climb for sunrise at the summit.' },
      { title: 'Knuckles Range', details: 'Guided trek through cloud forest.' },
      { title: 'Ella', details: 'Flying Ravana zip-line.' },
      { title: 'Departure', details: 'Transfer to Colombo.' },
    ],
    inclusions: ['Private vehicle & guide', 'All adventure activities with instructors', '5 nights accommodation', 'Safety gear'],
  },
]
