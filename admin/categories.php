<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';

require_admin();

$pdo = get_db();

$errors = [];


/*
|--------------------------------------------------------------------------
| CATEGORY ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        abort_request(
            403,
            'Invalid CSRF token.'
        );
    }

    $action = $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | CREATE CATEGORY
    |--------------------------------------------------------------------------
    */

    if ($action === 'create') {

        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $errors[] = 'Category name is required.';
        }

        $slug = generate_slug($name);

        if ($name !== '' && $slug === '') {
            $errors[] =
                'Unable to generate a valid category slug.';
        }

        if (empty($errors)) {

            if (
                category_exists(
                    $pdo,
                    $name,
                    $slug
                )
            ) {

                $errors[] =
                    'This category already exists.';

            } else {

                create_category(
                    $pdo,
                    $name,
                    $slug
                );

                flash_set(
                    'Category created successfully.'
                );

                header(
                    'Location: /product-catalog/admin/categories.php'
                );

                exit;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT CATEGORY
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'edit') {

        $categoryId = filter_input(
            INPUT_POST,
            'category_id',
            FILTER_VALIDATE_INT
        );

        $name = trim($_POST['name'] ?? '');

        if (!$categoryId) {
            $errors[] =
                'Invalid category.';
        }

        if ($name === '') {
            $errors[] =
                'Category name is required.';
        }

        $slug = generate_slug($name);

        if ($name !== '' && $slug === '') {
            $errors[] =
                'Unable to generate a valid category slug.';
        }

        if (empty($errors)) {

            if (
                category_exists(
                    $pdo,
                    $name,
                    $slug,
                    $categoryId
                )
            ) {

                $errors[] =
                    'Another category already uses this name.';

            } else {

                update_category(
                    $pdo,
                    $categoryId,
                    $name,
                    $slug
                );

                flash_set(
                    'Category updated successfully.'
                );

                header(
                    'Location: /product-catalog/admin/categories.php'
                );

                exit;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE CATEGORY
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'delete') {

        $categoryId = filter_input(
            INPUT_POST,
            'category_id',
            FILTER_VALIDATE_INT
        );

        if (!$categoryId) {
            abort_request(
                400,
                'Invalid category ID.'
            );
        }

        $productCount =
            get_category_product_count(
                $pdo,
                $categoryId
            );

        if ($productCount > 0) {

            $errors[] =
                'This category cannot be deleted because it contains products.';

        } else {

            delete_category(
                $pdo,
                $categoryId
            );

            flash_set(
                'Category deleted successfully.'
            );

            header(
                'Location: /product-catalog/admin/categories.php'
            );

            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | INVALID ACTION
    |--------------------------------------------------------------------------
    */

    else {

        abort_request(
            400,
            'Invalid category action.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$flash = flash_get();


/*
|--------------------------------------------------------------------------
| LOAD CATEGORIES
|--------------------------------------------------------------------------
*/

$categories =
    get_categories_with_product_count(
        $pdo
    );
?>


<?php require __DIR__ . '/../includes/header.php'; ?>


<section class="admin-dashboard">


    <!-- ==================================================
         PAGE HEADING
    ================================================== -->

    <div class="admin-heading">

        <div>

            <span class="eyebrow">
                NOVASTORE ADMIN
            </span>

            <h1>
                Categories
            </h1>

            <p>
                Organise your catalog and manage
                product categories.
            </p>

        </div>

        <a
            href="/product-catalog/admin/"
            class="button-secondary"
        >
            ← Dashboard
        </a>

    </div>


    <!-- ==================================================
         FLASH MESSAGE
    ================================================== -->

    <?php if ($flash): ?>

        <div
            class="flash flash-<?= htmlspecialchars(
                $flash['type']
            ) ?>"
        >
            <?= htmlspecialchars(
                $flash['message']
            ) ?>
        </div>

    <?php endif; ?>


    <!-- ==================================================
         ERRORS
    ================================================== -->

    <?php if (!empty($errors)): ?>

        <div class="error-box">

            <strong>
                Please fix the following:
            </strong>

            <ul>

                <?php foreach ($errors as $error): ?>

                    <li>
                        <?= htmlspecialchars($error) ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <!-- ==================================================
         CATEGORY MANAGEMENT
    ================================================== -->

    <div class="categories-layout">


        <!-- ==================================================
             ADD CATEGORY
        ================================================== -->

        <div class="dashboard-panel">

            <div class="dashboard-panel-heading">

                <div>

                    <span class="section-label">
                        NEW CATEGORY
                    </span>

                    <h2>
                        Add Category
                    </h2>

                </div>

            </div>


            <form
                method="post"
                action="/product-catalog/admin/categories.php"
                class="product-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        csrf_token()
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="create"
                >


                <div class="form-group">

                    <label for="name">
                        Category name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="e.g. Audio"
                        required
                    >

                    <span class="field-help">
                        A URL-friendly slug will be created automatically.
                    </span>

                </div>


                <button
                    type="submit"
                    class="button-primary"
                >
                    Add Category
                </button>

            </form>

        </div>


        <!-- ==================================================
             EXISTING CATEGORIES
        ================================================== -->

        <div class="dashboard-panel">

            <div class="dashboard-panel-heading">

                <div>

                    <span class="section-label">
                        CATALOG
                    </span>

                    <h2>
                        Existing Categories
                    </h2>

                </div>

            </div>


            <div class="category-admin-list">

                <?php if (empty($categories)): ?>

                    <div class="empty-state">

                        <h3>
                            No categories yet
                        </h3>

                        <p>
                            Create your first category using
                            the form on this page.
                        </p>

                    </div>

                <?php else: ?>


                    <?php foreach ($categories as $category): ?>

                        <div class="category-admin-row">


                            <!-- EDIT CATEGORY -->

                            <form
                                method="post"
                                action="/product-catalog/admin/categories.php"
                                class="category-edit-form"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(
                                        csrf_token()
                                    ) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="edit"
                                >

                                <input
                                    type="hidden"
                                    name="category_id"
                                    value="<?= (int) $category['id'] ?>"
                                >


                                <input
                                    type="text"
                                    name="name"
                                    value="<?= htmlspecialchars(
                                        $category['name']
                                    ) ?>"
                                    class="category-name-input"
                                    required
                                >


                                <span class="category-slug">
                                    <?= htmlspecialchars(
                                        $category['slug']
                                    ) ?>
                                </span>


                                <button
                                    type="submit"
                                    class="admin-action"
                                >
                                    Save
                                </button>

                            </form>


                            <!-- PRODUCT COUNT -->

                            <div class="category-product-count">

                                <?= (int) $category['product_count'] ?>

                                <?= (int) $category['product_count'] === 1
                                    ? 'product'
                                    : 'products' ?>

                            </div>


                            <!-- DELETE / IN USE -->

                            <?php if (
                                (int) $category['product_count'] === 0
                            ): ?>

                                <form
                                    method="post"
                                    action="/product-catalog/admin/categories.php"
                                    onsubmit="return confirm('Delete this category?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars(
                                            csrf_token()
                                        ) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="category_id"
                                        value="<?= (int) $category['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="admin-action admin-action-delete"
                                    >
                                        Delete
                                    </button>

                                </form>

                            <?php else: ?>

                                <span class="category-in-use">
                                    In use
                                </span>

                            <?php endif; ?>


                        </div>

                    <?php endforeach; ?>


                <?php endif; ?>

            </div>

        </div>

    </div>

</section>


<?php require __DIR__ . '/../includes/footer.php'; ?>