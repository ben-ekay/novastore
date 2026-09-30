<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

require_admin();


/*
|--------------------------------------------------------------------------
| REQUEST METHOD
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort_request(
        405,
        'Method not allowed.'
    );
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    abort_request(
        403,
        'Invalid CSRF token.'
    );
}


/*
|--------------------------------------------------------------------------
| PRODUCT ID
|--------------------------------------------------------------------------
*/

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    abort_request(
        400,
        'Invalid product ID.'
    );
}


/*
|--------------------------------------------------------------------------
| LOAD PRODUCT
|--------------------------------------------------------------------------
*/

$pdo = get_db();

$product = get_product_by_id(
    $pdo,
    $id
);

if (!$product) {
    abort_request(
        404,
        'Product not found.'
    );
}


/*
|--------------------------------------------------------------------------
| DELETE PRODUCT
|--------------------------------------------------------------------------
*/

delete_product(
    $pdo,
    $id
);

delete_product_image(
    $product['image_url'] ?? null
);


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

flash_set(
    'Product deleted successfully.'
);


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

$redirect = $_POST['redirect'] ?? '';

if ($redirect === 'dashboard') {
    header('Location: /product-catalog/admin/#products');
    exit;
}

header(
    'Location: /product-catalog/index.php'
);

exit;