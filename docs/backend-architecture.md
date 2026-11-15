# Backend Architecture & PRD: Lean Global Travel & Tourism

Status: **Phase 1 (Locations: Cities + Destination points) built.** Later modules are planned below.
Stack: Laravel 12 · PHP 8.2 · MySQL 8 · Filament 3 (admin) · spatie/laravel-permission · spatie/laravel-medialibrary.

---

## 1. Goals

1. The admin manages **Cities** and the **Destination points** inside each city
   (e.g. Anuradhapura → Ruwanwali Maha Saya, Jaya Sri Maha Bodhi; Ella → Ravana Falls, Nine Arch Bridge).
2. Each point has images, descriptions, latitude/longitude and standard travel info. Points appear as pins on the
   website's "Discover your perfect destination" Sri Lanka map, filtered by **Theme**.
3. The admin is **private**: it is reachable only through a secret URL path (`ADMIN_PATH`) and never linked from the website.
4. The architecture can be extended (tours, guides, testimonials, inquiries, settings) without reworking Phase 1.
5. Every input is validated on the server, and secure defaults are used everywhere.

## 2. Domain model

```
Province 1─* District 1─* City 1─* Destination *─* Theme
TourCategory 1─* Tour 1─* TourDay *─* Destination   (ordered: pivot sort_order = visit order)
                  Tour 1─* TourPrice                 (group-size price table)
                  TourDay *─1 City                   (overnight)
                                     │  *─1 PlaceType
                                     └─ media (cover + gallery)   [polymorphic, reusable]
User *─* Role *─* Permission                                     [spatie/permission]
```

| Entity | Purpose | Managed by |
|---|---|---|
| **Province** (9) | Administrative grouping, used for filters | Seeded. Super Admin can edit |
| **District** (25) | Belongs to a province. A city belongs to a district | Seeded. Super Admin can edit |
| **City** | e.g. Anuradhapura, Ella, Kandy. Has its own cover image, description and centre lat/lng | Admin CRUD |
| **PlaceType** | Kind of point: Waterfall, Temple, Beach, National Park, Viewpoint… | Admin CRUD |
| **Theme** | Map filter cards (Popular Beaches, Wildlife & Nature…). `side` = left/right column | Admin CRUD |
| **TourCategory** | How trips are classified (Beach Tours, Cultural & Heritage Tours…). Name, slug, icon, short description, cover image. One category has many tours | Admin CRUD |
| **Tour** | A sellable trip ("7 Days Cultural Triangle"). Exactly one category. Overview (summary, highlights, pace, group size, start/end, best months), cover + gallery, inclusions/exclusions, SEO, draft/published, featured. **Duration is not stored**: it is the number of days | Admin CRUD ("Plan a tour") |
| **TourDay** | One itinerary day: title, description, destination points **in visit order**, overnight city, hotel (free text), meals (B/L/D), activities, drive km/hours, photo. `day_number` = repeater order | Edited inside the tour |
| **Activity** | Library of things to do (Jeep safari, White-water rafting …): name, description, related images, active, sort. Seeded with ~54 Sri Lankan activities (`ActivitySeeder`, also in production). Picked per itinerary day (pivot `activity_tour_day.sort_order`) by search; "+" on the tour form adds a new one | Admin CRUD (Tours → Activities) |
| **TourPrice** | Price per person for a group-size range (`pax_from`–`pax_to`, empty = "and more") + vehicle. Ranges may not overlap. Website "From" = lowest row | Edited inside the tour |
| **Destination** | A point on the map. Belongs to one City and one PlaceType, and has many Themes | Admin CRUD |

Why this shape:
- **City → Destination (1:N)** matches the brief directly. The city gives each point its context (breadcrumbs,
  "more in Ella", city pages later).
- **Tours pick destination points per day** (pivot `destination_tour_day` with `sort_order`), so "tours that visit Sigiriya" is one query (`Destination::tourDays`). Categories do not link to points directly. Map themes stay separate and only drive the website map.
- **Publishing a tour** needs a cover image, at least one day and one price row (form rules + the list's publish action). **Duplicate** deep-copies days, points, prices and photos as a new draft (7-day → 10-day tours).
- **Destination ↔ Theme (N:N)** allows one point under several cards (Arugam Bay = Beaches + Adventure).
- **PlaceType** is a lookup table, not a hard-coded enum, so the admin can add types without a deploy.
- **Media is polymorphic** (`media` table via medialibrary). Tours, guides and others reuse it later with no new tables.
- **Province/District** are seeded reference data, so filters such as "Places in the Central Province" come free.

## 3. Database schema (Phase 1)

All tables use `id` bigint PK and `timestamps`. Business tables also have `softDeletes` (restorable from the admin).

### provinces
| column | type | rules |
|---|---|---|
| name | varchar(100) | unique |
| slug | varchar(120) | unique |

### districts
| column | type | rules |
|---|---|---|
| province_id | FK → provinces | restrictOnDelete |
| name | varchar(100) | unique |
| slug | varchar(120) | unique |

### cities
| column | type | notes |
|---|---|---|
| district_id | FK → districts | restrictOnDelete, indexed |
| name | varchar(120) | unique per district |
| slug | varchar(140) | unique (global) |
| summary | varchar(300) null | plain text |
| description | text null | sanitized HTML |
| latitude / longitude | decimal(10,7) | Sri Lanka bounds |
| is_active | bool default true | indexed |
| sort_order | uint default 0 | |
| meta_title / meta_description | varchar(70) / varchar(160) null | SEO |
| deleted_at | timestamp null | |
| media | cover (1) | via medialibrary |

### place_types
`name` (unique, 60) · `slug` (unique) · `icon` (Bootstrap icon name, nullable) · `sort_order` · `is_active`

### themes
`title` (unique, 80) · `slug` (unique) · `side` enum(`left`,`right`) · `sort_order` · `is_active` · media: image (1)

### destinations
| column | type | notes |
|---|---|---|
| city_id | FK → cities | restrictOnDelete (a city with points can't be hard-deleted) |
| place_type_id | FK → place_types | restrictOnDelete |
| name | varchar(150) | unique per city |
| slug | varchar(170) | unique |
| summary | varchar(300) | required, plain text: card text / map tooltip |
| description | longtext null | sanitized HTML |
| latitude / longitude | decimal(10,7) | **required**, Sri Lanka bounds |
| address | varchar(255) null | |
| opening_hours | varchar(150) null | free text, e.g. "Daily 07:00–17:30" |
| entry_fee | varchar(150) null | free text, e.g. "USD 30 (foreigners)" |
| best_time_to_visit | varchar(150) null | |
| visit_duration_minutes | smallint null | 5–1440 |
| status | enum(`draft`,`published`) | only `published` reaches the API |
| is_featured | bool | also shown in the home-page photo mosaic (cover image required) |
| sort_order | uint | |
| meta_title / meta_description | | SEO |
| deleted_at | | |
| media | cover (1, required to publish) + gallery (max 12) | |

Indexes: `(status, is_featured)`, `(city_id, status)`, `(latitude, longitude)`.

### destination_theme (pivot)
`destination_id` FK cascadeOnDelete · `theme_id` FK cascadeOnDelete · PK(destination_id, theme_id)

## 4. Validation rules

All rules live in one place per entity (`app/Support/Validation/*Rules.php`: City, Destination, Theme, PlaceType,
District, User). The Filament forms, the `make:admin` command **and** any future API write endpoints use the same rules,
so they can't drift apart.

**Two layers.** (1) Instant browser checks (`public/js/admin-validation.js`) from the field's HTML attributes:
required, length, number range, email, `livePattern` regex, password match. These run on blur and while typing, and they
block submit. They exist for speed only. (2) The server runs the full rule set on every submit, including uniqueness,
existence of related records and image checks. See CLAUDE.md → "Form validation".

| Field | Rules |
|---|---|
| name | required · string · 2–150 · `unique` within parent (city / district), ignoring the current record |
| slug | required · `alpha_dash` lowercase (`^[a-z0-9]+(?:-[a-z0-9]+)*$`) · max 170 · unique · auto-filled from name |
| summary | required (destination) · string · max 300 · tags stripped |
| description | nullable · max 20 000 chars · **HTML purified** (allow-list: p, br, strong, em, ul, ol, li, a[href], h3, h4, blockquote) |
| latitude | required · numeric · between **5.85 and 9.95** (Sri Lanka) |
| longitude | required · numeric · between **79.5 and 82.0** |
| city_id / district_id / place_type_id | required · `exists` (active, not soft-deleted) |
| themes | array · max 6 · each `exists:themes,id` |
| visit_duration_minutes | nullable · integer · 5–1440 |
| opening_hours / entry_fee / best_time / address | nullable · string · max 150/150/150/255 · tags stripped |
| status | required · enum `DestinationStatus` |
| cover image | jpg/jpeg/png/webp · max **5 MB** · min **800×500 px** · required when status = published or featured |
| gallery images | same file rules · max 12 files |
| meta_title / meta_description | nullable · max 70 / 160 |
| theme.side | required · enum `ThemeSide` (left/right) |
| sort_order | integer · 0–65535 |

Uploaded images are re-encoded through medialibrary conversions (`thumb` 400w, `card` 800w, `large` 1600w, WebP),
which also strips EXIF/GPS metadata from the generated files.

## 5. Security

| Concern | Measure |
|---|---|
| **Private admin entrance** | Filament panel path = `config('admin.path')` ← `ADMIN_PATH` env (random, e.g. `lg-c6e4aeab58`). There is no link from the website, nothing is linked from `/`, and responses carry `X-Robots-Tag: noindex`. `/admin`, `/login` and `/` return **404**/a neutral page. |
| Authentication | Filament login, bcrypt passwords (min 12 chars, mixed case, numbers, not in breach list in production), **5 attempts/minute rate limit** (built-in), no public registration, no password reset unless mail is configured |
| Panel access | `User::canAccessPanel()` → `is_active` **and** has a role |
| Authorisation | spatie roles + permissions; one **Policy per model** (`cities.view`, `cities.create`, `cities.update`, `cities.delete`, `cities.restore`, …). Super Admin bypasses via `Gate::before` |
| Roles | **super-admin** (everything incl. users, roles, provinces/districts) · **content-editor** (cities, destinations, tour categories, tours, activities, place types, themes) · **inquiry-manager** (future inquiries module) |
| Sessions | encrypted DB sessions, `secure` + `httponly` + `samesite=lax` cookies in production, CSRF on every admin form |
| XSS | Rich text purified on save (allow-list). Plain fields are tags-stripped. React renders text, never raw HTML (except purified descriptions) |
| SQL injection | Eloquent / query builder only, no raw SQL with user input |
| Mass assignment | explicit `$fillable` on every model |
| File uploads | MIME + extension + size + dimensions validated, random file names, stored on the `public` disk under `media/`, **no SVG uploads**, PHP execution denied in `storage/` via `.htaccess` |
| Public API | **read-only GET**, rate-limited (60 req/min/IP), only `published`/`is_active` data, API Resources whitelist the fields (no internal columns leak), query filters validated (422 JSON), slugs regex-constrained, CORS limited to `FRONTEND_URLS` with GET only, short public caching + ETag on 200s (errors never cached) |
| CORS | only origins in `FRONTEND_URLS` |
| Headers | `SecurityHeaders` middleware: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` |
| Production | `APP_DEBUG=false`, `APP_ENV=production`, HTTPS forced, `php artisan optimize` |

## 6. Application structure

```
backend/app/
├─ Enums/                 DestinationStatus, ThemeSide
├─ Models/                Province, District, City, PlaceType, Theme, Destination, User
├─ Models/Concerns/       HasSlug (auto slug), Sanitizes (purify/strip on save)
├─ Policies/              one per model (permission-based)
├─ Support/Validation/    CityRules, DestinationRules, TourCategoryRules, TourRules (tour + dayRules + priceRules + no-overlap check), ActivityRules, ThemeRules, PlaceTypeRules, CommonRules (geo, image)
├─ Services/              DestinationQuery (filters + eager loading for the API)
├─ Http/Controllers/Api/V1/  read-only controllers (thin: call service → Resource)
├─ Http/Resources/V1/     CityResource, DestinationResource, ThemeResource, PlaceTypeResource
├─ Http/Middleware/       SecurityHeaders
├─ Filament/Resources/    CityResource, DestinationResource, ThemeResource, PlaceTypeResource,
│                         DistrictResource, UserResource (+ relation manager: City → Destinations)
└─ Providers/Filament/    AdminPanelProvider (private path, brand colours: ink + gold)
```

Extending: a new module (e.g. Tours) = migration + model (+ `HasMedia`) + `Rules` class + Policy + permissions
seeder entry + Filament Resource + API Resource/controller. Nothing existing changes.

## 7. Public API (v1, read-only)

Base: `{APP_URL}/api/v1`. The frontend sets `VITE_API_URL=http://localhost:8000/api/v1`.

| Method | Endpoint | Returns |
|---|---|---|
| GET | `/themes` | `[{ id, slug, title, side, image }]` (active, sorted) |
| GET | `/destinations` | `[{ id, slug, name, category, lat, lng, themes[], featured, summary, description, image, city{ id, name, slug } }]`. Filters: `?theme=slug`, `?city=slug`, `?featured=1` |
| GET | `/destinations/{slug}` | single point + `gallery[]`, travel info, city, district, province |
| GET | `/cities` | `[{ id, slug, name, district, province, lat, lng, image, destinationsCount }]` |
| GET | `/cities/{slug}` | city + its published destinations |
| GET | `/place-types` | `[{ id, slug, name, icon }]` |
| GET | `/tour-categories` | `[{ id, slug, name, label, icon, summary, image, thumb, toursCount }]` (active, sorted; count = published tours) |
| GET | `/tours`, `/tours/{slug}` | see CLAUDE.md → API contract. Only published tours in an active category |

`category` = PlaceType name and `image` = cover `card` (800w) URL, so the existing React map and mosaic work
without changes. Responses are wrapped in `{ "data": … }` (the frontend already unwraps it).

## 8. Roadmap

| Phase | Modules |
|---|---|
| **1 (done)** | Auth + roles, Provinces/Districts (seeded), Cities, Place types, Themes, Destinations + images, public API |
| 2 | Tours ✅ (categories, day-by-day itinerary linked to destinations and the activities library, group-size prices). Next: map picker in the admin form, Guides |
| 3 | Inquiries inbox (POST `/inquiries`, status new/read/replied, email notification via queue), Testimonials, FAQs |
| 4 | Site settings (contact, stats, hero video upload), activity log/audit trail, sitemap |

## 9. Running it

```bash
cd backend
composer install
cp .env.example .env && php artisan key:generate   # set DB_* and a random ADMIN_PATH
php artisan migrate --seed                          # provinces, districts, roles, place types, themes, sample data
php artisan storage:link
php artisan make:admin                              # interactive: creates a super-admin user
php artisan serve                                   # http://localhost:8000/{ADMIN_PATH}
php artisan test
```

## 10. Performance & production checklist

Why the admin was slow (2026-11-14): OPcache was off in Laragon's PHP, no Laravel/Filament caches existed,
and sessions/cache used the database driver. Fixes applied:

- **SPA mode:** `->spa()` in `AdminPanelProvider` swaps page content via `wire:navigate` (no full reload).
- **OPcache:** must be on in every environment (`zend_extension=opcache`, `opcache.enable=1`,
  `opcache.memory_consumption=256`, `opcache.max_accelerated_files=20000`). Production: `opcache.validate_timestamps=0`
  and reload PHP-FPM on each deploy. Do not load Xdebug on the web server.
- **Drivers:** `SESSION_DRIVER=file`, `CACHE_STORE=file` (or `redis` in production if available).
- **Caches:** locally `php artisan icons:cache` + `php artisan filament:cache-components`.
  Re-run `filament:cache-components` (or `filament:clear-cached-components`) after adding a Resource/Page/Widget,
  otherwise the new one does not appear. Production deploy: `php artisan optimize` + `php artisan filament:optimize`.
- **Tables:** every Resource table eager-loads relations and media with `modifyQueryUsing(... ->with([...]))`
  and uses `withCount` for counts. Keep this for new resources (avoids N+1 queries).

Before go-live: `APP_ENV=production`, `APP_DEBUG=false` (debug pages leak stack traces and env values),
`LOG_LEVEL=warning`, HTTPS, `expose_php=Off`.
