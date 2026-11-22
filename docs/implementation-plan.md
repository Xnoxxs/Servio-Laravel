# Servio implementation plan

Start each phase after the previous branch is merged into `main` and the user opens the next phase on its dedicated branch. At each milestone, run the relevant checks and present the exact files and proposed commit. Continue after the user confirms the commit.

| Phase and branch | Milestones | Dependencies and acceptance |
| --- | --- | --- |
| 1 · `feature/architecture` | Inspect repository and draft requirements, architecture, plan and progress. Reconcile the documents with all 12 course sections when supplied. | Course material and user approval. Schema, authorization, workflows and scope are reviewed before application implementation starts. The supplied branch name applies to this phase. |
| 2 · `feature/02-database` | Create schema and constraints. Add models, relationships, factories and seeders. | Approved Phase 1 design. A clean SQLite database migrates and seeds, starter tables are preserved and tests cover business constraints and relationships. |
| 3 · `feature/03-auth` | Build Blade registration and session flows. Add admin access boundary and document provisioning. | User model and schema. A user can register, sign in and out, use one identity and cannot self-grant admin access. |
| 4 · `feature/04-marketplace` | Build shared Blade layout and public listings, then search, category filters and service detail. | Category and service data. Guests can find published services and use the marketplace on mobile and desktop. |
| 5 · `feature/05-provider` | Add provider dashboard and ownership-scoped listing CRUD. Add image upload, replacement and cleanup. | Auth and marketplace. Providers manage their own listings, see publication changes on the marketplace and cannot modify other accounts' listings. |
| 6 · `feature/06-bookings` | Implement requests and customer history. Add provider dashboard, decisions, cancellation and completion. | Auth and published services. All status transitions enforce ownership and time rules, and saved prices survive listing edits. |
| 7 · `feature/07-reviews-admin` | Implement verified-by-booking reviews. Add admin dashboard, category management and moderation. | Booking completion and admin gate. Only eligible customers review, and public pages reflect moderation. |
| 8 · `feature/08-notifications` | Connect booking changes to queued notifications. Verify worker and mail operation. | Stable transitions. Successful changes enqueue expected mail after commit to the correct recipient. |
| 9 · `feature/09-final-qa` | Add GitHub Actions, complete test coverage and perform responsive/accessibility QA. Update setup and progress documentation. | All MVP flows. Fresh checkout can install, migrate, seed, build and test. The full workflow passes from registration through review, and admin and email flows are verified. |

## Decisions to reconcile during course review

The course references were unavailable, so the course position on these topics is unknown. Project proposals are recorded in the requirements and architecture documents.

| Decision | Proposed default |
| --- | --- |
| Submission requirements and mandatory tooling | Confirm any rubric, prescribed database or starter kit, deployment target and required deliverables from the materials before implementation. |
| Booking lifecycle and time | Accept pending before start, reject pending at any time, cancel before start, complete at or after start. Use Europe/Madrid for input and UTC storage. |
| Pricing | Single EUR price per service, stored in cents and snapshotted at request time. |
| Listing removal and catalogue data | One optional cover image per service, unpublish to preserve historical records and restrict deletion of categories in use. |
| Admin scope and review moderation | Manage categories, unpublish listings and hide or restore review visibility. Provision admin access through a trusted operator. |
| Account and email scope | Native session auth with Blade forms. Booking milestone emails are required. Password reset screens and email verification are optional pending review. |
| Deployment | SQLite is the verified local baseline. Confirm production database, durable image storage, mail transport and hosting before deployment. |
