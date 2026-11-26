# Servio architecture

## Repository baseline

| Area | Observed |
| --- | --- |
| Runtime | PHP 8.5.12 and Laravel 13.43.0 installed. Composer declares Laravel ^13.17. |
| Skeleton | Standard Laravel skeleton. The only application page is the default Blade welcome page at `/`. Bootstrap adds the health endpoint at `/up`. No marketplace controllers, policies or CI workflow are present. |
| Authentication | The `web` session guard uses the Eloquent `User` provider. The model already hashes passwords and supports notifications. Starter migrations provide users, sessions and password reset tokens. No auth starter kit, registration/login routes or auth views are installed. |
| Frontend | Vite 8.3.8, Laravel Vite plugin 3.2.1 and Tailwind CSS 4.3.5. Keep the existing CSS and JS entry points, Blade and Instrument Sans. |
| Database | The active connection is SQLite and the three starter migrations have run. The example environment uses SQLite, database-backed sessions/cache/queues and log mail. Pest uses in-memory SQLite, array session/cache/mail and a synchronous queue. |
| Tooling | Laravel Boost 2.10.4, Pint 1.32.3, Pest 5.3.7 and Pest Laravel plugin 5.0.1. Boost is installed, but its MCP tools were unavailable in this session. |

Retain the existing bootstrap, guard, user model, infrastructure tables and frontend build. Phase 3 adds Blade registration, login and logout using Laravel's installed session authentication and password hashing. Apply guest and auth middleware, CSRF protection, login throttling, session regeneration after sign-in and session invalidation with CSRF token regeneration on sign-out. Public registration cannot set administrative privileges. Keep password reset infrastructure available for a later decision.

## Proposed schema and Eloquent relationships

All domain tables use generated integer primary keys and timestamps. Add migrations in dependency order and preserve the existing user and infrastructure migrations.

| Table | Fields and constraints | Relationships |
| --- | --- | --- |
| `users` | Keep existing name, unique email, verification timestamp, hashed password and remember token. Add `is_admin` boolean default false. | Has many services via `provider_id` and customer bookings via `customer_id`. Incoming provider bookings are reached through owned services. |
| `categories` | Required name and unique slug. | Has many services. |
| `services` | Required `category_id` and `provider_id` foreign keys, title, description, `price_cents` nonnegative integer and `is_published` boolean default false. Nullable `image_path`. | Belongs to category and provider, has many bookings and has many reviews through bookings. |
| `bookings` | Required `service_id` and `customer_id` foreign keys, UTC `scheduled_at`, `status` string default `pending`, nonnegative `price_cents` and three-character `currency`. | Belongs to service and customer, has one review. Provider identity follows the service, whose owner is immutable. |
| `reviews` | Required unique `booking_id` foreign key, integer rating constrained to 1–5, nullable comment and `is_hidden` boolean default false. | Belongs to booking. Author and service derive from the booking. |

Use restrictive foreign keys to preserve referenced users, categories, services and bookings. Use portable constraints and validate allowed values in application code. Cast booking status to a backed enum, timestamps to date-time values and publication/admin/moderation flags to booleans. Seed default categories and deterministic demo data using factories. Provision admins through a trusted operator workflow. Keep demo identities and credentials out of production provisioning.

Index marketplace queries by publication and category, provider listings by provider, bookings by customer and scheduled time, and incoming bookings by service, status and scheduled time. The unique review foreign key enforces one review per booking. Use deterministic ordering with an ID tiebreaker, bound search parameters, pagination and eager loading for the relationships displayed in lists.

## Routes and controller responsibilities

| Access | Method and path | Named route and responsibility |
| --- | --- | --- |
| Public | GET `/`, `/services` | `home`, `services.index` → `ServiceController@home` and `ServiceController@index` render published listings with validated search and category filters. |
| Public | GET `/services/{service}` | `services.show` → `ServiceController@show` renders a published service and visible reviews. |
| Guest | GET/POST `/signIn`, GET/POST `/login` | `signIn`, `signIn.store`, `login`, `login.store` → registration and session controllers render forms and process submissions. |
| Signed in | POST `/logout` | `logout` → session controller ends the session. |
| Provider | GET `/provider` | `provider.dashboard` → provider dashboard controller summarizes owned services and incoming bookings. |
| Provider | GET/POST `/provider/services`, GET `/provider/services/create`, GET `/provider/services/{service}/edit`, PATCH/DELETE `/provider/services/{service}` | `provider.services.*` → resource controller handles owned listing CRUD and images. DELETE unpublishes. |
| Customer | GET `/bookings`, GET `/bookings/{booking}` | `bookings.index/show` → customer booking controller scopes reads to the customer. |
| Customer | GET `/services/{service}/bookings/create`, POST `/services/{service}/bookings` | `bookings.create/store` → service controller displays the request form and records the booking. |
| Provider | GET `/provider/bookings`, GET `/provider/bookings/{booking}` | `provider.bookings.index/show` → provider controller scopes records to owned services. |
| Booking participant | PATCH `/bookings/{booking}/cancel`; PATCH `/provider/bookings/{booking}/{accept, reject, complete}` | `bookings.cancel` and `provider.bookings.accept/reject/complete` → transition actions call shared booking logic. Each action has a literal route and explicit authorization. |
| Customer | POST `/bookings/{booking}/reviews` | `bookings.reviews.store` → review controller validates and creates a review for an eligible booking. |
| Admin | GET `/admin` | `admin.dashboard` → protected administrator landing page. Category management and moderation routes are deferred. |

Use named web routes, model binding, Form Requests for nontrivial inputs and model policies for resource actions. The admin route requires authentication and the admin middleware. Service mutation routes require ownership. Booking reads are scoped by dashboard and record access is checked on direct URLs. Booking mutations require the appropriate participant and state. Review creation requires the booking customer and completed status. Public queries and detail routes exclude unpublished services and hidden reviews.

## Workflow integrity and infrastructure

- Derive customer, provider, initial status and price server-side. At creation, check publication, ownership and future time and take the price snapshot as part of the write. Protect creation against concurrent unpublication or price edits using a transaction with serialization or a conditional write compatible with the selected database.
- Centralize booking transitions. Use conditional updates against the expected current state so concurrent actions cannot both succeed on SQLite or a future row-locking engine. Recheck the applicable time constraint at the write boundary, and surface conflicts without repeating the transition or email. Validate and insert reviews with the unique booking constraint as the final concurrency guard.
- Use Laravel's filesystem with generated image paths. Validate raster type, file size and dimensions. Store a new file before updating its database reference, clean it up on failed persistence and remove the superseded file after a successful update. Never accept a storage path from the request.
- Use queued Laravel Notifications after the booking transaction commits. Use the existing database queue initially, a worker and retry/backoff settings. Document failed job handling. Include a relevant dashboard link and send one notification per successful state change in normal operation. Mail transport retries can produce duplicate delivery.
- Keep data access in controllers, models or focused domain operations. Reuse Blade components for navigation, forms, alerts, listing cards and booking statuses. Verify keyboard access, form errors, empty states and responsive dashboard layouts.

## Testing strategy

Use Pest feature tests with factories and isolated database state. Test guest/member/admin boundaries, attempted role escalation, cross-account direct URLs, publication and search filters, assignment of ownership, uploads and replacement cleanup, price snapshots, time boundaries, every state transition and invalid actor/state pairing, review uniqueness and moderation, and notification recipients. Use a fake disk and notifications, freeze time for time-dependent cases and assert database state as well as response behavior. Include coverage that exercises conditional writes and verifies that rejected transitions do not notify. Check actual queue processing and delivery separately because the test queue runs synchronously. GitHub Actions should install locked dependencies, build assets, run Pest and validate formatting. Phase 9 includes a manual mobile and desktop walkthrough.
