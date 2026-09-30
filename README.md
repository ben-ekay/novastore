# Product Catalog — Basic PHP + MySQL Website

A minimal product catalog: a home page that lists products from a MySQL
database, and a form page for adding new ones. Built with plain PHP, HTML,
and CSS — no framework required.

## Files

```
product-catalog/
├── index.php        Home page — lists all products from the database
├── add.php          Form + handler for adding a new product
├── config/db.php     Database connection settings (edit this first)
├── css/style.css     Styling for all pages
├── schema.sql        Creates the database, table, and sample data
└── README.md
```

## Requirements

- PHP 8+ with the `pdo_mysql` extension
- MySQL (or MariaDB) server

## Setup

1. **Create the database and table.** From a terminal, with your MySQL
   server running:

   ```bash
   mysql -u root -p < schema.sql
   ```

   This creates a `product_catalog` database, a `products` table, and
   seeds it with five sample products. (Omit `-p` if your root user has no
   password.)

2. **Set your credentials.** Open `config/db.php` and update the four
   constants at the top if your username, password, or host differ from
   the defaults (`root` user, no password, `127.0.0.1`):

   ```php
   const DB_HOST = '127.0.0.1';
   const DB_NAME = 'product_catalog';
   const DB_USER = 'root';
   const DB_PASS = '';
   ```

3. **Run the site.** PHP's built-in server is the fastest way to try it
   locally:

   ```bash
   cd product-catalog
   php -S localhost:8000
   ```

   Then open http://localhost:8000 in your browser.

   (Any standard PHP host — Apache, Nginx+PHP-FPM, XAMPP/MAMP, etc. —
   works too; just point the document root at this folder.)

## Using it

- **Home page (`index.php`)** — shows every product currently in the
  `products` table as a card grid (image, name, description, price).
- **Add product (`add.php`)** — a form that validates input server-side
  and inserts a new row into `products` via a prepared statement, then
  redirects back to the home page.

## Notes on the database layer

- `config/db.php` uses PDO with prepared statements everywhere data is
  inserted, so user input is never concatenated into SQL.
- All output is passed through `htmlspecialchars()` before being echoed
  into HTML, to avoid XSS from stored data.
- This is intentionally minimal (no auth, no edit/delete yet) so it's easy
  to read end-to-end and extend — natural next steps would be an
  edit/delete page, image uploads instead of URLs, and pagination.
