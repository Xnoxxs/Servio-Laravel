# Servio

Servio is a Laravel marketplace for local services such as cleaning, gardening, plumbing and moving. Visitors can browse, search and filter listings. A single account can publish services and request appointments from other providers. Providers can accept, reject and complete bookings. Customers can cancel eligible appointments and review completed bookings.

The app uses Laravel 13, Blade, Tailwind CSS, Vite and SQLite for local development. Booking emails are queued. The administrator landing page is restricted to administrators, and management tools are deferred.

## Local setup

Install PHP 8.5 with PDO SQLite and GD, Composer, and Node.js 22.12 or newer with npm. From a fresh checkout, run:

```sh
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
php artisan serve
```

Open the address Artisan prints, usually `http://127.0.0.1:8000`. Keep the server terminal open. Set `APP_URL` in `.env` to the address you actually use so image URLs and email links resolve correctly. For frontend development, run `npm run dev` in a separate terminal. On an existing checkout, keep your existing `.env` and application key.

## Demo data

`php artisan db:seed` loads the default categories and, in local and testing environments, demo listings, bookings and reviews. In a fresh local database, use either of these accounts with the password `password`.

| Account | Email |
| --- | --- |
| Demo provider | `provider@servio.example` |
| Demo customer | `customer@servio.example` |

Set `DEMO_PROVIDER_EMAIL` before the first seed to choose the demo provider’s login and booking email address. You can also register a new account to test the customer and provider workflows.

Service covers are stored under `storage/app/public`. For demo photos on a fresh checkout, copy the generated `demo-services` folder into that directory and re-run `php artisan db:seed`. To generate them instead, set `OPENAI_API_KEY` privately in `.env` and run `php artisan servio:generate-demo-images --images=2`. Image generation uses a paid API.

## Booking email in development

Use `QUEUE_CONNECTION=database` and `MAIL_MAILER=log` in your local `.env` to inspect email without sending it externally. In separate terminals, run:

```sh
php artisan queue:work database --timeout=60
tail -f storage/logs/laravel.log
```

Request or update a booking in the browser. The worker logs the email with its recipient, subject and booking link. Use `php artisan queue:failed` to inspect failed jobs and `php artisan queue:retry <job-id>` to retry a specific job after fixing the cause.

## SendGrid delivery

To enable live email, configure the following in the environment where the worker runs:

```dotenv
MAIL_MAILER=sendgrid
SENDGRID_API_KEY=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=Servio
QUEUE_CONNECTION=database
```

Use a SendGrid key with Mail Send permission and a verified From address. The mailer uses SMTP with required TLS on port 587. Refresh cached configuration if applicable and restart the worker after changing its settings. [SendGrid SMTP setup](https://www.twilio.com/docs/sendgrid/for-developers/sending-email/integrating-with-the-smtp-api) · [Sender verification](https://www.twilio.com/docs/sendgrid/ui/sending-email/sender-verification)

## Tests

```sh
vendor/bin/pest --compact
```

The test suite uses an in-memory SQLite database and a test mailer. It covers authentication, listing ownership, uploads, booking transitions, reviews and notification routing. For a focused run, pass a file such as `tests/Feature/BookingLifecycleTest.php` to Pest.
