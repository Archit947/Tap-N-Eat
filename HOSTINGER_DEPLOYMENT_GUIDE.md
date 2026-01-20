# Hostinger Deployment Guide - QSR System

## Overview
You're hosting at: `qsr.catalystsolutions.eco`

---

## Step 1: Build Your React Frontend

### 1.1 Generate Production Build

In your local machine, run:
```bash
cd Myqsr
npm run build
```

This creates a `dist/` folder with all static files ready for production.

### 1.2 What You Get
```
dist/
├── index.html
├── assets/
│   ├── *.js
│   ├── *.css
│   └── ...
└── (other static files)
```

---

## Step 2: Directory Structure on Hostinger (all inside Tap-N-Eat)

### Create This Directory Structure:

```
public_html/
└── qsr/
    └── Tap-N-Eat/
        ├── frontend/               ← React build files (Vite dist)
        │   ├── index.html
        │   └── assets/
        ├── api/                    ← PHP endpoints
        │   ├── employees.php
        │   ├── rfid-scan.php
        │   ├── transactions.php
        │   ├── wallet-recharge.php
        │   ├── print-thermal.php
        │   └── ...
        ├── admin/                  ← Admin panel
        │   ├── admin.html
        │   └── admin.js
        ├── config/                 ← Shared config
        │   ├── database.php
        │   └── env_loader.php
        ├── uploads/                ← (optional) create if your app saves files
        ├── .env                    ← Environment file (placed here)
        ├── .htaccess               ← SPA + protection rules
        └── (optional assets/images)
```

---

## Step 3: Upload to Hostinger

### 3.1 Using File Manager (Easy Way)

1. Login to Hostinger Control Panel → File Manager.
2. Navigate to `public_html/` and create `qsr`, then inside it create `Tap-N-Eat`.
3. Inside `Tap-N-Eat` create folders: `frontend`, `api`, `admin`, `config`. Create `uploads` only if your app needs to store uploaded files.

### 3.2 Upload Files

- Frontend: upload **contents of `dist/`** to `public_html/qsr/Tap-N-Eat/frontend/` (so `index.html` and `assets/` live there).
- API: upload `backend/api/` to `public_html/qsr/Tap-N-Eat/api/`.
- Admin: upload `backend/admin/` to `public_html/qsr/Tap-N-Eat/admin/`.
- Config: upload `backend/config/` to `public_html/qsr/Tap-N-Eat/config/`.
- Env: place `.env` at `public_html/qsr/Tap-N-Eat/.env`.
- Uploads (optional): create `uploads/` if needed and make it writable.

### 3.3 Using FTP (Alternative)

Same as above, just create `qsr/Tap-N-Eat/` and upload into its subfolders.

---

## Step 4: .htaccess for routing and protection (no custom index.php needed)

Create `public_html/qsr/Tap-N-Eat/.htaccess` with:

```apache
Options -Indexes
RewriteEngine On

# Block sensitive files/folders
RewriteRule ^(config|\.env|.*\.sql)$ - [F,L]

# Allow API/Admin/uploads/frontend assets and real files/dirs through
RewriteCond %{REQUEST_URI} !^/qsr/Tap-N-Eat/api/
RewriteCond %{REQUEST_URI} !^/qsr/Tap-N-Eat/admin/
RewriteCond %{REQUEST_URI} !^/qsr/Tap-N-Eat/uploads/
RewriteCond %{REQUEST_URI} !^/qsr/Tap-N-Eat/frontend/assets/
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# SPA fallback to frontend index
RewriteRule ^qsr/Tap-N-Eat/?$ /qsr/Tap-N-Eat/frontend/index.html [L]
RewriteRule ^qsr/Tap-N-Eat/(.*)$ /qsr/Tap-N-Eat/frontend/index.html [L]
```

---

## Step 5: Update Configuration Files

### 5.1 Update `.env` (place at `public_html/qsr/Tap-N-Eat/.env`)

```env
# Database Configuration (Update with Hostinger DB credentials)
DB_HOST=your-hostinger-db-host
DB_NAME=your_database_name
DB_USER=your_db_username
DB_PASSWORD=your_db_password

# Thermal Printer Configuration
PRINTER_IP=192.168.0.105
PRINTER_PORT=9100

# Application Settings
APP_ENV=production
APP_DEBUG=false
```

Get DB credentials from Hostinger:
- Login to Hostinger Control Panel
- Go to Databases section
- Copy connection details

### 5.2 Update React API URL and base

- `vite.config.js` should have `base: '/qsr/Tap-N-Eat/frontend/'` (already set).
- Set API base URL in your React code (e.g., `src/components/AdminDashboard.jsx`) to `/qsr/Tap-N-Eat/api/` or the full domain `https://qsr.catalystsolutions.eco/qsr/Tap-N-Eat/api/` before running `npm run build`.
    - Example: `const API_BASE_URL = '/qsr/Tap-N-Eat/api/';`
    - Rebuild, then upload the fresh `dist/` to `frontend/`.

---

## Step 6: Set File Permissions

In Hostinger File Manager:

1. Right-click folders → Change Permissions.
2. Set: folders `755`, files `644`.
3. Apply to: `public_html/qsr/Tap-N-Eat/` (including `frontend/`, `api/`, `admin/`, `config/`, and `uploads/` if you created it).

---

## Step 7: Verify Installation

### 7.1 Test Frontend
Open: `https://qsr.catalystsolutions.eco/qsr/Tap-N-Eat/frontend/` (or just `/qsr/Tap-N-Eat/` if you set a redirect). You should see the app load; check DevTools Network that `assets/*.js` are 200.

### 7.2 Test Backend API
Open: `https://qsr.catalystsolutions.eco/qsr/Tap-N-Eat/api/employees.php` — should return JSON.

### 7.3 Test Database Connection
Create `public_html/qsr/Tap-N-Eat/test-connection.php`:

```php
<?php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        echo json_encode(['status' => 'Connected', 'message' => 'Database connection successful']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'Error', 'message' => $e->getMessage()]);
}
?>
```

Visit: `https://qsr.catalystsolutions.eco/qsr/Tap-N-Eat/test-connection.php`

---

## Step 8: Troubleshooting

### Issue: "404 Not Found" for API endpoints

**Solution:**
1. Check `.htaccess` exists in `public_html/qsr/`
2. Verify `index.php` exists in `public_html/qsr/`
3. Check file permissions (755 for folders, 644 for files)
4. Enable mod_rewrite in Hostinger (usually default)

### Issue: Database connection fails

**Solution:**
1. Verify database credentials in `.env`
2. Check database name and user are correct
3. Ensure user has permissions to access database
4. Test connection with `test-connection.php`

### Issue: Frontend shows blank page

**Solution:**
1. Open DevTools → Console/Network to see failing URLs.
2. If `assets/*.js` 404, rebuild locally after confirming `vite.config.js` base is `/qsr/Tap-N-Eat/frontend/`, then re-upload `dist/` into `frontend/`.
3. Ensure `.htaccess` is at `public_html/qsr/Tap-N-Eat/.htaccess`.
4. Confirm API base in the React build points to `/qsr/Tap-N-Eat/api/`.

### Issue: CORS errors

**Solution:**
1. Verify CORS headers in `index.php` (already set)
2. Check `.htaccess` is allowing requests
3. Test with `curl -i https://qsr.catalystsolutions.eco/qsr/api/employees.php`

---

## Final URL Structure

Once deployed:

| URL | Purpose |
|-----|---------|
| `https://qsr.catalystsolutions.eco/qsr/Tap-N-Eat/frontend/` | Frontend (React App) |
| `https://qsr.catalystsolutions.eco/qsr/Tap-N-Eat/api/employees.php` | API Endpoint |
| `https://qsr.catalystsolutions.eco/qsr/Tap-N-Eat/api/rfid-scan.php` | RFID Scanning |
| `https://qsr.catalystsolutions.eco/qsr/Tap-N-Eat/.env` | ⚠️ Should be blocked by .htaccess |

---

## Security Notes

### Important:

1. **Protect `.env` file** - Add to `.htaccess`:
```apache
<FilesMatch "\.env$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

2. **Enable HTTPS** - Should be automatic on Hostinger
3. **Set strong database password**
4. **Keep sensitive files out of public folder**
5. **Disable debug mode** - Set `APP_DEBUG=false` in `.env`

---

## Deployment Checklist

- [ ] Build React app locally (`npm run build`) with `base: '/qsr/Tap-N-Eat/frontend/'`
- [ ] Create directory structure under `public_html/qsr/Tap-N-Eat/`
- [ ] Upload `dist/` contents to `public_html/qsr/Tap-N-Eat/frontend/`
- [ ] Upload `backend/api` to `public_html/qsr/Tap-N-Eat/api/`
- [ ] Upload `backend/admin` to `public_html/qsr/Tap-N-Eat/admin/`
- [ ] Upload `backend/config` to `public_html/qsr/Tap-N-Eat/config/`
- [ ] Place `.env` at `public_html/qsr/Tap-N-Eat/.env`
- [ ] Upload `.htaccess` to `public_html/qsr/Tap-N-Eat/`
- [ ] Update API base in React code to `/qsr/Tap-N-Eat/api/` and rebuild
- [ ] Create `uploads/` only if your app needs it; set file permissions (folders 755, files 644)
- [ ] Test frontend: `https://qsr.catalystsolutions.eco/qsr/Tap-N-Eat/frontend/`
- [ ] Test API: `https://qsr.catalystsolutions.eco/qsr/Tap-N-Eat/api/employees.php`
- [ ] Test database: `https://qsr.catalystsolutions.eco/qsr/Tap-N-Eat/test-connection.php`
- [ ] Protect `.env` with `.htaccess`; remove `test-connection.php` after testing

---

## Need Help?

If you encounter issues:

1. **Check Hostinger Control Panel** → **Logs** section
2. **Enable detailed PHP errors** temporarily (for debugging)
3. **Verify all file permissions**
4. **Test each component separately**

---

**Last Updated:** January 5, 2026  
**Status:** Ready for Hostinger deployment
