import { unsplash } from '@/utils/format'

/**
 * Destination points. Every point with lat/lng is plotted on the illustrated map.
 * - themes:   theme slugs (see data/themes.js) — controls which card highlights it
 * - featured: true → also shown as a photo card in the "Explore our beautiful island" mosaic
 *             (featured points need an image + description)
 * Mock data. Shape must match the API contract in CLAUDE.md.
 */
export const destinations = [
  // ---- Featured (photo mosaic) ----
  { id: 1, name: 'Sigiriya', category: 'Cultural', featured: true, themes: ['culture', 'adventure'], lat: 7.957, lng: 80.7603,
    description: 'The 5th-century Lion Rock fortress rising above the jungle.', image: unsplash('photo-1612862862126-865765df2ded', 1000) },
  { id: 2, name: 'Kandy', category: 'Cultural', featured: true, themes: ['culture', 'gastronomy'], lat: 7.2906, lng: 80.6337,
    description: 'Hill capital and home of the sacred Temple of the Tooth.', image: unsplash('photo-1665849050332-8d5d7e59afb6', 800) },
  { id: 3, name: 'Ella', category: 'Hill Country', featured: true, themes: ['adventure', 'wildlife'], lat: 6.8667, lng: 81.0466,
    description: 'Nine Arch Bridge, waterfalls and endless tea views.', image: unsplash('photo-1566296314736-6eaac1ca0cb9', 800) },
  { id: 4, name: 'Nuwara Eliya', category: 'Hill Country', featured: true, themes: ['wildlife', 'gastronomy'], lat: 6.9497, lng: 80.7891,
    description: '“Little England”: cool climate and emerald tea estates.', image: unsplash('photo-1544015759-237f87d55ef3', 800) },
  { id: 5, name: 'Mirissa', category: 'Beach', featured: true, themes: ['beaches'], lat: 5.9483, lng: 80.4716,
    description: 'Palm-fringed bays, whale watching and golden sunsets.', image: unsplash('photo-1580910527739-556eb89f9d65', 800) },
  { id: 6, name: 'Yala', category: 'Wildlife', featured: true, themes: ['wildlife'], lat: 6.3728, lng: 81.5016,
    description: 'The best place on earth to spot wild leopards.', image: unsplash('photo-1456926631375-92c8ce872def', 800) },
  { id: 7, name: 'Udawalawe', category: 'Wildlife', featured: true, themes: ['wildlife'], lat: 6.474, lng: 80.8987,
    description: 'Herds of wild elephants on open grasslands.', image: unsplash('photo-1564760055775-d63b17a55c44', 800) },
  { id: 8, name: 'Trincomalee', category: 'Beach', featured: true, themes: ['beaches', 'adventure'], lat: 8.5874, lng: 81.2152,
    description: 'Crystal water, coral reefs and diving on the east coast.', image: unsplash('photo-1544551763-46a013bb70d5', 800) },

  // ---- Map points ----
  { id: 9, name: 'Bentota', category: 'Beach', themes: ['beaches'], lat: 6.421, lng: 79.998 },
  { id: 10, name: 'Unawatuna', category: 'Beach', themes: ['beaches'], lat: 6.0098, lng: 80.2497 },
  { id: 11, name: 'Weligama', category: 'Beach', themes: ['beaches', 'adventure'], lat: 5.975, lng: 80.4297 },
  { id: 12, name: 'Arugam Bay', category: 'Beach', themes: ['beaches', 'adventure', 'lesser-travelled'], lat: 6.84, lng: 81.836 },
  { id: 13, name: 'Kalpitiya', category: 'Beach', themes: ['beaches', 'lesser-travelled'], lat: 8.2333, lng: 79.7667 },
  { id: 14, name: 'Pasikudah', category: 'Beach', themes: ['beaches'], lat: 7.929, lng: 81.561 },
  { id: 15, name: 'Minneriya', category: 'Wildlife', themes: ['wildlife'], lat: 8.04, lng: 80.9 },
  { id: 16, name: 'Wilpattu', category: 'Wildlife', themes: ['wildlife', 'lesser-travelled'], lat: 8.456, lng: 80.014 },
  { id: 17, name: 'Sinharaja', category: 'Wildlife', themes: ['wildlife'], lat: 6.4, lng: 80.5 },
  { id: 18, name: 'Horton Plains', category: 'Wildlife', themes: ['wildlife', 'adventure'], lat: 6.802, lng: 80.806 },
  { id: 19, name: 'Kitulgala', category: 'Adventure', themes: ['adventure'], lat: 6.989, lng: 80.418 },
  { id: 20, name: 'Knuckles Range', category: 'Adventure', themes: ['adventure', 'lesser-travelled'], lat: 7.45, lng: 80.78 },
  { id: 21, name: 'Adam’s Peak', category: 'Adventure', themes: ['adventure', 'culture'], lat: 6.8096, lng: 80.4994 },
  { id: 22, name: 'Anuradhapura', category: 'Cultural', themes: ['culture'], lat: 8.3114, lng: 80.4037 },
  { id: 23, name: 'Polonnaruwa', category: 'Cultural', themes: ['culture'], lat: 7.9403, lng: 81.0188 },
  { id: 24, name: 'Dambulla', category: 'Cultural', themes: ['culture'], lat: 7.8567, lng: 80.6492 },
  { id: 25, name: 'Galle Fort', category: 'Cultural', themes: ['culture', 'gastronomy'], lat: 6.0269, lng: 80.217 },
  { id: 26, name: 'Jaffna', category: 'Cultural', themes: ['culture', 'lesser-travelled', 'gastronomy'], lat: 9.6615, lng: 80.0255 },
  { id: 27, name: 'Mannar', category: 'Cultural', themes: ['lesser-travelled'], lat: 8.977, lng: 79.904 },
  { id: 28, name: 'Haputale', category: 'Hill Country', themes: ['lesser-travelled'], lat: 6.768, lng: 80.958 },
  { id: 29, name: 'Batticaloa', category: 'Beach', themes: ['lesser-travelled', 'gastronomy'], lat: 7.731, lng: 81.6747 },
  { id: 30, name: 'Colombo', category: 'City', themes: ['gastronomy'], lat: 6.9271, lng: 79.8612 },
  { id: 31, name: 'Negombo', category: 'Beach', themes: ['gastronomy', 'beaches'], lat: 7.2083, lng: 79.8358 },
]
