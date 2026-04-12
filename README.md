# PDL_Helpdesk — Installation & Setup Guide
## Pantex Dress Ltd. Internal IT Helpdesk System

---

## 1. SYSTEM REQUIREMENTS

| Component   | Minimum                    |
|-------------|----------------------------|
| Web Server  | Apache 2.4+ (XAMPP)        |
| PHP         | 7.4+ (8.1+ recommended)    |
| MySQL       | 5.7+ / MariaDB 10.4+       |
| Browser     | Chrome 90+, Edge 90+, Firefox 88+ |

---

## 2. INSTALLATION STEPS

### Step 1 — Copy project files

Place the `PDL_Helpdesk` folder into your XAMPP web root:

```
C:\xampp\htdocs\PDL_Helpdesk\
```

The final folder structure should be:

```
htdocs/
└── PDL_Helpdesk/
    ├── config/
    ├── core/
    ├── modules/
    ├── shared/
    ├── static/
    ├── uploads/
    ├── logs/
    ├── index.php
    └── .htaccess
```

---

### Step 2 — Enable Apache mod_rewrite

Open XAMPP Control Panel → Apache → Config → httpd.conf

Find and uncomment (remove the `#`):
```
LoadModule rewrite_module modules/mod_rewrite.so
```

Also find the `<Directory "C:/xampp/htdocs">` block and change:
```
AllowOverride None
```
to:
```
AllowOverride All
```

Restart Apache.

---

### Step 3 — Create the database

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click **New** in the left sidebar
3. Name it `pdl_helpdesk`, collation: `utf8mb4_unicode_ci`
4. Click **Create**
5. Select the `pdl_helpdesk` database
6. Click the **Import** tab
7. Choose file: `PDL_Helpdesk/config/schema.sql`
8. Click **Go**

All tables will be created automatically.

---

### Step 4 — Configure database credentials

Open `config/database.php` and update:

```php
private const DB_HOST = '127.0.0.1';   // usually 127.0.0.1
private const DB_PORT = '3306';         // MySQL default
private const DB_NAME = 'pdl_helpdesk';
private const DB_USER = 'root';         // your MySQL username
private const DB_PASS = '';             // your MySQL password (empty for XAMPP default)
```

---

### Step 5 — Configure base URL

Open `config/config.php` and update `BASE_URL`:

```php
// For local development:
define('BASE_URL', 'http://localhost/PDL_Helpdesk/');

// For LAN access (replace with your server's IP):
define('BASE_URL', 'http://192.168.0.160/PDL_Helpdesk/');
```

**Important:** Include the trailing slash.

---

### Step 6 — Set folder permissions

Make sure these folders are writable by Apache:

```
PDL_Helpdesk/uploads/
PDL_Helpdesk/logs/
```

On Windows (XAMPP), this is usually automatic.

On Linux:
```bash
chmod -R 755 /var/www/html/PDL_Helpdesk/
chmod -R 777 /var/www/html/PDL_Helpdesk/uploads/
chmod -R 777 /var/www/html/PDL_Helpdesk/logs/
```

---

### Step 7 — First Login

Open your browser and go to:
```
http://localhost/PDL_Helpdesk/
```
or on LAN:
```
http://192.168.0.160/PDL_Helpdesk/
```

Login with the default Super Admin account:

| Field    | Value              |
|----------|--------------------|
| Username | `superadmin`       |
| Password | `Admin@PDL2024`    |

> ⚠️ **Change this password immediately after first login.**

---

## 3. CREATING YOUR FIRST USERS

After logging in as Super Admin:

1. Go to **Users** in the sidebar
2. Click **Add User**
3. Create accounts for:
   - IT staff (Role: IT, Department: IT)
   - MIS staff (Role: MIS, Department: MIS)
   - Normal users (Role: Normal User, Department: General)
   - Admin(s) (Role: Admin, Department: General)

---

## 4. CONFIGURATION REFERENCE

### `config/config.php`

| Constant              | Default        | Description                          |
|-----------------------|----------------|--------------------------------------|
| `BASE_URL`            | (set by you)   | Full URL to the app with trailing /  |
| `APP_ENV`             | `production`   | `development` shows PHP errors       |
| `SESSION_LIFETIME`    | `28800`        | Session timeout in seconds (8 hours) |
| `MAX_FILE_SIZE`       | `10485760`     | 10MB max upload size                 |
| `MAX_FILES_PER_UPLOAD`| `5`            | Max files per ticket                 |
| `TICKETS_PER_PAGE`    | `25`           | Pagination size for ticket list      |
| `TICKET_PREFIX`       | `PDL`          | Prefix for ticket codes (PDL-000001) |

### `config/database.php`

Update `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` for your MySQL environment.

---

## 5. USER ROLES REFERENCE

| Role         | Description                                               |
|--------------|-----------------------------------------------------------|
| `normal_user`| Creates and tracks own tickets                            |
| `it`         | Manages IT department ticket queue                        |
| `mis`        | Manages MIS department ticket queue                       |
| `admin`      | Full access: all tickets, users, reports, audit logs      |
| `super_admin`| All admin rights; cannot be deactivated or edited by others |

---

## 6. TICKET WORKFLOW

```
[Created]  →  Open
              ↓ IT/MIS picks up
           In Progress
              ↓ Issue resolved
           Solved
              ↓ Admin closes
           Closed
```

- Tickets belong to **departments** (IT, MIS, CLICK), not individuals.
- Any IT user can work on any IT ticket.
- Transfer is available when a ticket needs to move to another department.
- Normal users **cannot reopen** tickets but can comment.

---

## 7. ADDING A NEW MODULE (For Developers)

To add a future module (e.g., Knowledge Base):

1. Create folder: `modules/knowledge_base/`
2. Add subfolders: `controllers/`, `models/`, `views/`
3. Create controller: `modules/knowledge_base/controllers/KBController.php`
   - Extend `Controller`
4. Create model: `modules/knowledge_base/models/KBModel.php`
   - Extend `Model`
5. Register routes in `core/Router.php`:
   ```php
   'kb'        => ['modules/knowledge_base/controllers/KBController.php', 'KBController', 'index'],
   'kb/article'=> ['modules/knowledge_base/controllers/KBController.php', 'KBController', 'article'],
   ```
6. Add permissions in `core/RBAC.php`:
   ```php
   'kb.view'   => ['normal_user','it','mis','admin','super_admin'],
   'kb.create' => ['admin','super_admin'],
   ```
7. Add sidebar link in `shared/components/sidebar.php`

---

## 8. SECURITY CHECKLIST

Before going live on your network:

- [ ] Change default `superadmin` password
- [ ] Set a strong MySQL password (not blank)
- [ ] Set `APP_ENV` to `production` in `config.php`
- [ ] Ensure `logs/` is not web-accessible (`.htaccess` handles this)
- [ ] Ensure `config/` PHP files are not web-accessible (`.htaccess` handles this)
- [ ] Set PHP `upload_max_filesize = 10M` and `post_max_size = 12M` in php.ini

---

## 9. FREQUENTLY ASKED QUESTIONS

**Q: I get a 404 for every page except the root.**
A: mod_rewrite is not enabled. See Step 2 above.

**Q: File uploads fail.**
A: Check that `uploads/tickets/` exists and is writable. Also verify PHP upload limits in `php.ini`.

**Q: Charts don't appear.**
A: Chart.js is loaded from CDN. Ensure the server has internet access, or download Chart.js and host it in `static/js/`.

**Q: I want to use HTTPS.**
A: Set `secure => true` in `core/Auth.php` session cookie params and update `BASE_URL` to `https://`.

**Q: How do I add PDF export?**
A: Install TCPDF or mPDF via Composer, then extend `ReportController::export()` with the `pdf` format case.

---

## 10. FILE STRUCTURE OVERVIEW

```
PDL_Helpdesk/
│
├── config/
│   ├── config.php          ← App constants & settings
│   ├── database.php        ← PDO singleton connection
│   └── schema.sql          ← Full database schema + seed data
│
├── core/
│   ├── Auth.php            ← Session, login, CSRF, guards
│   ├── RBAC.php            ← Role-based permission registry
│   ├── Model.php           ← Base model with query helpers
│   ├── Controller.php      ← Base controller: render, redirect, json
│   ├── Router.php          ← URL → controller dispatcher
│   ├── Notification.php    ← Event-based notification service
│   └── AuditLog.php        ← Immutable event logger
│
├── modules/
│   ├── auth/               ← Login, logout
│   ├── dashboard/          ← 3 role-specific dashboards + notifications
│   ├── tickets/            ← Full ticket CRUD, comments, uploads, transfers
│   ├── users/              ← Admin user management
│   ├── reports/            ← Analytics, charts, CSV export
│   └── audit/              ← Filterable audit log viewer
│
├── shared/
│   ├── components/
│   │   ├── sidebar.php     ← Collapsible navigation
│   │   ├── navbar.php      ← Top bar, dark mode, notifications
│   │   └── notification_panel.php
│   └── layouts/
│       ├── main_layout.php ← App shell with AlpineJS + Chart.js
│       └── auth_layout.php ← Minimal wrapper for login page
│
├── static/
│   └── js/app.js           ← Global JS: dark mode, utilities
│
├── uploads/
│   └── tickets/            ← Uploaded screenshots (hashed filenames)
│
├── logs/                   ← PHP error logs (web-inaccessible)
├── index.php               ← Front controller (all requests here)
└── .htaccess               ← Routing + security rules
```

---

## 11. SUPPORT

For issues or questions about this system, contact the developer or refer to the inline code comments. Every file contains a docblock explaining its purpose and every complex section is commented.

---

*PDL_Helpdesk v1.0.0 · Pantex Dress Ltd. · Internal Use Only*
