<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

$pdo = get_db();

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    abort_request(
        400,
        'Invalid product ID.'
    );
}

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

$stockStatus = get_stock_status(
    (int) $product['stock']
);

$flash = flash_get();
?>

<?php require __DIR__ . '/includes/header.php'; ?>

<section class="product-detail-page">

    <a
        class="back-link"
        href="/product-catalog/index.php"
    >
        ← Back to products
    </a>

    <?php if ($flash): ?>

    <div
    class="flash flash-<?= htmlspecialchars($flash['type']) ?>"
>
    <?= htmlspecialchars($flash['message']) ?>
</div>

<?php endif; ?>

    <div class="product-detail">

        <div class="product-detail-image">

            <img
                src="<?= htmlspecialchars(
                    $product['image_url']
                    ?: 'https://placehold.co/900x700?text=No+Image'
                ) ?>"
                alt="<?= htmlspecialchars($product['name']) ?>"
            >

            <div class="detail-image-badges">

                <?php if ((int) $product['featured'] === 1): ?>

                    <span class="badge badge-featured">
                        Featured
                    </span>

                <?php endif; ?>

                <?php if (!empty($product['category_name'])): ?>

                    <span class="badge badge-category">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </span>

                <?php endif; ?>

            </div>

        </div>

        <div class="product-detail-content">

            <span class="eyebrow">
                NOVASTORE PRODUCT
            </span>

            <h1>
                <?= htmlspecialchars($product['name']) ?>
            </h1>

            <p class="product-detail-description">
                <?= htmlspecialchars($product['description']) ?>
            </p>

            <div class="product-detail-meta">

                <span
                    class="detail-stock <?= htmlspecialchars(
                        $stockStatus['class']
                    ) ?>"
                >
                    <?= htmlspecialchars($stockStatus['label']) ?>

                    <?php if ((int) $product['stock'] > 0): ?>
                        · <?= (int) $product['stock'] ?> available
                    <?php endif; ?>
                </span>

                <?php if (!empty($product['category_name'])): ?>

                    <span class="detail-category">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </span>

                <?php endif; ?>

            </div>

            <div class="product-detail-price">
                £<?= number_format(
                    (float) $product['price'],
                    2
                ) ?>
            </div>

            <div class="product-detail-actions">

                <?php if (admin_is_logged_in()): ?>

                    <a
                        href="/product-catalog/edit.php?id=<?= (int) $product['id'] ?>"
                        class="button-primary"
                    >
                        Edit Product
                    </a>

                    <form
                        method="post"
                        action="/product-catalog/delete.php"
                        class="delete-form"
                        onsubmit="return confirm('Are you sure you want to delete this product?');"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(csrf_token()) ?>"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) $product['id'] ?>"
                        >

                        <button
                            type="submit"
                            class="button-danger"
                        >
                            Delete Product
                        </button>

                    </form>

                <?php endif; ?>

                <a
                    href="/product-catalog/index.php"
                    class="button-secondary"
                >
                    Continue Browsing
                </a>

            </div>

            <div class="product-detail-dates">

                <span>
                    Added:
                    <?= date(
                        'd M Y',
                        strtotime($product['created_at'])
                    ) ?>
                </span>

                <span>
                    Updated:
                    <?= date(
                        'd M Y',
                        strtotime($product['updated_at'])
                    ) ?>
                </span>

            </div>

        </div>

    </div>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>