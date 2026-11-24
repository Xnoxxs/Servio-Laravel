# Servio requirements

Servio is a university project for finding and booking local services such as car washing, house cleaning, plumbing, gardening, electrical work and moving. The scope below comes from the project instructions and Phase 1 brief. Course alignment is pending because the 12 sections were unavailable in this checkout.

## Required MVP

| Area | Acceptance criteria |
| --- | --- |
| Public marketplace | Guests can browse published services, search by keyword, filter by category and open a detail page showing the description, provider, price, image when supplied and visible reviews. |
| Shared accounts | Users can register, log in and log out. The same account can publish its own services and request services from other users. Administrative permissions are granted separately. |
| Provider services | Authenticated users can list, create, edit and remove their own services from the marketplace, upload or replace a service image and see incoming requests for their services. |
| Bookings | A signed-in customer selects a future date and time for another user's published service. The request starts pending and stores the price shown for the service at creation. Customers and providers see their respective booking dashboards. |
| Booking decisions | Providers accept or reject pending requests for their own services and can complete accepted bookings. Customers can cancel eligible bookings. The server enforces ownership and permitted state transitions. |
| Reviews | Customers can submit one review per completed booking they made. Visible reviews appear on the associated service. |
| Administration | Administrators can access a protected landing page. Category management, listing moderation and review moderation are deferred. |
| Email | Booking lifecycle changes send queued email to the affected customer or provider. |
| Interface and quality | Use reusable Blade layouts and components, Tailwind CSS, responsive layouts, accessible forms and automated Pest tests. GitHub Actions verifies the application before merge. |

## Proposed business rules for approval

- A customer can cancel a pending or accepted booking before its scheduled start. A provider can accept a pending booking before its start, reject a pending booking at any time and complete an accepted booking at or after its start. Rejected, cancelled and completed bookings are terminal. Past pending requests stay visible and can be rejected. No automatic expiry is proposed.
- Use EUR prices in integer cents. Display a single price per service and preserve both amount and currency on each booking. Show times in Europe/Madrid, convert to UTC for persistence and reject invalid or ambiguous local times with a clear validation message.
- Services have one category and an optional cover image. Removing a listing unpublishes it and preserves its booking and review history. A category in use cannot be deleted until its listings are reassigned. Existing bookings can progress after unpublishing.
- A review contains a rating from 1 to 5 and an optional written comment. Public pages show visible reviews. Administrative actions do not grant authority to act as a customer or provider on another user's booking.
- Send new request and cancellation emails to the provider, and acceptance, rejection and completion emails to the customer.

## Scope boundaries

Online payments, real-time chat, AI recommendations, external maps and complex scheduling are outside the MVP. The booking workflow uses one appointment start and a saved duration. Accepting a request checks for overlaps against accepted or completed bookings for the same service. An appointment can start when the preceding one ends. Optional enhancements for a later decision include multiple images, password reset screens, email verification, review editing and booking reminders. Course requirements may change the priority of these items once the references are available.
