<?php
/*
|--------------------------------------------------------------------------
| EXPECTED VARIABLES
|--------------------------------------------------------------------------
|
| $statusCode
| $errorTitle
| $errorMessage
|
*/
?>

<?php require __DIR__ . '/header.php'; ?>

<section class="error-page">

    <div class="error-card">

        <span class="error-code">
            <?= (int) $statusCode ?>
        </span>

        <h1>
            <?= htmlspecialchars($errorTitle) ?>
        </h1>

        <p>
            <?= htmlspecialchars($errorMessage) ?>
        </p>

        <div class="error-actions">

            <a
                href="/product-catalog/index.php"
                class="button-primary"
            >
                Back to Products
            </a>

            <?php if (admin_is_logged_in()): ?>

                <a
                    href="/product-catalog/admin/"
                    class="button-secondary"
                >
                    Dashboard
                </a>

            <?php endif; ?>

        </div>

    </div>

</section>

<?php require __DIR__ . '/footer.php'; ?>