# Architecture

This document describes the high‑level architecture, runtime topology, request flows, and data boundaries of Camagru.

## 1) Topology (Docker)

- NGINX (reverse proxy and static assets)
  - Config in [server/nginx/](server/nginx/)
  - Serves PHP via FastCGI to PHP-FPM
- PHP-FPM (application runtime)
  - App code mounted from [client/](client/)
- MariaDB (database)
  - Initialized by [init.sql](init.sql)
- phpMyAdmin (developer tool; optional)
- Orchestration via [docker-compose.yml](docker-compose.yml) and [Makefile](Makefile)

Networking: containers share a Docker network; PHP connects to DB via `DB_SERVER` and `DB_PORT` from `.env`.

## 2) Application Layout

- Public entry points (PHP): [client/](client/)
  - Home/gallery: [client/index.php](client/index.php)
  - Auth: [client/signup.php](client/signup.php), [client/login.php](client/login.php), [client/logout.php](client/logout.php), [client/confirm.php](client/confirm.php)
  - Password reset: [client/forgot_password.php](client/forgot_password.php), [client/reset_password.php](client/reset_password.php)
  - Profile: [client/profile.php](client/profile.php), [client/edit_profile.php](client/edit_profile.php)
  - Gallery interactions (endpoints): [client/likes.php](client/likes.php), [client/comments.php](client/comments.php)
  - Image pipeline: [client/post_photo.php](client/post_photo.php), [client/photo_edit.php](client/photo_edit.php), [client/save_image.php](client/save_image.php), [client/toggle_photo_status.php](client/toggle_photo_status.php), [client/delete_photo.php](client/delete_photo.php)
- Frontend JS: [client/includes/js/gallery.js](client/includes/js/gallery.js)
- Assets:
  - Stickers/overlays: [client/images/stickers/](client/images/stickers)
  - User uploads: [client/uploads/](client/uploads)
- Shared includes (HTML/PHP): [client/includes/](client/includes)

## 3) Request Flow

1. Browser requests a page (e.g., [client/index.php](client/index.php)) through NGINX.
2. NGINX forwards `.php` requests to PHP-FPM.
3. PHP scripts:
   - Validate CSRF tokens for state‑changing requests.
   - Authenticate session for protected routes.
   - Interact with MariaDB using prepared statements.
4. Frontend enhancements:
   - [client/includes/js/gallery.js](client/includes/js/gallery.js) progressively enhances likes and comments with AJAX calls to [client/likes.php](client/likes.php) and [client/comments.php](client/comments.php).
5. Email delivery:
   - Outbound mail sent via SendGrid using `SENDGRID_API_KEY` in [client/email.php](client/email.php) (account verification, password reset, and comment notifications if enabled).

## 4) Data Model (high level)

- users: id, username, email, password_hash, email_verified, notify_on_comment, created_at, updated_at
- photos: id, user_id, path, is_public, created_at
- comments: id, photo_id, user_id, body, created_at
- likes: id, photo_id, user_id, created_at
- password_resets: user_id, token, expires_at, created_at

Note: Exact schema is created in [init.sql](init.sql).

## 5) Security

- Authentication: server‑side sessions.
- Passwords: `password_hash` + `password_verify`.
- SQLi: prepared statements only.
- XSS: escape all dynamic output; sanitize input.
- CSRF: tokens embedded in forms and exposed to JS via meta tags; validated on POST endpoints.
- File uploads: server‑side validation (MIME/size), storage under [client/uploads/](client/uploads).

## 6) Image Processing

- Upload flow: [client/post_photo.php](client/post_photo.php) -> [client/save_image.php](client/save_image.php).
- Overlays: sticker assets from [client/images/stickers/](client/images/stickers).
- Server‑side composition happens in PHP (GD/ImageMagick capable), producing a final image stored in [client/uploads/](client/uploads).

## 7) Gallery Interactions

- Likes: POST to [client/likes.php](client/likes.php) with CSRF token; optimistic UI handled by [client/includes/js/gallery.js](client/includes/js/gallery.js).
- Comments: POST to [client/comments.php](client/comments.php) with CSRF token; after insert, the photo’s author can be notified via [client/email.php](client/email.php) if notifications are enabled.

## 8) Configuration

- Environment: `.env` (DB credentials, service hostnames, SendGrid API key).
- Make targets encapsulate lifecycle:
  - Up: `make` or `make all`
  - Rebuild: `make re`
  - Stop: `make down`
  - Clean: `make clean` / `make fclean`

## 9) Known Gaps / Roadmap

- Image editor
  - Better sticker tooling (resize/rotate/drag), client‑side preview before upload
  - EXIF orientation handling for uploads; server‑side thumbnail generation

- Gallery UX
  - Sorting and filtering (by date, likes, author); keep classic pagination (no infinite scroll)
  - Lazy‑loading of thumbnails and consistent cropping/aspect ratio
  - Storage housekeeping: remove orphaned files on delete, disk quota safeguards

- Notifications
  - Granular preferences (separate toggles for likes vs. comments)
  - Unsubscribe link in emails and throttling for repeated notifications

- Anti‑abuse and security hardening
  - Rate limiting (login, signup, likes/comments) and CAPTCHA on signup/comment
  - SameSite/HttpOnly/Secure cookies; session fixation rotation

- Admin and moderation
  - Admin dashboard to moderate photos/comments, handle reports, and suspend users
  - Content reporting flow and audit logs

- Observability
  - Centralized structured logging, request IDs, and error tracking (e.g., Sentry)
  - Basic metrics and health/readiness endpoints for containers

- Testing and CI/CD
  - PHPUnit integration tests for DB and endpoints; JS unit tests
  - E2E tests (Playwright/Cypress) for critical flows (signup, post, like, comment)
  - GitHub Actions pipeline (lint, test, build) with test database

- Deployability
  - Production Docker Compose override, NGINX TLS, sample .env.production
  - DB migrations (split init.sql into versioned migrations)
  - Backup/restore scripts and retention policy

- Accessibility and internationalization
  - WCAG AA pass (focus states, ARIA labels, color contrast)
  - Locale switch (en, pt‑BR) for UI and emails

- Performance and caching
  - HTTP caching (ETag/Last‑Modified) for static assets and thumbnails
  - Gzip/Brotli at NGINX, keep‑alive tuning

- SEO and meta
  - Open Graph/Twitter cards, sitemap.xml, robots.txt
  - Canonical URLs and pagination rel links