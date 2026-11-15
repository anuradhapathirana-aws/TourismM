# CLAUDE.md — Lean Global Travel & Tourism

Guidance for Claude Code (and humans) working in this repository.

## Project overview

Marketing website for **Lean Global Travel & Tourism**, a Sri Lanka private-tour / chauffeur-guide company.

| Part | Status | Location |
|------|--------|----------|
| Frontend: React 19 + Vite 6 SPA | **Built** | `frontend/` |
| Backend + admin: Laravel 12 API + Filament 3 admin | **Phase 1 + Tours + Inquiries built** (custom tour inquiries inbox + guests; tours + tour categories + activities library; locations: cities, destination points, map themes, place types; users/roles) | `backend/` |

**Backend architecture, schema, validation, security and roadmap: [`docs/backend-architecture.md`](docs/backend-architecture.md).** Read it before backend work.
The frontend fetches only the modules listed in `LIVE_MODULES` (`frontend/src/services/api.js`) from the API.
Everything else (guides, testimonials, FAQs, settings, the short tour enquiry form) still uses mock data / WhatsApp until its backend module is built.

### Backend essentials
- **Private admin:** served only at `/{ADMIN_PATH}` (`backend/.env`). Never link to it from the website, never use `/admin`.
  Create users with `php artisan make:admin` (no public registration).
- **Roles:** `super-admin` (everything), `content-editor` (locations), `inquiry-manager` (inquiries + guests). Catalogue: `app/Support/Permissions.php`.
  Each model has a policy that extends `ResourcePolicy`.
- **Validation:** one rule set per entity in `app/Support/Validation/*Rules.php`, used by the Filament forms (via `FormRules::for`) and tests.
  Never write field rules in only one place. Coordinates are limited to Sri Lanka by `config/locations.php`.
- **Sanitising:** models use `SanitizesInput` (`$plainText` strips tags, `$richText` purified by the "rich" allow-list in `config/purify.php`).
- **Images:** spatie medialibrary via `InteractsWithImages` (thumb/card/large WebP). Upload rules in `CommonRules::image()`: no SVG, max 5 MB, min 800×500.
- **Public API:** read-only `GET /api/v1/*`, 60 req/min. The one write route is `POST /journey-requests` (extra per-IP limit 5/min + 20/day, honeypot `website`). Only `published` points in active cities, `published` tours in active categories.
  Successful GETs send `Cache-Control: public, max-age=60` + `ETag` (304 on repeat) via `PublicApiCache` (`API_CACHE_SECONDS`, 0 = off).
- **Queue:** `QUEUE_CONNECTION=database`. The 1600px `large` image conversion is queued, so run `php artisan queue:work` (cron/Supervisor in production).
- **Inquiries:** `guests` (one row per person, matched by lower-cased email; a returning guest's details are updated, a deleted one restored)
  1─* `inquiries` (reference `LG-YYMM-#####`, status New → Contacted → Quoted → Booked / Lost, `type` custom_tour | package, `source`)
  *─* destinations / activities (ordered pivots) · 1─* `inquiry_notes` (timeline). Option lists for the trip fields live in
  `config/inquiries.php` and must match `frontend/src/components/journey/journeyOptions.js`. Website intake: `App\Services\InquiryIntake`.
  Every status change writes a timeline row from the `Inquiry` model itself (set `$inquiry->statusNote` first to attach a note).
- **Filament gotcha:** closures get their arguments injected by *name*. Use `$query`, `$record`, `$get`, `$state`, never `$q` or `$d`.
- **New module checklist:** migration → model (+ traits) → `*Rules` → Policy + `Permissions::RESOURCES` → seeder → Filament Resource (follow "Admin UI" below) → API Resource/controller/route → tests → add to `LIVE_MODULES`.

### Admin UI (Filament): compact, fits one screen
The owner wants admin pages to fit on one screen with as little scrolling as possible. Shared setup lives in
`app/Filament/Support/AdminUi.php` (registered in `AdminPanelProvider::boot()`) and `backend/public/css/admin.css`.
- **Layout:** full-width content, 15rem sidebar that collapses to icons (`‹` button). Edit pages use
  `$form->columns(['lg' => 4])`: main group `columnSpan(['lg' => 3])` + side group (Publishing, images) `['lg' => 1]`.
- **Fields per row:** wrap fields in `AdminUi::grid([...])` (12 columns, 6 on tablets) and size every field
  to its data with `->span(...)`: `xs` numbers/sort/toggles · `sm` coordinates, short selects, dates, phone ·
  `md` names, slugs, emails · `lg` addresses, titles · `xl` long single-line text · `full` rich text, galleries, long textareas.
- **Help text:** `->tip('…')` (a "?" at the end of the label row, text shows on hover). **Never use `->helperText()`**
  or explanatory Placeholders/section descriptions.
- **Tables:** single-line rows, so no `->description()` under a column; give the data its own column. Image columns are 32px (global).
  Row actions are **icon-only** with a tooltip: Edit/View/Delete/Restore/ForceDelete/Replicate are configured globally;
  custom row actions must call `->iconButton()->tooltip('…')`. Don't hide row actions in an `ActionGroup`.
- **Sections** are compact (global). Fonts and spacing are reduced in `admin.css`; adjust there, not inline.
- `tests/Feature/AdminUiTest.php` renders every admin page. Add new pages to its list.

### Form validation: two layers, every form
1. **Browser (instant, UX only):** `backend/public/js/admin-validation.js` checks a field when the user leaves it,
   then on every keystroke, and blocks submit while a field is wrong. It reads the HTML attributes Filament renders,
   so **always mirror the server rule on the field**: `->required()`, `->minLength()`, `->maxLength()`,
   `->minValue()/->maxValue()`, `->integer()`/`->numeric()`, `->email()`, and `->livePattern($regex, 'message')` for every
   regex rule. Fields with `maxLength ≥ 50` get a "n / max" counter automatically. Password pairs: `data-lg-wait-for` on the
   password and `data-lg-match` on the confirmation (see `UserResource`). Filament drops `required` inside tabs/collapsibles, so
   `AdminUi` adds `data-lg-required` to every required TextInput/Textarea: call `->extraInputAttributes([...], merge: true)` (always
   `merge: true`, or that flag is lost). Selects, rich text, uploads and uniqueness are
   checked by the server only.
2. **Server (authoritative):** every field also gets `->rules(FormRules::for($rules, 'field'))` from its
   `app/Support/Validation/*Rules.php` class (and `->nestedRecursiveRules()` for array items). Never trust the browser layer.
   Any other entry point (artisan commands, future write APIs) reuses the same `*Rules` class.
- Write a clear custom message for rules the user can't guess (`->validationMessages([...])`, e.g. unique-per-city).
- Test both: required/format/unique/limit failures and a valid save per form (see `tests/Feature/FormValidationTest.php`).

- **Performance:** admin runs in Filament SPA mode (`->spa()`); OPcache must be on. After adding a Filament
  Resource/Page/Widget run `php artisan filament:cache-components`, or it won't show. Production checklist: docs §10.

Backend commands (inside `backend/`): `php artisan serve` · `php artisan test` · `./vendor/bin/pint` · `php artisan migrate --seed` (`SEED_IMAGES=true` downloads demo photos).

## Repository layout

```
TourismM/
├─ CLAUDE.md                 ← this file
├─ .claude/skills/           ← all skills live here (auto-loaded by Claude Code):
│    ui-ux-pro-max/, laravel-specialist/, find-skills/
├─ skills-lock.json          ← records where the skill came from (used by `npx skills`)
├─ .htaccess                 ← blocks web access to .claude/.git (root sits in Laragon's www)
├─ docs/backend-architecture.md ← backend PRD: domain model, schema, validation, security, API, roadmap
├─ frontend/                 ← React app (all UI work happens here)
└─ backend/                  ← Laravel 12 API + Filament admin (private path)
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
components/ui/           SectionHeading, TourCard, TourBrowser, InquiryForm, PageLoader,
                         TourRouteMap (tour page, under the enquiry form: itinerary places as numbered pins on the
                         black/gold SL map; hover/focus/tap a pin → popup with photo + details)
sections/home/           one file per home-page section (Hero, About, Destinations, WhyUs,
                         MeetGuides, DestinationMap, Tours, Testimonials, Faq, Contact)
pages/                   Home, Tours (/tours, ?category=slug), TourDetail (/tours/:slug), NotFound
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
  The island is black + gold and drawn per **district** (`utils/districts.js`: 25 districts from Natural Earth admin-1,
  same projection; regenerate, don't hand-edit). Districts that contain the shown places light up (`districtAt()`).
  Clicking a photo card in the "Explore our beautiful island" mosaic spotlights that single place on this map.
  No map library and no API key are used.

## API contract (frontend ↔ Laravel)

Set `VITE_API_URL=http://localhost:8000/api/v1` in `frontend/.env` (see `.env.example`). Modules listed in
`LIVE_MODULES` (`services/api.js`) are then fetched over HTTP. Responses are wrapped in `{ "data": ... }`.
Field names below are what the frontend expects (camelCase). Map snake_case columns in the Laravel Resource.
✅ = served by the backend now. The rest are planned and their shapes are fixed by the frontend.

| Method | Endpoint | Returns |
|--------|----------|---------|
| GET | `/settings` | site object: same shape as `config/site.js` (name, shortName, phone, whatsapp, email, address, hours, currency, founded, stats{travellers,packages,destinations,rating,reviews}, social{…}, hero{eyebrow,title,highlight,subtitle,videos[],poster}) |
| GET ✅ | `/destinations` | `[{ id, slug, name, category, lat, lng, themes[], featured, description, image, thumb, city{id,name,slug} }]`. `description` = short summary. Filters: `?theme=&city=&type=&featured=1`. The mosaic shows `featured` points that have an `image` |
| GET ✅ | `/destinations/{slug}` | the above + `content` (HTML), `gallery[]`, `address`, `openingHours`, `entryFee`, `bestTimeToVisit`, `visitDurationMinutes`, `district`, `province` |
| GET ✅ | `/cities`, `/cities/{slug}` | cities with `destinationsCount`; the detail endpoint includes `destinations[]` |
| GET ✅ | `/themes` | `[{ id, slug, title, image, side }]` (`side`: `"left"`/`"right"` column on desktop; 6 themes fit the layout best) |
| GET ✅ | `/place-types` | `[{ id, slug, name, icon }]` |
| GET ✅ | `/tour-categories` | `[{ id, slug, name, label, icon, summary, image, thumb, toursCount }]`. `label` = name without a trailing "Tours" (website tabs). How trips are classified (Beach Tours, Cultural & Heritage…). Separate from map themes |
| GET ✅ | `/tours` | `[{ id, slug, title, category, categorySlug, categoryLabel, duration, days, price, currency, featured, image, thumb, summary, highlights[], itinerary[{title,details}], inclusions[] }]`. `duration` = "5 Days / 4 Nights" from the day count; `price` = lowest per-person price. Filters: `?category=&featured=1`. Home "Discover Sri Lanka" shows `featured` tours (first 6 if none) + "Show more tours" → `/tours` page (all tours; the header "Tours" menu links there too). Tabs follow the `/tour-categories` order |
| GET ✅ | `/tours/{slug}` | the above + `content`, `exclusions[]`, `pace`, `groupMin`, `groupMax`, `startPoint`, `endPoint`, `bestMonths[]`, `prices[{paxFrom,paxTo,pax,vehicle,pricePerPerson}]`, `gallery[]`, and a full `itinerary[{day,title,details,overnight{name,slug},hotel,meals[],activities[{id,slug,name,description,image,thumb}],distanceKm,driveHours,image,destinations[{id,slug,name,lat,lng,category,city,description,image,thumb}]}]` (published points, active activities only). 404 → "Tour not found" |
| GET | `/guides` | `[{ id, name, role, bio, photo, languages[], specialties[], years, toursLed, rating, whatsapp }]` |
| GET | `/testimonials` | `[{ id, name, country, message, rating, photo }]` |
| GET | `/faqs` | `[{ id, question, answer }]` |
| GET ✅ | `/activities` | `[{ id, slug, name }]` active activities in admin sort order (choices in the "Build your own journey" popup) |
| POST ✅ | `/journey-requests` | "Build your own journey" popup (`components/journey/`). Body (snake_case): `arrival_date, arrival_time, departure_date, flexible_dates, arrival_airport, occasion, pace, men, women, children, child_ages[], accommodation, rooms, meal_plan, vehicle, guide_language, budget, dietary, accessibility, destination_ids[], activity_ids[], notes, name, email, phone, country, contact_method, referral, website` (honeypot). Option values are listed in `journeyOptions.js`. → 201, or 422 `{ errors }` (`child_ages.N` maps to that child's field), 429 when over the limit. 201 body: `{ data: { reference } }`, shown to the guest. Rules: `InquiryRules::website()`; option keys: `config/inquiries.php` |
| POST | `/inquiries` | body: `{ name, email, phone, country, date, travellers, tour, tour_slug, message }` → 201. Validation errors → 422 `{ errors: { field: [msg] } }` (Laravel default, shown under the fields) |

Remaining admin scope (roadmap in `docs/backend-architecture.md`): guides, testimonials, FAQs,
settings page (contact details, stats, hero video upload), the short tour enquiry form (store it as an inquiry with `type=package`).
CORS origins come from `FRONTEND_URLS` in `backend/.env`.

## Deployment

`npm run build` → upload `frontend/dist/`. `public/.htaccess` is copied into `dist/` and rewrites unknown
paths to `index.html` so `/tours/:slug` deep links work on Apache. For Nginx use `try_files $uri /index.html;`.
