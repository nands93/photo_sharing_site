# Architecture

This document describes the high‑level architecture, runtime topology, request flows, and data boundaries of Camagru.

## 1) Topology (Docker)

- NGINX (reverse proxy and static assets)
  - Config in [server/nginx/](../server/nginx/)
  - Serves PHP via FastCGI to PHP-FPM
- PHP-FPM (application runtime)
  - App code mounted from [client/](../client/)
- MariaDB (database)
  - Initialized by [init.sql](../init.sql)
- phpMyAdmin (developer tool; optional)
- Orchestration via [docker-compose.yml](../docker-compose.yml) and [Makefile](../Makefile)

Networking: containers share a Docker network; PHP connects to DB via `DB_SERVER` and `DB_PORT` from `.env`.

## 2) Application Layout

- Public entry points (PHP): [client/](../client/)
  - Home/gallery: [client/index.php](../client/index.php)
  - Auth: [client/signup.php](../client/signup.php), [client/login.php](../client/login.php), [client/logout.php](../client/logout.php), [client/confirm.php](../client/confirm.php)
  - Password reset: [client/forgot_password.php](../client/forgot_password.php), [client/reset_password.php](../client/reset_password.php)
  - Profile: [client/profile.php](../client/profile.php), [client/edit_profile.php](../client/edit_profile.php)
  - Gallery interactions (endpoints): [client/likes.php](../client/likes.php), [client/comments.php](../client/comments.php)
  - Image pipeline: [client/post_photo.php](../client/post_photo.php), [client/photo_edit.php](../client/photo_edit.php), [client/save_image.php](../client/save_image.php), [client/toggle_photo_status.php](../client/toggle_photo_status.php), [client/delete_photo.php](../client/delete_photo.php)
- Frontend JS: [client/includes/js/gallery.js](../client/includes/js/gallery.js)
- Assets:
  - Stickers/overlays: [client/images/stickers/](../client/images/stickers)
  - User uploads: [client/uploads/](../client/uploads)
- Shared includes (HTML/PHP): [client/includes/](../client/includes)

## 3) Request Flow

1. Browser requests a page (e.g., [client/index.php](../client/index.php)) through NGINX.
2. NGINX forwards `.php` requests to PHP-FPM.
3. PHP scripts:
   - Validate CSRF tokens for state‑changing requests.
   - Authenticate session for protected routes.
   - Interact with MariaDB using prepared statements.
4. Frontend enhancements:
   - [client/includes/js/gallery.js](../client/includes/js/gallery.js) progressively enhances likes and comments with AJAX calls to [client/likes.php](../client/likes.php) and [client/comments.php](../client/comments.php).
5. Email delivery:
   - Outbound mail sent via Gmail SMTP using PHPMailer in [client/email.php](../client/email.php), authenticated with `GMAIL_EMAIL` and `GMAIL_APP_PASSWORD` from `.env` (account verification, password reset, and comment notifications if enabled).

## 4) Data Model (high level)

Note: Exact schema is created in [init.sql](../init.sql).

- users:
  - id, username, email, password_hash
  - email_verified (boolean), notify_comments (boolean)
  - reset_password_token, reset_password_expires
  - created_at, last_login
- user_photos:
  - id, user_id, path, is_public, was_posted, created_at
- comments:
  - id, photo_id, user_id, username, comment_text, ip_address, user_agent, created_at
- likes:
  - id, photo_id, user_id, created_at

## 5) Security

- Authentication: server‑side sessions; session ID rotation on login
- Passwords: `password_hash` + `password_verify`
- SQLi: prepared statements only
- XSS: escape all dynamic output; sanitize input
- CSRF: tokens embedded in forms and exposed to JS via meta tags; validated on POST endpoints
- Rate limiting: applied to sensitive endpoints (e.g., login)
- File uploads: server‑side validation (MIME/size), storage under [client/uploads/](../client/uploads)

## 6) Image Processing

- Upload/webcam flow: [client/photo_edit.php](../client/photo_edit.php) -> [client/save_image.php](../client/save_image.php)
- Overlays: sticker assets from [client/images/stickers/](../client/images/stickers)
- Server‑side composition happens in PHP, producing a final image stored in [client/uploads/](../client/uploads)

## 7) Gallery Interactions

- Likes: POST to [client/likes.php](../client/likes.php) with CSRF token; optimistic UI handled by [client/includes/js/gallery.js](../client/includes/js/gallery.js)
- Comments: POST to [client/comments.php](../client/comments.php) with CSRF token; after insert, the photo’s author can be notified via [client/email.php](../client/email.php) if notifications are enabled

## 8) Configuration

- Environment: `.env` (DB credentials, Gmail SMTP credentials)
- Make targets encapsulate lifecycle:
  - Up: `make` or `make all`
  - Rebuild: `make re`
  - Stop: `make down`
  - Clean: `make clean` / `make fclean`
