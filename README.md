# NovaStore

NovaStore is a PHP and MySQL e-commerce style web application designed to manage and display products through a clean public catalogue and a protected administration area.

The project was built as a practical full-stack development exercise using PHP, MySQL, HTML, CSS and JavaScript, with a focus on maintainability, security and usability.

---

## Features

### Public catalogue

- Responsive product grid
- Product categories
- Search by product name, description or category
- Product sorting:
  - Newest
  - Price: Low to High
  - Price: High to Low
  - Name: A–Z
  - Name: Z–A
- Product detail pages
- Featured product badges
- Stock status indicators
- Category filtering
- Empty-state messages when no products are found

### Admin area

- Secure administrator login
- Dashboard with product statistics
- Product management
- Add products
- Edit products
- Delete products
- Product image uploads
- Stock management
- Featured product management
- Category management
- Recent product activity
- Product filtering and sorting inside the admin dashboard

---

## Security

NovaStore includes several security measures:

- PDO prepared statements
- CSRF protection for sensitive forms
- Server-side validation
- Output escaping with `htmlspecialchars()`
- Secure admin sessions
- Session regeneration after login
- POST requests for destructive actions
- File upload validation
- Restricted image file types
- Database credentials excluded from Git with `.gitignore`

---

## Technologies

- PHP 8
- MySQL
- PDO
- HTML5
- CSS3
- JavaScript
- Apache
- MAMP
- phpMyAdmin
- Git
- GitHub

---

## Project Structure

```text
product-catalog/
│
├── admin/
│   ├── categories.php
│   ├── index.php
│   ├── login.php
│   └── logout.php
│
├── assets/
│   └── css/
│       └── style.css
│
├── config/
│   └── db.example.php
│
├── includes/
│   ├── error-page.php
│   ├── footer.php
│   ├── functions.php
│   ├── header.php
│   └── product-form.php
│
├── uploads/
│   └── products/
│       └── .gitkeep
│
├── .gitignore
├── add.php
├── delete.php
├── edit.php
├── index.php
├── product.php
├── README.md
└── schema.sql