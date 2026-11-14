import { unsplash } from '@/utils/format'

/**
 * Travel themes shown as cards around the map ("Discover your perfect destination").
 * Destinations link to themes via their `themes` array (slugs).
 * `side` decides which column the card sits in on desktop.
 * Mock data. Shape must match the API contract in CLAUDE.md.
 */
export const themes = [
  { id: 1, slug: 'beaches', title: 'Popular Beaches', side: 'left', image: unsplash('photo-1552055568-f8c4fb8c6320', 300) },
  { id: 2, slug: 'wildlife', title: 'Wildlife & Nature', side: 'left', image: unsplash('photo-1549366021-9f761d450615', 300) },
  { id: 3, slug: 'adventure', title: 'Adventure', side: 'left', image: unsplash('photo-1581791534721-e599df4417f7', 300) },
  { id: 4, slug: 'culture', title: 'History & Culture', side: 'right', image: unsplash('photo-1588598198321-9735fd52455b', 300) },
  { id: 5, slug: 'lesser-travelled', title: 'Lesser Travelled', side: 'right', image: unsplash('photo-1469474968028-56623f02e42e', 300) },
  { id: 6, slug: 'gastronomy', title: 'Gastronomy', side: 'right', image: unsplash('photo-1585937421612-70a008356fbe', 300) },
]
