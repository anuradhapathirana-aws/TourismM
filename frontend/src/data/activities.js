import { slugify } from '@/utils/format'

/**
 * Activities library (same names as the admin's Activities module).
 * Shown as choices in the "Build your own journey" planner.
 * Mock data. Shape must match GET /activities in CLAUDE.md → "API contract".
 */
const names = [
  'Jeep safari', 'Leopard safari in Yala', 'Elephant safari', 'The Gathering at Minneriya', 'Wilpattu safari',
  'Elephant Transit Home visit', 'Whale watching', 'Dolphin watching', 'Bird watching', 'Turtle hatchery visit',
  'Turtle watching at night', 'Sinharaja rainforest trek', 'Horton Plains & World’s End hike', 'Sigiriya Rock climb',
  'Pidurangala Rock hike', 'Dambulla Cave Temple visit', 'Ancient city cycling tour', 'Temple of the Tooth puja ceremony',
  'Kandyan cultural dance show', 'Village tour', 'Sri Lankan cooking class', 'Spice garden visit', 'Galle Fort walking tour',
  'Stilt fishermen visit', 'Mask carving workshop', 'Batik workshop', 'Gem museum visit', 'Meditation at a monastery',
  'Scenic train ride', 'Tea factory tour & tasting', 'Tea plucking experience', 'Nine Arch Bridge visit',
  'Little Adam’s Peak sunrise hike', 'Adam’s Peak night climb', 'Knuckles mountain trek', 'Waterfall visit',
  'White-water rafting', 'Canyoning', 'Zip-lining', 'Hot-air ballooning', 'Jungle trek', 'Kayaking', 'Snorkelling',
  'Scuba diving', 'Surfing lesson', 'Kitesurfing', 'Madu River boat safari', 'Sunset catamaran cruise', 'Deep-sea fishing',
  'Tuk-tuk city tour', 'Street-food tasting', 'Colombo shopping tour', 'Ayurveda treatment', 'Yoga session',
]

export const activities = names.map((name, i) => ({ id: i + 1, slug: slugify(name), name }))
