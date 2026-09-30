<?php
require __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort_request(
        405,
        'Method not allowed.'
    );
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    abort_request(
        403,
        'Invalid CSRF token.'
    );
}

admin_logout();

header(
    'Location: /product-catalog/index.php'
);

exit;