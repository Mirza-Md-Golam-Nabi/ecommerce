## About this Project

A single-city, Cash-on-Delivery grocery shop (Chaldal-style).

- **Storefront:** Inertia + React (customers browse, add to bag, place COD orders)
- **Admin panel:** Filament (`/admin`) — catalog, orders, purchase list, sales & profit reports
- **Stack:** Laravel 13, PHP 8.3, MySQL, Node.js 22

Design documents live in the [`docs/`](docs/README.md) folder.

## ✅ Project Setup Instructions

**Requirements:** PHP 8.3+, Composer, MySQL, Node.js 22+ and npm.

1. Clone the repository

For Local Machine:

```sh
git clone <repository-url> ecommerce
```

For Live Server or cPanel (inside the empty project folder):

```sh
git clone <repository-url> .
```

2. Goto project folder

```sh
cd ecommerce
```

3. Install dependencies using Composer

For Local Machine:

```sh
composer install
```

For Live Server:

```sh
composer install --no-dev --optimize-autoloader
```

If you want to install it in cPanel, first check **composer** is install or not. For checking:

```sh
composer -v
```

If you see "**Composer Not Found**", you need to install Composer on your system.
Follow this guide [Composer install in cPanel](https://github.com/Mirza-Md-Golam-Nabi/tips/blob/master/laravel/composer/README.md#composer-install-in-cpanel-%EF%B8%8F)

4. Create the **.env** file

Copy the example environment file:

```sh
cp .env.example .env
```

Then open `.env` and set the database and admin login details:

```env
APP_NAME="Your Shop Name"
APP_URL=http://localhost:8000

DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=

ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=password
```

For Live Server, also change these:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

5. Run this command:

On cPanel, first point `php` to PHP 8.3:

```sh
alias php="/opt/cpanel/ea-php83/root/usr/bin/php"
```

```sh
php artisan key:generate
```

```sh
php artisan storage:link
```

> `storage:link` is required — product and category images are uploaded to the `public` disk.

6. Create the database

Create a database named:

```sh
ecommerce
```

7. Run migrations and seeders

Run the following command to migrate and seed the database:

```sh
php artisan migrate --seed
```

> When `APP_ENV=production`, only the admin user is seeded. Demo areas and catalog data are seeded on local/testing environments only.

8. Run the application

```sh
npm install
```

and

```sh
npm run build
```

and

```sh
php artisan serve
```

For local development (server + queue worker + Vite hot reload together), you can instead run:

```sh
composer run dev
```

### 🌐 Extra steps for Live Server

1. Point the domain's document root to the project's **`public`** folder.

2. Cache the config, routes and views:

```sh
php artisan optimize
```

3. Add a **Cron Job** (cPanel → Cron Jobs) to run the scheduler every minute. It prunes old data and rebuilds the daily sales report summaries:

```sh
* * * * * cd /home/USERNAME/path-to-project && /opt/cpanel/ea-php83/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

4. Add a second **Cron Job** for the queue worker (reports are refreshed through queued jobs):

```sh
* * * * * cd /home/USERNAME/path-to-project && /opt/cpanel/ea-php83/root/usr/bin/php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

5. After every new deploy (`git pull`), run:

```sh
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm install && npm run build
php artisan optimize
```

## 🔑 Default Login Credentials

When the seeder file is executed, a default admin user is created in the `users` table from the `ADMIN_EMAIL` / `ADMIN_PASSWORD` values in `.env`.

| Panel | URL      | Email               | Password   |
| ----- | -------- | ------------------- | ---------- |
| Admin | `/admin` | `admin@example.com` | `password` |

Customers log in / sign up from the storefront itself; no seeded customer account is needed.

> ⚠️ These are default seeder credentials meant for local/testing environments only. Make sure to change `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env` **before** running the seeder on production.
