# 📸 Photo-sharing site

This project is a full‑stack image sharing app. Users can sign up, verify via email, log in, post photos, apply stickers, like, and comment. The stack focuses on security, responsiveness, and Dockerized development.

---

## ✨ Features

### 🔐 User
- Sign up with email verification ([client/signup.php](client/signup.php), [client/confirm.php](client/confirm.php))
- Secure login and logout ([client/login.php](client/login.php), [client/logout.php](client/logout.php))
- Password reset via email ([client/forgot_password.php](client/forgot_password.php), [client/reset_password.php](client/reset_password.php), [client/email.php](client/email.php))
- Edit profile (username, email, password) ([client/edit_profile.php](client/edit_profile.php), [client/profile.php](client/profile.php))
- Email notification preference (stored and respected; default: enabled)

### 🖼️ Gallery
- Public gallery of all users’ photos ([client/index.php](client/index.php))
- Likes and comments for logged‑in users ([client/likes.php](client/likes.php), [client/comments.php](client/comments.php))
- Client-side interactions via JS ([client/includes/js/gallery.js](client/includes/js/gallery.js))
- Email notification to the author on new comments ([client/email.php](client/email.php))
- Pagination (planned; minimum 5 per page)

### 🎥 Image Editor
- Photo upload as an alternative to webcam ([client/post_photo.php](client/post_photo.php))
- Server‑side processing in PHP ([client/save_image.php](client/save_image.php))
- Stickers/overlays assets ([client/images/stickers](client/images/stickers))
- Edit and manage photos ([client/photo_edit.php](client/photo_edit.php), [client/toggle_photo_status.php](client/toggle_photo_status.php), [client/delete_photo.php](client/delete_photo.php))
- Webcam live preview and capture (planned)

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

- Password hashing with `password_hash`
- SQL Injection protection (prepared statements)
- Output escaping to prevent XSS
- CSRF tokens on forms and AJAX
- Full client and server-side validation

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

### 🔧 Environment (.env)

Example:
```bash
MYSQL_ROOT_PASSWORD=your_root_password
MYSQL_DATABASE=camagru
MYSQL_USER=username
MYSQL_PASSWORD=your_password
PMA_USER=username
PMA_PASSWORD=your_password
PMA_ARBITRARY=1
DB_PORT=3306
DB_SERVER=mariadb
SENDGRID_API_KEY=your_sendgrid_api_key
```

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

## ✅ Done
- Dockerized stack (NGINX, MariaDB, phpMyAdmin, PHP-FPM)
- .env configuration and database bootstrap
- PHP <-> DB connection
- Core pages: home, signup, login, confirm, logout
- Email service integration ([client/email.php](client/email.php))
- Full validation (frontend and backend)
- Password hashing, SQLi prevention, XSS escaping, CSRF tokens
- Password reset flow (request + token + email + reset pages)
- Profile editing basics (username, email, password)
- Public gallery with likes and comments (for logged‑in users)

## 🟡 In Progress
- Email notification preference UI refinements
- Robust pagination in the gallery

## 🔲 Next
- Webcam preview and capture
- Minimum 5 items per page pagination and/or infinite scroll
- Responsive layout polish and cross‑browser checks

---

## 📄 Architecture

See [ARCHITETURE.md](ARCHITETURE.md) for container topology, request flow, data model, and security notes.

---