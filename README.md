# 📸 Photo-sharing site

A full‑stack image sharing app. Users can sign up (with email verification), log in, post photos, apply stickers, like, and comment. The stack focuses on security, responsiveness, and Dockerized development.

Status: Completed

---

## ✨ Features

### 🔐 User
- Sign up with email verification ([client/signup.php](client/signup.php), [client/confirm.php](client/confirm.php))
- Secure login and logout ([client/login.php](client/login.php), [client/logout.php](client/logout.php))
- Password reset via email ([client/forgot_password.php](client/forgot_password.php), [client/reset_password.php](client/reset_password.php), [client/email.php](client/email.php))
- Edit profile (username, email, password) ([client/edit_profile.php](client/edit_profile.php), [client/profile.php](client/profile.php))
- Email notification preference (respected for comment notifications)

### 🖼️ Gallery
- Public gallery of all users’ photos ([client/index.php](client/index.php))
- Likes and comments for logged‑in users ([client/likes.php](client/likes.php), [client/comments.php](client/comments.php))
- Client-side interactions via JS ([client/includes/js/gallery.js](client/includes/js/gallery.js))
- Email notification to the author on new comments ([client/email.php](client/email.php))
- Pagination

### 🎥 Image Editor
- Photo upload and webcam capture ([client/photo_edit.php](client/photo_edit.php))
- Server‑side processing in PHP ([client/save_image.php](client/save_image.php))
- Stickers/overlays assets ([client/images/stickers](client/images/stickers))
- Post, toggle visibility, and delete photos ([client/post_photo.php](client/post_photo.php), [client/toggle_photo_status.php](client/toggle_photo_status.php), [client/delete_photo.php](client/delete_photo.php))

---

## 🧱 Tech Stack

- Frontend: HTML5, CSS3, vanilla JavaScript
- Backend: PHP (no external frameworks)
- Database: MariaDB
- Web server: NGINX
- Containerization: Docker & Docker Compose
- Config management: `.env`
- Dev workflow: `Makefile`

---

## 🛡️ Security

- Password hashing with `password_hash` / `password_verify`
- SQL Injection protection (prepared statements)
- Output escaping to prevent XSS
- CSRF tokens on forms and AJAX
- Server and client-side validation
- Basic rate limiting on sensitive endpoints

---

## 🛠️ How to Run

Requires Docker and Make.

- Start dev environment:
  - make
  - or explicitly: make all
- Rebuild (including images):
  - make re
- Stop containers:
  - make down
- Light clean (containers + unused images):
  - make clean
- Full clean (containers, images, networks, orphan volumes):
  - make fclean

App default URL: http://localhost:8080

---

## 🔧 Environment (.env)

Example:
```bash
# Database
MYSQL_ROOT_PASSWORD=your_root_password
MYSQL_DATABASE=camagru
MYSQL_USER=username
MYSQL_PASSWORD=your_password
DB_PORT=3306
DB_SERVER=mariadb

# phpMyAdmin
PMA_USER=username
PMA_PASSWORD=your_password
PMA_ARBITRARY=1

# Email (Gmail SMTP with App Password)
GMAIL_EMAIL=your_gmail_address@gmail.com
GMAIL_APP_PASSWORD=your_16_char_app_password
```

The application uses Gmail SMTP via PHPMailer in [client/email.php](client/email.php). Do not use your normal Gmail password; use an App Password.

---

## 📧 How to create a Gmail App Password

Prerequisites: You must have 2‑Step Verification enabled on your Google account.

Steps:
1. Go to https://myaccount.google.com/
2. Navigate to Security > “2‑Step Verification” and complete setup if not already enabled.
3. On the 2‑Step Verification page, scroll to “App passwords” and open it.
   - If you don’t see “App passwords”, ensure 2‑Step Verification is enabled and your account allows it.
4. In “Select app”, choose “Mail”. In “Select device”, choose your device or “Other (Custom name)” and set a label (e.g., “Camagru”).
5. Click Generate. Copy the 16‑character App Password.
6. Add it to your `.env`:
   - GMAIL_EMAIL=your_gmail_address@gmail.com
   - GMAIL_APP_PASSWORD=the_16_character_app_password
7. Restart the stack: make re

Keep the App Password secret. Treat it like any other credential.

---

## 🗂️ Project Structure

- App:
  - [client/index.php](client/index.php), pages in [client/](client/)
  - Frontend JS: [client/includes/js/gallery.js](client/includes/js/gallery.js)
  - Assets: [client/images/](client/images/) and [client/uploads/](client/uploads/)
  - Mailer: [client/email.php](client/email.php)
- Infrastructure:
  - Docker compose: [docker-compose.yml](docker-compose.yml)
  - Make targets: [Makefile](Makefile)
  - DB init: [init.sql](init.sql)
  - NGINX/PHP images: [server/nginx/](server/nginx/), [server/php/](server/php/)

---

## ✅ Completed

- Dockerized stack (NGINX, MariaDB, phpMyAdmin, PHP-FPM)
- `.env` configuration and database bootstrap
- PHP <-> DB connection
- Core pages: home, signup, login, confirm, logout
- Email delivery (verification, reset, comments) via Gmail SMTP ([client/email.php](client/email.php))
- Validation (frontend and backend), CSRF tokens, XSS escaping, SQLi prevention
- Password reset flow (request + token + email + reset pages)
- Profile management (username, email, password, notifications)
- Gallery with likes, comments, and pagination
- Photo editor with upload/webcam + stickers, and gallery management

---

## 📄 Architecture

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for container topology, request flow, data model, and security notes.

---