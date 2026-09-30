<?php

function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (
        empty($_SESSION['csrf_token'])
        || !is_string($_SESSION['csrf_token'])
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_validate(?string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (
        empty($_SESSION['csrf_token'])
        || !is_string($_SESSION['csrf_token'])
        || empty($token)
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['csrf_token'],
        $token
    );
}
function admin_login(array $user): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    session_regenerate_id(true);

    $_SESSION['admin_user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
}

function admin_logout(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function admin_is_logged_in(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION['admin_user']['id']);
}

function require_admin(): void
{
    if (!admin_is_logged_in()) {
        header('Location: /product-catalog/admin/login.php');
        exit;
    }
}
function get_categories(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT
            id,
            name,
            slug
         FROM categories
         ORDER BY name ASC'
    );

    return $stmt->fetchAll();
}
function get_stock_status(int $stock): array
{
    if ($stock <= 0) {
        return [
            'label' => 'Out of stock',
            'class' => 'out-of-stock',
        ];
    }

    if ($stock <= 5) {
        return [
            'label' => 'Low stock',
            'class' => 'low-stock',
        ];
    }

    return [
        'label' => 'In stock',
        'class' => 'in-stock',
    ];
}
function upload_product_image(array $file): ?string
{
    if (
        !isset($file['error'])
        || $file['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            'There was an error uploading the image.'
        );
    }

    $maxSize = 5 * 1024 * 1024;

    if ($file['size'] > $maxSize) {
        throw new RuntimeException(
            'Image must be 5MB or smaller.'
        );
    }

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $mimeType = mime_content_type(
        $file['tmp_name']
    );

    if (!isset($allowedMimeTypes[$mimeType])) {
        throw new RuntimeException(
            'Only JPG, PNG and WebP images are allowed.'
        );
    }

    $extension = $allowedMimeTypes[$mimeType];

    $filename =
        bin2hex(random_bytes(16))
        . '.'
        . $extension;

    $uploadDirectory =
        __DIR__
        . '/../uploads/products';

    if (!is_dir($uploadDirectory)) {

        if (
            !mkdir(
                $uploadDirectory,
                0755,
                true
            )
            && !is_dir($uploadDirectory)
        ) {
            throw new RuntimeException(
                'Unable to create upload directory.'
            );
        }
    }

    $destination =
        $uploadDirectory
        . '/'
        . $filename;

    if (
        !move_uploaded_file(
            $file['tmp_name'],
            $destination
        )
    ) {
        throw new RuntimeException(
            'Unable to save uploaded image.'
        );
    }

    return
        '/product-catalog/uploads/products/'
        . $filename;
}


function delete_product_image(
    ?string $imageUrl
): void
{
    if (!$imageUrl) {
        return;
    }

    $prefix =
        '/product-catalog/uploads/products/';

    if (!str_starts_with($imageUrl, $prefix)) {
        return;
    }

    $filename = basename($imageUrl);

    $path =
        __DIR__
        . '/../uploads/products/'
        . $filename;

    if (is_file($path)) {
        unlink($path);
    }
}
function validate_product_values(array $values): array
{
    $errors = [];

    if (($values['name'] ?? '') === '') {
        $errors[] = 'Name is required.';
    }

    if (($values['description'] ?? '') === '') {
        $errors[] = 'Description is required.';
    }

    $categoryId = $values['category_id'] ?? '';

    if (
        $categoryId === ''
        || !ctype_digit((string) $categoryId)
    ) {
        $errors[] = 'Please select a category.';
    }

    $price = $values['price'] ?? '';

    if (
        $price === ''
        || !is_numeric($price)
        || (float) $price < 0
    ) {
        $errors[] = 'Price must be a positive number.';
    }

    $stock = $values['stock'] ?? '';

    if (
        $stock === ''
        || filter_var(
            $stock,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 0
                ]
            ]
        ) === false
    ) {
        $errors[] = 'Stock must be 0 or greater.';
    }

    return $errors;
}
function product_values_from_request(): array
{
    return [
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category_id' => trim($_POST['category_id'] ?? ''),
        'price' => trim($_POST['price'] ?? ''),
        'stock' => trim($_POST['stock'] ?? ''),
        'featured' => isset($_POST['featured']) ? '1' : '0',
    ];
}
function get_product_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT
            p.id,
            p.name,
            p.description,
            p.category_id,
            p.price,
            p.stock,
            p.featured,
            p.image_url,
            p.created_at,
            p.updated_at,
            c.name AS category_name
         FROM products p
         LEFT JOIN categories c
            ON p.category_id = c.id
         WHERE p.id = :id'
    );

    $stmt->execute([
        'id' => $id
    ]);

    $product = $stmt->fetch();

    return $product ?: null;
}
function create_product(
    PDO $pdo,
    array $values,
    ?string $imageUrl
): int {
    $stmt = $pdo->prepare(
        'INSERT INTO products
        (
            name,
            description,
            category_id,
            price,
            stock,
            featured,
            image_url
        )
        VALUES
        (
            :name,
            :description,
            :category_id,
            :price,
            :stock,
            :featured,
            :image_url
        )'
    );

    $stmt->execute([
        'name' => $values['name'],
        'description' => $values['description'],
        'category_id' => (int) $values['category_id'],
        'price' => (float) $values['price'],
        'stock' => (int) $values['stock'],
        'featured' => (int) $values['featured'],
        'image_url' => $imageUrl,
    ]);

    return (int) $pdo->lastInsertId();
}


function update_product(
    PDO $pdo,
    int $id,
    array $values,
    ?string $imageUrl
): void {
    $stmt = $pdo->prepare(
        'UPDATE products
         SET
            name = :name,
            description = :description,
            category_id = :category_id,
            price = :price,
            stock = :stock,
            featured = :featured,
            image_url = :image_url
         WHERE id = :id'
    );

    $stmt->execute([
        'name' => $values['name'],
        'description' => $values['description'],
        'category_id' => (int) $values['category_id'],
        'price' => (float) $values['price'],
        'stock' => (int) $values['stock'],
        'featured' => (int) $values['featured'],
        'image_url' => $imageUrl,
        'id' => $id,
    ]);
}
function delete_product(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare(
        'DELETE FROM products
         WHERE id = :id'
    );

    $stmt->execute([
        'id' => $id
    ]);
}
function flash_set(
    string $message,
    string $type = 'success'
): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $allowedTypes = [
        'success',
        'error',
        'warning',
        'info',
    ];

    if (!in_array($type, $allowedTypes, true)) {
        $type = 'success';
    }

    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type,
    ];
}


function flash_get(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['flash_message'])) {
        return null;
    }

    $flash = $_SESSION['flash_message'];

    unset($_SESSION['flash_message']);

    return $flash;
}
function abort_request(
    int $statusCode,
    string $message
): never {
    http_response_code($statusCode);

    $titles = [
        400 => 'Bad Request',
        403 => 'Access Denied',
        404 => 'Page Not Found',
        405 => 'Method Not Allowed',
        500 => 'Something Went Wrong',
    ];

    $errorTitle =
        $titles[$statusCode]
        ?? 'Something Went Wrong';

    $errorMessage = $message;

    require __DIR__ . '/error-page.php';

    exit;
}
function generate_slug(string $text): string
{
    $text = trim($text);
    $text = strtolower($text);

    $text = iconv(
        'UTF-8',
        'ASCII//TRANSLIT//IGNORE',
        $text
    );

    $text = preg_replace(
        '/[^a-z0-9]+/',
        '-',
        $text
    );

    $text = trim(
        $text,
        '-'
    );

    return $text;
}
function category_exists(
    PDO $pdo,
    string $name,
    string $slug,
    ?int $excludeId = null
): bool {
    $sql = '
        SELECT id
        FROM categories
        WHERE (
            name = :name
            OR slug = :slug
        )
    ';

    $params = [
        'name' => $name,
        'slug' => $slug,
    ];

    if ($excludeId !== null) {
        $sql .= ' AND id != :id';
        $params['id'] = $excludeId;
    }

    $sql .= ' LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetch();
}


function create_category(
    PDO $pdo,
    string $name,
    string $slug
): int {
    $stmt = $pdo->prepare(
        'INSERT INTO categories
        (
            name,
            slug
        )
        VALUES
        (
            :name,
            :slug
        )'
    );

    $stmt->execute([
        'name' => $name,
        'slug' => $slug,
    ]);

    return (int) $pdo->lastInsertId();
}


function update_category(
    PDO $pdo,
    int $id,
    string $name,
    string $slug
): void {
    $stmt = $pdo->prepare(
        'UPDATE categories
         SET
            name = :name,
            slug = :slug
         WHERE id = :id'
    );

    $stmt->execute([
        'name' => $name,
        'slug' => $slug,
        'id' => $id,
    ]);
}


function get_category_product_count(
    PDO $pdo,
    int $categoryId
): int {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM products
         WHERE category_id = :id'
    );

    $stmt->execute([
        'id' => $categoryId,
    ]);

    return (int) $stmt->fetchColumn();
}


function delete_category(
    PDO $pdo,
    int $categoryId
): void {
    $stmt = $pdo->prepare(
        'DELETE FROM categories
         WHERE id = :id'
    );

    $stmt->execute([
        'id' => $categoryId,
    ]);
}


function get_categories_with_product_count(
    PDO $pdo
): array {
    $stmt = $pdo->query(
        'SELECT
            c.id,
            c.name,
            c.slug,
            COUNT(p.id) AS product_count
         FROM categories c
         LEFT JOIN products p
            ON p.category_id = c.id
         GROUP BY
            c.id,
            c.name,
            c.slug
         ORDER BY c.name ASC'
    );

    return $stmt->fetchAll();
}
function category_id_exists(
    PDO $pdo,
    int $categoryId
): bool {
    $stmt = $pdo->prepare(
        'SELECT id
         FROM categories
         WHERE id = :id
         LIMIT 1'
    );

    $stmt->execute([
        'id' => $categoryId,
    ]);

    return (bool) $stmt->fetchColumn();
}
function get_recent_products(
    PDO $pdo,
    int $limit = 5
): array {
    $stmt = $pdo->prepare(
        'SELECT
            p.id,
            p.name,
            p.image_url,
            p.created_at,
            p.updated_at,
            c.name AS category_name,
            CASE
                WHEN p.created_at = p.updated_at
                THEN "Added"
                ELSE "Updated"
            END AS activity_type
         FROM products p
         LEFT JOIN categories c
            ON p.category_id = c.id
         ORDER BY p.updated_at DESC
         LIMIT :limit'
    );

    $stmt->bindValue(
        ':limit',
        $limit,
        PDO::PARAM_INT
    );

    $stmt->execute();

    return $stmt->fetchAll();
}
function build_admin_filter_url(
    array $currentFilters,
    array $changes = []
): string {

    $filters = array_merge(
        $currentFilters,
        $changes
    );

    $filters = array_filter(
        $filters,
        static fn($value) =>
            $value !== ''
            && $value !== null
    );

    $query = http_build_query(
        $filters
    );

    return '/product-catalog/admin/'
        . ($query !== ''
            ? '?' . $query
            : '')
        . '#products';
}
function time_ago(string $dateTime): string
{
    $timestamp = strtotime($dateTime);

    if ($timestamp === false) {
        return '';
    }

    $difference = time() - $timestamp;

    if ($difference < 60) {
        return 'just now';
    }

    if ($difference < 3600) {
        $minutes = (int) floor($difference / 60);

        return $minutes === 1
            ? '1 min ago'
            : $minutes . ' mins ago';
    }

    if ($difference < 86400) {
        $hours = (int) floor($difference / 3600);

        return $hours === 1
            ? '1 hour ago'
            : $hours . ' hours ago';
    }

    if ($difference < 604800) {
        $days = (int) floor($difference / 86400);

        return $days === 1
            ? '1 day ago'
            : $days . ' days ago';
    }

    return date(
        'd M Y',
        $timestamp
    );
}