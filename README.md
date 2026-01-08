# Project Management Web Application

# NEW N8N STUFF
CONFIGURE IT, N8N HAS IT OWN MASSIVE DOCS I AM NOT PUTTING GUIDES HERE, SIMPLEST SELF HOST IS npm i -g n8n, def better if its docker, but i did it with npm too; my docker is in vm linux server and its already filled to the brim.

## Tech Stack

### Backend
- Laravel 11
- MySQL Database
- Laravel Sanctum (SPA Authentication)
- CORS configured for local development

### Frontend
- Nuxt 
- Vue  with Composition API
- Vuetify (Material Design)
- Pinia (State Management)

---

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 8.2+** (comes with XAMPP)
- **Composer** - [Download here](https://getcomposer.org/download/)
- **Node.js 18+** - [Download here](https://nodejs.org/)
- **XAMPP** (for MySQL and Apache)
- **mkcert** - [Installation guide](https://github.com/FiloSottile/mkcert#installation)

---

## HTTPS Setup (Using mkcert)

This project uses HTTPS for both frontend and backend. Follow these steps to set up SSL certificates.

### 1. Install mkcert

**Windows (using Chocolatey):**
```bash
choco install mkcert
```

**macOS (using Homebrew):**
```bash
brew install mkcert
brew install nss
```

**Linux:**
```bash
sudo apt install libnss3-tools
wget -O mkcert https://github.com/FiloSottile/mkcert/releases/download/v1.4.4/mkcert-v1.4.4-linux-amd64
chmod +x mkcert
sudo mv mkcert /usr/local/bin/
```

### 2. Install Local CA

```bash
mkcert -install
```

This installs a local Certificate Authority (CA) in your system trust store.

### 3. Generate Certificates for Frontend

Navigate to your frontend directory and generate certificates:

```bash
cd frontend_pm
mkcert localhost 127.0.0.1 ::1
```

This creates two files:
- `localhost+2.pem` (certificate)
- `localhost+2-key.pem` (private key)

Rename them for clarity:
```bash
# Windows (PowerShell)
Rename-Item -Path "localhost+2.pem" -NewName "localhost.pem"
Rename-Item -Path "localhost+2-key.pem" -NewName "localhost-key.pem"

# macOS/Linux
mv localhost+2.pem localhost.pem
mv localhost+2-key.pem localhost-key.pem
```

### 4. Generate Certificates for Backend

Navigate to XAMPP's Apache certs folder (create it if it doesn't exist):

```bash
# Windows
cd C:\xampp\apache
mkdir certs
cd certs

# macOS/Linux
cd /Applications/XAMPP/xamppfiles/apache
mkdir certs
cd certs
```

Generate certificates:
```bash
mkcert localhost 127.0.0.1 ::1
```

Rename them:
```bash
# Windows (PowerShell)
Rename-Item -Path "localhost+2.pem" -NewName "localhost.pem"
Rename-Item -Path "localhost+2-key.pem" -NewName "localhost-key.pem"

# macOS/Linux
mv localhost+2.pem localhost.pem
mv localhost+2-key.pem localhost-key.pem
```

### 5. Configure Apache for HTTPS

#### Step 5.1: Edit httpd.conf

Open `C:\xampp\apache\conf\httpd.conf` (or `/Applications/XAMPP/xamppfiles/etc/httpd.conf` on macOS)

Find the line that says:
```apache
Listen 80
```

Change it to:
```apache
Listen 8000
```

**Note:** The default is `80`. We're changing it to `8000` so Laravel serves on port 8000.

#### Step 5.2: Edit httpd-ssl.conf

Open `C:\xampp\apache\conf\extra\httpd-ssl.conf`

Add this VirtualHost configuration at the bottom of the file:

```apache
<VirtualHost *:8000>
    ServerName localhost
    
    # CHANGE THIS PATH to match your project location
    DocumentRoot "C:/xampp/htdocs/your-project-folder/backend_pm/public"
    
    <Directory "C:/xampp/htdocs/your-project-folder/backend_pm/public">
        AllowOverride All
        Require all granted
    </Directory>
    
    SSLEngine on
    SSLCertificateFile "C:/xampp/apache/certs/localhost.pem"
    SSLCertificateKeyFile "C:/xampp/apache/certs/localhost-key.pem"
</VirtualHost>
```

**Important:** Replace `your-project-folder` with your actual project folder name.

Example:
```apache
DocumentRoot "C:/xampp/htdocs/dashboard/Latihan-Magang-Project-Management-WebApp/backend_pm/public"
<Directory "C:/xampp/htdocs/dashboard/Latihan-Magang-Project-Management-WebApp/backend_pm/public">
```

**For macOS/Linux:** Use forward slashes:
```apache
DocumentRoot "/Applications/XAMPP/xamppfiles/htdocs/your-project-folder/backend_pm/public"
```

### 6. Restart Apache

Restart Apache from the XAMPP Control Panel or command line:

```bash
# XAMPP Control Panel: Click "Stop" then "Start" for Apache
```

---

## Backend Setup (Laravel)

### 1. Clone the Repository

```bash
git clone <your-repo-url>
cd <project-folder>
```

### 2. Navigate to Backend Directory

```bash
cd backend_pm
```

### 3. Install PHP Dependencies

```bash
composer install
```

**Note:** Sanctum is already included in Laravel 11 by default, so no additional installation is needed.

### 4. Configure Environment

Copy the `.env.example` file (or use your existing `.env`):

```bash
cp .env.example .env
```

Make sure your `.env` has these settings:

```env
APP_URL=https://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=backend_pm
DB_USERNAME=root
DB_PASSWORD=

FRONTEND_URL=https://localhost:3000
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
SESSION_DRIVER=database
SESSION_DOMAIN=localhost
SESSION_SAME_SITE=None

FILESYSTEM_DISK=public
```

**Important:** Note the `https://` in URLs!

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Create Database

1. Open **phpMyAdmin** (http://localhost/phpmyadmin)
2. Create a new database named `backend_pm`

### 7. Run Migrations

```bash
php artisan migrate
```

This will create all necessary tables (users, workspaces, boards, lists, cards, etc.).

### 8. Create Storage Link

This is required for file uploads (attachments):

```bash
php artisan storage:link
```

This creates a symbolic link from `public/storage` to `storage/app/public`.

### 9. Start Apache

The backend will be available at `https://localhost:8000` via Apache (XAMPP).

**Note:** You don't need to run `php artisan serve` because Apache is handling the server.

---

## Frontend Setup (Nuxt 3)

### 1. Navigate to Frontend Directory

Open a **new terminal** and navigate to the frontend folder:

```bash
cd frontend_pm
# or whatever your frontend folder is named
```

### 2. Install Node Dependencies

```bash
npm install
```

This will install:
- Nuxt 3
- Vuetify 3
- Pinia (state management)
- All other dependencies

### 3. Configure Environment

Create a `.env` file in the frontend directory:

```bash
# .env
NUXT_PUBLIC_API_BASE=https://localhost:8000/api
```

### 4. Configure Nuxt for HTTPS

Make sure your `nuxt.config.ts` includes the SSL configuration:

```typescript
export default defineNuxtConfig({
  devServer: {
    https: {
      key: './localhost-key.pem',
      cert: './localhost.pem',
    }
  },
  runtimeConfig: {
    public: {
      apiBase: 'https://localhost:8000/api'
    }
  }
})
```

### 5. Start the Frontend Server

```bash
npm run dev
```

The frontend will be available at `https://localhost:3000`

---

## Usage

1. **Open your browser** and go to `https://localhost:3000`
2. **Register a new account** or login
3. **Create a workspace** to get started
4. **Create boards** within your workspace
5. **Add lists and cards** to organize your tasks
6. **Set due dates** and view them in the calendar

---

## Project Structure

```
project-root/
├── backend_pm/              # Laravel Backend
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   └── ...
│   ├── database/
│   │   └── migrations/
│   ├── routes/
│   │   └── api.php
│   └── storage/
│       └── app/public/      # File uploads
│
└── frontend_pm/             # Nuxt Frontend
    ├── localhost.pem        # SSL certificate
    ├── localhost-key.pem    # SSL private key
    ├── components/
    ├── composables/
    ├── pages/
    ├── stores/
    └── types/
```

---

## Troubleshooting

### SSL Certificate Issues

**Issue: Browser shows "Your connection is not private"**
- Make sure you ran `mkcert -install` to install the local CA
- Restart your browser after installing mkcert
- In Chrome: Click "Advanced" → "Proceed to localhost (unsafe)" (it's safe, it's your own certificate)

**Issue: Certificate files not found**
- Make sure the `.pem` files are in the correct locations:
  - Frontend: `frontend_pm/localhost.pem` and `frontend_pm/localhost-key.pem`
  - Backend: `C:/xampp/apache/certs/localhost.pem` and `C:/xampp/apache/certs/localhost-key.pem`
- Check file paths in `nuxt.config.ts` and `httpd-ssl.conf`

### Backend Issues

**Issue: Apache won't start after changing httpd.conf**
- Check Apache error logs in `C:\xampp\apache\logs\error.log`
- Make sure port 8000 is not already in use
- Verify the `DocumentRoot` path exists and is correct

**Issue: "Target class [Controller] does not exist"**
- Run: `composer dump-autoload`

**Issue: "SQLSTATE[HY000] [1049] Unknown database"**
- Make sure you created the `backend_pm` database in phpMyAdmin
- Check your `.env` database credentials

**Issue: "The stream or file could not be opened"**
- Run: `php artisan cache:clear`
- Check folder permissions: `chmod -R 775 storage bootstrap/cache`

**Issue: Storage link not working**
- Delete existing link: `rm public/storage`
- Recreate: `php artisan storage:link`

### Frontend Issues

**Issue: "Cannot connect to backend"**
- Make sure Apache is running and serving Laravel on `https://localhost:8000`
- Check your `NUXT_PUBLIC_API_BASE` environment variable has `https://`
- Check CORS settings in `backend_pm/config/cors.php`

**Issue: "npm install" fails**
- Try: `npm install --legacy-peer-deps`
- Or delete `node_modules` and `package-lock.json`, then run `npm install` again

**Issue: Session/CSRF token mismatch**
- Clear browser cookies for `localhost`
- Make sure both frontend and backend use `https://`
- Check `SESSION_DOMAIN` in backend `.env` is set to `localhost`
- Check `SANCTUM_STATEFUL_DOMAINS` includes `localhost:3000`
- Make sure `SESSION_SAME_SITE=None` in backend `.env`

---

## API Endpoints

### Authentication
- `POST /api/register` - Register new user
- `POST /api/login` - Login
- `POST /api/logout` - Logout
- `GET /api/user` - Get current user

### Workspaces
- `GET /api/workspaces` - List all workspaces
- `POST /api/workspaces` - Create workspace
- `GET /api/workspaces/{id}` - Get workspace details
- `PUT /api/workspaces/{id}` - Update workspace
- `DELETE /api/workspaces/{id}` - Delete workspace

### Boards
- `GET /api/boards` - List boards
- `POST /api/boards` - Create board
- `GET /api/boards/{id}` - Get board with lists and cards
- `PUT /api/boards/{id}` - Update board
- `DELETE /api/boards/{id}` - Delete board

### Cards
- `POST /api/cards` - Create card
- `GET /api/cards/{id}` - Get card details
- `PUT /api/cards/{id}` - Update card
- `DELETE /api/cards/{id}` - Delete card
- `POST /api/cards/{id}/members` - Add member to card
- `POST /api/cards/{id}/labels` - Add label to card

For a complete list of endpoints, check `backend_pm/routes/api.php`

---

## SOME ENDPOINTS MIGHT BE UNUSED DUE TO SCRAPPED IDEAS

```
project-root/
├── nuxt.config.ts
├── package.json
├── tsconfig.json
├── app.vue
├── assets/
│   └── styles/
│       └── main.scss
├── components/
│   ├── board/
│   │   ├── BoardHeader.vue
│   │   ├── BoardSwitcher.vue
│   │   ├── BoardList.vue
│   │   └── BoardBackground.vue
│   ├── list/
│   │   ├── ListContainer.vue
│   │   ├── ListHeader.vue
│   │   ├── ListActions.vue
│   │   ├── ListColorPicker.vue
│   │   └── AddList.vue
│   ├── card/
│   │   ├── Card.vue
│   │   ├── CardModal.vue
│   │   ├── CardLabels.vue
│   │   ├── CardMembers.vue
│   │   ├── CardDueDate.vue
│   │   ├── CardDescription.vue
│   │   ├── CardAttachments.vue
│   │   ├── CardChecklist.vue
│   │   ├── CardComments.vue
│   │   └── AddCard.vue
│   ├── calendar/
│   │   ├── CalendarView.vue
│   │   ├── CalendarHeader.vue
│   │   ├── CalendarGrid.vue
│   │   ├── CalendarRangeSelector.vue
│   │   └── CalendarMenu.vue
│   ├── common/
│   │   ├── ColorPicker.vue
│   │   ├── DatePicker.vue
│   │   ├── MemberSelector.vue
│   │   ├── LabelManager.vue
│   │   └── AttachmentUploader.vue
│   └── layout/
│       ├── Navbar.vue
│       ├── Sidebar.vue
│       └── WorkspaceSelector.vue
├── composables/
│   ├── useBoard.ts
│   ├── useList.ts
│   ├── useCard.ts
│   ├── useCalendar.ts
│   ├── useLabels.ts
│   ├── useMembers.ts
│   ├── useDragDrop.ts
│   └── useWorkspace.ts
├── layouts/
│   └── default.vue
├── middleware/
│   └── auth.ts
├── pages/
│   └── index.vue
├── plugins/
│   └── vuetify.ts
├── stores/
│   ├── board.ts
│   ├── list.ts
│   ├── card.ts
│   ├── workspace.ts
│   ├── user.ts
│   └── ui.ts
├── types/
│   ├── board.ts
│   ├── list.ts
│   ├── card.ts
│   ├── workspace.ts
│   ├── user.ts
│   └── index.ts
└── utils/
    ├── api.ts
    ├── constants.ts
    ├── helpers.ts
    └── validators.ts
```

# ERD
![ERD](./Untitled%20Diagram.drawio(3).png "ERD")