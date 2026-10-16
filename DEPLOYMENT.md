# eMunicipal — Production Deployment Guide

> **Biringan City Administration — Academic Demonstration Project**
> This system is fictional and for demonstration purposes only.

---

## 1. Before you deploy

Complete these steps **locally** before uploading anything:

1. Ensure no SQL dump files exist in `storage/` — move them offline.
2. Ensure no debug/report files exist in the project root.
3. Run `composer install --no-dev --optimize-autoloader` to rebuild `vendor/`.
4. Run all test suites with MySQL running and confirm they pass.
5. Verify `git status` shows no staged secrets.

---

## 2. Hosting requirements

| Requirement         | Value                        |
|---------------------|------------------------------|
| PHP version         | 8.0 or higher (8.1–8.2 recommended) |
| PHP extensions      | pdo_mysql, fileinfo, mbstring, openssl, json, zip |
| Database            | MySQL 5.7+ or MariaDB 10.3+  |
| Web server          | Apache 2.4+ with mod_rewrite |
| Document root       | Must point to `public/`       |
| Writable paths      | `storage/logs/`, `storage/uploads/` |

---

## 3. Document root configuration

**Critical:** The hosting provider must set the domain's document root to the
`public/` subdirectory, not the project root. This automatically places
`storage/`, `vendor/`, and `.env` above the web root and out of reach of HTTP.

```
Domain document root: /home/youraccount/emunicipal/public
```

The root `.htaccess` is only needed for XAMPP subdirectory setups
(`localhost/eMunicipal/`). On standard shared hosting with `public/` as the
document root, only `public/.htaccess` applies.

---

## 4. Production `.env` configuration

Create a `.env` file on the production server. **Never commit this file.**
Copy `.env.example` and fill in every value listed below.

### Required values

```dotenv
# Application
APP_NAME=eMunicipal
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_HTTPS=true

# Database — use the credentials from your hosting control panel
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_strong_database_password

# Resident notification email (transactional SMTP — NOT Mailtrap sandbox)
# Sign up for Mailtrap sending, SendGrid, Brevo, or similar
MAIL_HOST=smtp.your-transactional-provider.com
MAIL_PORT=587
MAIL_USERNAME=your_transactional_username
MAIL_PASSWORD=your_transactional_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME=eMunicipal

# Contact form email (Gmail App Password or other SMTP)
# Rotate the Gmail App Password — do not reuse the development one
CONTACT_OWNER_EMAIL=your@email.com
CONTACT_SMTP_HOST=smtp.gmail.com
CONTACT_SMTP_PORT=587
CONTACT_SMTP_USERNAME=youremail@gmail.com
CONTACT_SMTP_PASSWORD=your_new_gmail_app_password
CONTACT_SMTP_FROM_ADDRESS=youremail@gmail.com
CONTACT_SMTP_FROM_NAME=eMunicipal
CONTACT_SMTP_ENCRYPTION=tls

# Session and uploads
SESSION_LIFETIME=30
UPLOAD_MAX_SIZE_MB=5
UPLOAD_PATH=storage/uploads
```

### Key differences from development

| Variable      | Development value         | Production value                  |
|---------------|---------------------------|-----------------------------------|
| `APP_ENV`     | `development`             | `production`                      |
| `APP_DEBUG`   | `true`                    | `false`                           |
| `APP_URL`     | `http://localhost/eMunicipal` | `https://yourdomain.com`      |
| `APP_HTTPS`   | `false`                   | `true`                            |
| `DB_PASSWORD` | empty (XAMPP default)     | strong password from host panel   |
| `MAIL_HOST`   | `sandbox.smtp.mailtrap.io`| real transactional SMTP           |

---

## 5. Credential rotation before go-live

Before connecting the domain, revoke and regenerate the following:

- **Mailtrap sandbox credentials** — log into Mailtrap, rotate the SMTP credentials
  in the inbox settings. Update `MAIL_USERNAME` and `MAIL_PASSWORD` in production `.env`.
- **Gmail App Password** — log into your Google account › Security › 2-Step Verification
  › App Passwords. Delete the old app password and generate a new one.
  Update `CONTACT_SMTP_PASSWORD` in production `.env`.

The development `.env` credentials must not be used on the production server.

---

## 6. Database setup

```bash
# On the hosting server (phpMyAdmin or MySQL CLI):
# 1. Create the database
CREATE DATABASE `emunicipal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 2. Import the schema (idempotent — uses CREATE IF NOT EXISTS)
SOURCE database/schema.sql;

# 3. Import the seeds (run once on a fresh database only)
SOURCE database/seeds.sql;
```

Do **not** import the development SQL dump files — use the clean schema + seeds
for a fresh production database.

---

## 7. File permissions

```bash
chmod 755 storage/
chmod 755 storage/logs/
chmod 755 storage/uploads/
chown -R www-data:www-data storage/   # adjust user for your host
```

---

## 8. Install Composer dependencies on server

```bash
cd /path/to/emunicipal
composer install --no-dev --optimize-autoloader
```

---

## 9. HTTPS redirect

After SSL is confirmed working, add this at the top of `public/.htaccess`
inside the `<IfModule mod_rewrite.c>` block, **before** the existing rules:

```apache
# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
```

---

## 10. Post-deployment smoke tests

1. Landing page loads at `https://yourdomain.com/`
2. Guest sees Login + Register buttons
3. Register a test resident (fictional data)
4. Login as resident → resident dashboard loads
5. Submit a test document request
6. Logout → navbar reverts to guest state
7. Login as staff → staff dashboard loads
8. Login as admin → admin dashboard loads
9. Policy pages load: `/policy/privacy`, `/policy/terms`, `/policy/moderation`, `/policy/copyright`
10. Registration without T&C checkbox fails with correct error
11. Request `https://yourdomain.com/storage/some.sql` → returns 403 (never a download)
12. Security headers present: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Content-Security-Policy`
13. No mixed-content warnings (all assets load over HTTPS)

---

## 11. Database backup plan

- Take a manual backup via phpMyAdmin immediately after confirming the site works.
- Enable automated daily backups in the hosting control panel.
- Store backups outside the web root and never in `storage/`.
