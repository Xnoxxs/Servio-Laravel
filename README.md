<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Booking email in development

Keep `QUEUE_CONNECTION=database` and `MAIL_MAILER=log` in your local `.env`. Set `APP_URL` to the Servio address you open in your browser so the queued emails link to the right app. Apply the migrations and start a worker from the project folder:

```sh
php artisan migrate
php artisan queue:work database --timeout=60
```

In a second terminal, run `tail -f storage/logs/laravel.log` with the default local logging configuration. Request or update a booking in the browser. The worker writes the outgoing message to the log, so you can check its recipient, subject and booking link locally. Check failed jobs with `php artisan queue:failed`; after fixing the cause, retry a specific job with `php artisan queue:retry <job-id>`.

The demo provider login and its booking email address can be set with `DEMO_PROVIDER_EMAIL` in the local environment before seeding.

## SendGrid delivery

For an environment that should send real email, set these values in its private environment configuration:

```dotenv
MAIL_MAILER=sendgrid
SENDGRID_API_KEY=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=Servio
QUEUE_CONNECTION=database
```

Use a SendGrid API key with Mail Send permission and a From address verified through Single Sender Verification or domain authentication. The `sendgrid` mailer uses SMTP with required TLS on port 587. Refresh cached configuration if your environment uses it, then restart the queue worker so it reloads the settings. [SendGrid SMTP setup](https://www.twilio.com/docs/sendgrid/for-developers/sending-email/integrating-with-the-smtp-api) · [Sender verification](https://www.twilio.com/docs/sendgrid/ui/sending-email/sender-verification)
