<?php
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPath = parse_url(
    $_SERVER['REQUEST_URI'] ?? '',
    PHP_URL_PATH
);

$isProducts =
    $currentPath === '/product-catalog/'
    || $currentPath === '/product-catalog'
    || $currentPath === '/product-catalog/index.php'
    || $currentPath === '/product-catalog/product.php';

$isDashboard =
    $currentPath === '/product-catalog/admin/'
    || $currentPath === '/product-catalog/admin/index.php'
    || $currentPath === '/product-catalog/edit.php';

$isCategories =
    $currentPath === '/product-catalog/admin/categories.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>NovaStore | Product Catalog</title>

    <link
        rel="stylesheet"
        href="/product-catalog/assets/css/style.css"
    >
</head>

<body>

<header class="site-header">

    <div class="header-container">

        <a
            class="brand"
            href="/product-catalog/index.php"
        >

            <span class="brand-mark">
                N
            </span>

            <span class="brand-text">
                NovaStore
            </span>

        </a>

        <nav class="main-nav">

    <a
        href="/product-catalog/index.php"
        class="<?= $isProducts ? 'nav-active' : '' ?>"
    >
        Products
    </a>

    <?php if (admin_is_logged_in()): ?>

        <a
            href="/product-catalog/admin/"
            class="<?= $isDashboard ? 'nav-active' : '' ?>"
        >
            Dashboard
        </a>

        <a
            href="/product-catalog/admin/categories.php"
            class="<?= $isCategories ? 'nav-active' : '' ?>"
        >
            Categories
        </a>

        <form
    method="post"
    action="/product-catalog/admin/logout.php"
    class="nav-logout-form"
>
    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars(csrf_token()) ?>"
    >

    <button
        type="submit"
        class="nav-link-button"
    >
        Log out
    </button>
</form>

    <?php else: ?>

        <a
            href="/product-catalog/admin/login.php"
        >
            Log in
        </a>

    <?php endif; ?>

</nav>

    </div>

</header>

<main class="main-content">