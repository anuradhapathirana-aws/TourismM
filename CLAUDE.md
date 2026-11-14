# CLAUDE.md — Lean Global Travel & Tourism

Guidance for Claude Code (and humans) working in this repository.

## Project overview

Marketing website for **Lean Global Travel & Tourism**, a Sri Lanka private-tour / chauffeur-guide company.

| Part | Status | Location |
|------|--------|----------|
| Frontend: React 19 + Vite 6 SPA | **Built** | `frontend/` |
| Backend + admin: Laravel API & admin panel | **Not started.** The owner will build it | `backend/` (future) |

Until the backend exists the frontend runs entirely on **local mock data** (`frontend/src/data/`).
Do not add PHP, a database or an admin UI unless asked. The backend will be Laravel.

## Repository layout

```
TourismM/
├─ CLAUDE.md                 ← this file
├─ .claude/skills/           ← all skills live here (auto-loaded by Claude Code):
│    ui-ux-pro-max/, laravel-specialist/, find-skills/
├─ skills-lock.json          ← records where the skill came from (used by `npx skills`)
├─ .htaccess                 ← blocks web access to .claude/.git (root sits in Laragon's www)
├─ frontend/                 ← React app (all UI work happens here)
└─ backend/                  ← (future) Laravel app
```

### Frontend structure (`frontend/src/`)

```
main.jsx                 entry: Router + SiteProvider + global CSS
App.jsx                  routes (lazy-loaded pages)
config/site.js           business details: phone, WhatsApp, email, stats, hero video/text
context/SiteContext.jsx  provides `site` to all components → useSite()
services/api.js          ★ ONLY data access layer (mock now, Laravel later)
data/*.js                mock content: tours, destinations, themes, guides, testimonials, faqs
utils/geo.js             Sri Lanka SVG outline + project(lat, lng) for the explore map
hooks/                   useApi (async loader), useReveal (scroll animations),
                         useCountUp (stats), usePageTitle
utils/format.js          waLink, telLink, mailLink, money, unsplash, resized, unique
components/layout/       Header, Footer, Layout, ScrollManager, FloatingButtons
components/ui/           SectionHeading, TourCard, InquiryForm, PageLoader
sections/home/           one file per home-page section (Hero, About, Destinations, WhyUs,
                         MeetGuides, DestinationMap, Tours, Testimonials, Faq, Contact)
pages/                   Home, TourDetail (/tours/:slug), NotFound
styles/global.css        all styles, design tokens in :root
assets/images/           LG.jpeg (original logo), LG-crop.jpg (used in header/footer), LG-mark.jpg
```

Path alias: `@` → `frontend/src` (see `vite.config.js`).

## Commands (run inside `frontend/`)

```bash
npm install
npm run dev       # dev server http://localhost:5173
npm run build     # → frontend/dist
npm run preview   # serve the build on :4173
npm run lint      # ESLint; keep it at zero problems
```

## Design system (from the LG logo, do not change without the owner)

- **Palette:** deep black + brushed gold. Tokens live in `:root` of `styles/global.css`:
  `--ink #0b0b0c`, `--ink-2`, `--ink-3`, `--gold #c8a36b`, `--gold-light #ecd3a8`,
  `--gold-deep #8a6a40`, `--gold-grad`, `--cream #f7f2ea` (light sections), `--on-dark`, `--on-dark-muted`.
  Use tokens, never raw hex in new components.
- **Fonts:** Cormorant Garamond (headings, serif, italic gold highlight via `<em>`) + Jost (body). Loaded in `index.html`.
- **Icons:** Bootstrap Icons (`<i className="bi bi-…" aria-hidden="true" />`). No emoji as icons.
- **Rhythm:** sections alternate `section-dark` / `section-light`. Heading pattern = eyebrow + title + gold `<em>` (use `SectionHeading`).
- **Logo:** `LG-crop.jpg` has a black background and is rendered with `mix-blend-mode: screen` so it blends into dark surfaces. Only place it on dark backgrounds.
- **Motion:** add class `reveal` to animate on scroll (handled globally by `useReveal`). Everything must respect `prefers-reduced-motion` (already wired in CSS and hooks).

## Skills (`.claude/skills/`)

Load the matching skill before starting that kind of work. Where a skill disagrees with this file,
**this file wins.**

| Skill | Use for | Project rules that override it |
|-------|---------|-------------------------------|
| `ui-ux-pro-max` | any frontend UI work: new sections, reviews, accessibility | logo palette + fonts in "Design system"; its auto-generated palette/fonts are ignored |
| `laravel-specialist` | all backend/admin work in `backend/` | endpoints, camelCase field names and response shapes in "API contract" |
| `find-skills` | finding/installing new skills | install new skills into `.claude/skills/` (not `.agents/`) |

Useful ui-ux-pro-max queries:
```bash
python .claude/skills/ui-ux-pro-max/scripts/search.py "<query>" --stack react
python .claude/skills/ui-ux-pro-max/scripts/search.py "<query>" --domain ux
```

## Conventions

- Components get data through **`services/api.js`** functions + `useApi(...)`. Never import `data/*.js` directly in components.
- Business details (phone, WhatsApp, email, stats) come from `useSite()`. Never hard-code them in JSX.
- Accessibility baseline (keep it): skip link, visible `:focus-visible`, labelled form fields (`htmlFor`),
  errors under fields with `aria-describedby`, `aria-live` for dynamic status, buttons (not divs) for actions,
  44px touch targets, alt text on meaningful images and `alt=""` on decorative ones.
- Images: give `width`/`height` attributes (prevents layout shift), `loading="lazy"` below the fold,
  use `resized(url, w)` for thumbnails.
- In-page links use router links to `"/#section-id"`. `ScrollManager` handles scrolling.
- Test at 375px, 768px, 1024px and 1440px. No horizontal scroll.

## Content

- **Section toggles:** `config/site.js → sections` (e.g. `faq: false`) hides a home section and its nav/footer links.
  The FAQ section is currently **hidden** at the owner's request (2026-11-08). The code and data are kept.
- **Hero video:** `config/site.js → hero.videos` (array, first playable wins) + `hero.poster` fallback.
  The current clip is a Pexels stock placeholder. Replace with the client's own footage
  (put it at `frontend/public/video/hero.mp4` and list `'/video/hero.mp4'` first, ideally ≤ 8 MB, 1080p, H.264).
- **Images** are Unsplash placeholders (hot-linked). Replace with the client's own photos before launch.
- **Contact details** in `config/site.js` are placeholders (`+94 77 123 4567`, `hello@leanglobal.lk`).
- **Enquiry form:** with no backend it opens WhatsApp with the enquiry pre-filled. When `VITE_API_URL` is set it POSTs to the API instead.
- **Explore map** (`sections/home/DestinationMap.jsx`, section id `#map`): an illustrated SVG of Sri Lanka
  with 6 **theme cards** around it (3 left / 3 right). Hovering, focusing or tapping a card shows that theme's
  destination points + labels. Points are placed from real **latitude/longitude** via `utils/geo.js → project()`
  (same projection as the outline, which is Natural Earth 1:10m, public domain). Valid range: lat 5.85–9.95, lng 79.5–82.0.
  Points outside that range are skipped. Labels auto-stack so they don't overlap.
  Clicking a photo card in the "Explore our beautiful island" mosaic spotlights that single place on this map.
  No map library and no API key are used.

## API contract for the future Laravel backend

Set `VITE_API_URL` in `frontend/.env` (see `.env.example`) and `services/api.js` switches from mock data
to HTTP. Responses may be wrapped in `{ "data": ... }` (Laravel API Resources). Both forms work.
Field names below are what the frontend expects (camelCase). Map snake_case columns in the Laravel Resource.

| Method | Endpoint | Returns |
|--------|----------|---------|
| GET | `/settings` | site object: same shape as `config/site.js` (name, shortName, phone, whatsapp, email, address, hours, currency, founded, stats{travellers,packages,destinations,rating,reviews}, social{…}, hero{eyebrow,title,highlight,subtitle,videos[],poster}) |
| GET | `/destinations` | `[{ id, name, category, lat, lng, themes[], featured, description, image }]`. `themes` = theme slugs (many-to-many). `featured: true` also shows it in the photo mosaic (then `image` + `description` are required) |
| GET | `/themes` | `[{ id, slug, title, image, side }]` (`side`: `"left"`/`"right"` column on desktop; 6 themes fit the layout best) |
| GET | `/tours` | `[{ id, slug, title, category, duration, price, featured, image, summary, highlights[], itinerary[{title,details}], inclusions[] }]` |
| GET | `/tours/{slug}` | single tour (404 → frontend shows "Tour not found") |
| GET | `/guides` | `[{ id, name, role, bio, photo, languages[], specialties[], years, toursLed, rating, whatsapp }]` |
| GET | `/testimonials` | `[{ id, name, country, message, rating, photo }]` |
| GET | `/faqs` | `[{ id, question, answer }]` |
| POST | `/inquiries` | body: `{ name, email, phone, country, date, travellers, tour, tour_slug, message }` → 201. Validation errors → 422 `{ errors: { field: [msg] } }` (Laravel default, shown under the fields) |

Suggested admin (Laravel) scope: CRUD for tours, destinations (name, lat/lng, theme checkboxes, featured,
image, description), themes, guides, testimonials, FAQs; settings page
(contact details, stats, hero video upload); inquiries inbox with status (new/read/replied).
Enable CORS for the frontend origin.

## Deployment

`npm run build` → upload `frontend/dist/`. `public/.htaccess` is copied into `dist/` and rewrites unknown
paths to `index.html` so `/tours/:slug` deep links work on Apache. For Nginx use `try_files $uri /index.html;`.
