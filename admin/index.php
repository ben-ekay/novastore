<?php

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';

require_admin();

$pdo = get_db();

$flash = flash_get();

$recentProducts = get_recent_products(
    $pdo,
    5
);


/*
|--------------------------------------------------------------------------
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$search = trim($_GET['q'] ?? '');


$allowedStatuses = [
    '',
    'featured',
    'standard',
    'in-stock',
    'low-stock',
    'out-of-stock',
];

$status = $_GET['status'] ?? '';

if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}


$categoryId = filter_input(
    INPUT_GET,
    'category',
    FILTER_VALIDATE_INT
);


$allowedSorts = [
    'updated_desc',
    'updated_asc',
    'name_asc',
    'name_desc',
    'price_asc',
    'price_desc',
    'stock_asc',
    'stock_desc',
];

$sort = $_GET['sort'] ?? 'updated_desc';

if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'updated_desc';
}


/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = get_categories($pdo);


/*
|--------------------------------------------------------------------------
| VALIDATE CATEGORY FILTER
|--------------------------------------------------------------------------
*/

if ($categoryId) {

    $validCategoryIds = array_map(
        static fn(array $category): int =>
            (int) $category['id'],
        $categories
    );

    if (!in_array(
        (int) $categoryId,
        $validCategoryIds,
        true
    )) {
        $categoryId = null;
    }
}


/*
|--------------------------------------------------------------------------
| CURRENT FILTERS
|--------------------------------------------------------------------------
*/

$currentFilters = [
    'q' => $search,
    'status' => $status,
    'category' => $categoryId ?: '',
    'sort' => $sort !== 'updated_desc'
        ? $sort
        : '',
];

$returnUrl = build_admin_filter_url(
    $currentFilters
);


/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

$totalProducts = (int) $pdo->query(
    'SELECT COUNT(*)
     FROM products'
)->fetchColumn();


$totalCategories = (int) $pdo->query(
    'SELECT COUNT(*)
     FROM categories'
)->fetchColumn();


$totalStock = (int) $pdo->query(
    'SELECT COALESCE(SUM(stock), 0)
     FROM products'
)->fetchColumn();


$featuredProducts = (int) $pdo->query(
    'SELECT COUNT(*)
     FROM products
     WHERE featured = 1'
)->fetchColumn();


$standardProducts = (int) $pdo->query(
    'SELECT COUNT(*)
     FROM products
     WHERE featured = 0'
)->fetchColumn();


/*
|--------------------------------------------------------------------------
| LOW STOCK
|--------------------------------------------------------------------------
*/

$lowStockProducts = $pdo->query(
    'SELECT
        id,
        name,
        stock
     FROM products
     WHERE stock BETWEEN 1 AND 5
     ORDER BY stock ASC, name ASC'
)->fetchAll();

$lowStockCount = count(
    $lowStockProducts
);


/*
|--------------------------------------------------------------------------
| OUT OF STOCK
|--------------------------------------------------------------------------
*/

$outOfStockCount = (int) $pdo->query(
    'SELECT COUNT(*)
     FROM products
     WHERE stock = 0'
)->fetchColumn();


/*
|--------------------------------------------------------------------------
| INVENTORY HEALTH
|--------------------------------------------------------------------------
*/

$healthyStockCount =
    $totalProducts
    - $lowStockCount
    - $outOfStockCount;


$healthyPercent = $totalProducts > 0
    ? ($healthyStockCount / $totalProducts) * 100
    : 0;


$lowStockPercent = $totalProducts > 0
    ? ($lowStockCount / $totalProducts) * 100
    : 0;


$outOfStockPercent = $totalProducts > 0
    ? ($outOfStockCount / $totalProducts) * 100
    : 0;


/*
|--------------------------------------------------------------------------
| PRODUCT QUERY
|--------------------------------------------------------------------------
*/

$sql = '
    SELECT
        p.id,
        p.name,
        p.price,
        p.stock,
        p.featured,
        p.image_url,
        p.updated_at,
        p.category_id,
        c.name AS category_name
    FROM products p

    LEFT JOIN categories c
        ON p.category_id = c.id

    WHERE 1 = 1
';

$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= '
        AND (
            p.name LIKE :search_name
            OR c.name LIKE :search_category
        )
    ';

    $term = '%' . $search . '%';

    $params['search_name'] =
        $term;

    $params['search_category'] =
        $term;
}


/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

if ($categoryId) {

    $sql .= '
        AND p.category_id = :category_id
    ';

    $params['category_id'] =
        $categoryId;
}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($status === 'featured') {

    $sql .= '
        AND p.featured = 1
    ';

} elseif ($status === 'standard') {

    $sql .= '
        AND p.featured = 0
    ';

} elseif ($status === 'in-stock') {

    $sql .= '
        AND p.stock > 5
    ';

} elseif ($status === 'low-stock') {

    $sql .= '
        AND p.stock BETWEEN 1 AND 5
    ';

} elseif ($status === 'out-of-stock') {

    $sql .= '
        AND p.stock = 0
    ';
}


/*
|--------------------------------------------------------------------------
| SORTING
|--------------------------------------------------------------------------
*/

$orderBy = match ($sort) {

    'name_asc' =>
        'p.name ASC',

    'name_desc' =>
        'p.name DESC',

    'price_asc' =>
        'p.price ASC',

    'price_desc' =>
        'p.price DESC',

    'stock_asc' =>
        'p.stock ASC',

    'stock_desc' =>
        'p.stock DESC',

    'updated_asc' =>
        'p.updated_at ASC',

    default =>
        'p.updated_at DESC',
};


$sql .= '
    ORDER BY ' . $orderBy;


$stmt = $pdo->prepare(
    $sql
);

$stmt->execute(
    $params
);

$products =
    $stmt->fetchAll();

$visibleProductCount =
    count($products);


/*
|--------------------------------------------------------------------------
| ACTIVE FILTERS
|--------------------------------------------------------------------------
*/

$hasActiveFilters =
    $search !== ''
    || $status !== ''
    || $categoryId
    || $sort !== 'updated_desc';


$activeCategoryName = null;

if ($categoryId) {

    foreach ($categories as $category) {

        if (
            (int) $category['id']
            === (int) $categoryId
        ) {

            $activeCategoryName =
                $category['name'];

            break;
        }
    }
}

?>

<?php require __DIR__ . '/../includes/header.php'; ?>


<section class="admin-dashboard">


    <!-- ==================================================
         DASHBOARD HEADING
    ================================================== -->

    <div class="admin-heading">

        <div>

            <span class="eyebrow">
                NOVASTORE ADMIN
            </span>

            <h1>
                Dashboard
            </h1>

            <p>
                Manage your products, categories
                and inventory from one place.
            </p>


            <div class="dashboard-summary">

                <span>
                    <?= $totalProducts ?>

                    <?= $totalProducts === 1
                        ? 'product'
                        : 'products' ?>
                </span>

                <span>·</span>

                <span>
                    <?= $totalCategories ?>

                    <?= $totalCategories === 1
                        ? 'category'
                        : 'categories' ?>
                </span>

                <span>·</span>

                <span>
                    <?= $totalStock ?>
                    units
                </span>

            </div>

        </div>


        <div class="admin-heading-actions">

            <a
                href="/product-catalog/add.php"
                class="button-primary"
            >
                + Add Product
            </a>

        </div>

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
         STATISTICS
    ================================================== -->

    <div class="dashboard-stats">


        <a
            href="/product-catalog/admin/#products"
            class="stat-card stat-card-link"
        >

            <span class="stat-label">
                Total Products
            </span>

            <strong>
                <?= $totalProducts ?>
            </strong>

        </a>


        <a
            href="/product-catalog/admin/categories.php"
            class="stat-card stat-card-link"
        >

            <span class="stat-label">
                Categories
            </span>

            <strong>
                <?= $totalCategories ?>
            </strong>

        </a>


        <div class="stat-card">

            <span class="stat-label">
                Units in Stock
            </span>

            <strong>
                <?= $totalStock ?>
            </strong>

            <span class="stat-meta">
                Total inventory units
            </span>

        </div>


        <a
            href="<?= htmlspecialchars(
                build_admin_filter_url(
                    $currentFilters,
                    ['status' => 'featured']
                )
            ) ?>"
            class="stat-card stat-card-link"
        >

            <span class="stat-label">
                Featured
            </span>

            <strong>
                <?= $featuredProducts ?>
            </strong>

        </a>


        <a
            href="<?= htmlspecialchars(
                build_admin_filter_url(
                    $currentFilters,
                    ['status' => 'low-stock']
                )
            ) ?>"
            class="stat-card stat-card-link <?= $lowStockCount > 0
                ? 'stat-card-warning'
                : '' ?>"
        >

            <span class="stat-label">
                Low Stock
            </span>

            <strong>
                <?= $lowStockCount ?>
            </strong>

            <span class="stat-status">
                <?= $lowStockCount > 0
                    ? 'Needs attention'
                    : 'All good' ?>
            </span>

        </a>


        <a
            href="<?= htmlspecialchars(
                build_admin_filter_url(
                    $currentFilters,
                    ['status' => 'out-of-stock']
                )
            ) ?>"
            class="stat-card stat-card-link <?= $outOfStockCount > 0
                ? 'stat-card-danger'
                : '' ?>"
        >

            <span class="stat-label">
                Out of Stock
            </span>

            <strong>
                <?= $outOfStockCount ?>
            </strong>

            <span class="stat-status">
                <?= $outOfStockCount > 0
                    ? 'Restock required'
                    : 'All available' ?>
            </span>

        </a>

    </div>


    <!-- ==================================================
         QUICK ACTIONS
    ================================================== -->

    <section class="dashboard-panel quick-actions-panel">

        <div class="dashboard-panel-heading">

            <div>

                <span class="section-label">
                    QUICK ACTIONS
                </span>

                <h2>
                    Shortcuts
                </h2>

            </div>

        </div>


        <div class="quick-actions-grid">


            <a
                href="/product-catalog/add.php"
                class="quick-action-card"
            >

                <span class="quick-action-icon">
                    +
                </span>

                <div>

                    <strong>
                        Add Product
                    </strong>

                    <span>
                        Create a new catalog item
                    </span>

                </div>

            </a>


            <a
                href="/product-catalog/admin/categories.php"
                class="quick-action-card"
            >

                <span class="quick-action-icon">
                    #
                </span>

                <div>

                    <strong>
                        Manage Categories
                    </strong>

                    <span>
                        Create and organise categories
                    </span>

                </div>

            </a>


            <a
                href="/product-catalog/index.php"
                class="quick-action-card"
            >

                <span class="quick-action-icon">
                    ↗
                </span>

                <div>

                    <strong>
                        View Store
                    </strong>

                    <span>
                        Open the public product catalog
                    </span>

                </div>

            </a>

        </div>

    </section>


    <!-- ==================================================
         INVENTORY HEALTH
    ================================================== -->

    <section class="dashboard-panel inventory-health-panel">

        <div class="dashboard-panel-heading">

            <div>

                <span class="section-label">
                    INVENTORY HEALTH
                </span>

                <h2>
                    Stock Overview
                </h2>

            </div>

        </div>


        <div class="inventory-health-grid">


            <a
                href="<?= htmlspecialchars(
                    build_admin_filter_url(
                        $currentFilters,
                        ['status' => 'in-stock']
                    )
                ) ?>"
                class="inventory-health-item inventory-health-link <?= $status === 'in-stock'
                    ? 'inventory-health-active'
                    : '' ?>"
            >

                <span class="inventory-health-label">
                    In Stock
                </span>

                <strong>
                    <?= $healthyStockCount ?>
                </strong>

            </a>


            <a
                href="<?= htmlspecialchars(
                    build_admin_filter_url(
                        $currentFilters,
                        ['status' => 'low-stock']
                    )
                ) ?>"
                class="inventory-health-item inventory-health-link <?= $status === 'low-stock'
                    ? 'inventory-health-active'
                    : '' ?>"
            >

                <span class="inventory-health-label">
                    Low Stock
                </span>

                <strong class="low-stock">
                    <?= $lowStockCount ?>
                </strong>

            </a>


            <a
                href="<?= htmlspecialchars(
                    build_admin_filter_url(
                        $currentFilters,
                        ['status' => 'out-of-stock']
                    )
                ) ?>"
                class="inventory-health-item inventory-health-link <?= $status === 'out-of-stock'
                    ? 'inventory-health-active'
                    : '' ?>"
            >

                <span class="inventory-health-label">
                    Out of Stock
                </span>

                <strong class="out-of-stock">
                    <?= $outOfStockCount ?>
                </strong>

            </a>

        </div>


        <div class="inventory-health-bar">

            <span
                class="inventory-health-segment inventory-health-good"
                style="width: <?= $healthyPercent ?>%"
                title="In Stock"
            ></span>

            <span
                class="inventory-health-segment inventory-health-warning"
                style="width: <?= $lowStockPercent ?>%"
                title="Low Stock"
            ></span>

            <span
                class="inventory-health-segment inventory-health-danger"
                style="width: <?= $outOfStockPercent ?>%"
                title="Out of Stock"
            ></span>

        </div>


        <div class="inventory-health-legend">


            <div class="inventory-legend-item">

                <span
                    class="inventory-legend-dot inventory-legend-good"
                ></span>

                <span>
                    In Stock
                </span>

                <strong>
                    <?= number_format(
                        $healthyPercent,
                        0
                    ) ?>%
                </strong>

            </div>


            <div class="inventory-legend-item">

                <span
                    class="inventory-legend-dot inventory-legend-warning"
                ></span>

                <span>
                    Low Stock
                </span>

                <strong>
                    <?= number_format(
                        $lowStockPercent,
                        0
                    ) ?>%
                </strong>

            </div>


            <div class="inventory-legend-item">

                <span
                    class="inventory-legend-dot inventory-legend-danger"
                ></span>

                <span>
                    Out of Stock
                </span>

                <strong>
                    <?= number_format(
                        $outOfStockPercent,
                        0
                    ) ?>%
                </strong>

            </div>

        </div>

    </section>


    <!-- ==================================================
         PRODUCT MANAGEMENT
    ================================================== -->

    <div
        class="dashboard-panel"
        id="products"
    >

        <div class="dashboard-panel-heading">

            <div>

                <span class="section-label">
                    CATALOG MANAGEMENT
                </span>

                <h2>
                    Products
                </h2>

            </div>


            <span class="catalog-result-count">

                <?= $visibleProductCount ?>

                <?= $visibleProductCount === 1
                    ? 'result'
                    : 'results' ?>

            </span>

        </div>


        <!-- ==================================================
             FILTERS
        ================================================== -->

        <form
            class="admin-filters"
            method="get"
            action="/product-catalog/admin/#products"
        >

            <div class="admin-filter-chips">


                <a
                    href="<?= htmlspecialchars(
                        build_admin_filter_url(
                            $currentFilters,
                            ['status' => '']
                        )
                    ) ?>"
                    class="admin-filter-chip <?= $status === ''
                        ? 'active'
                        : '' ?>"
                >

                    <span>
                        All
                    </span>

                    <span class="filter-count">
                        <?= $totalProducts ?>
                    </span>

                </a>


                <a
                    href="<?= htmlspecialchars(
                        build_admin_filter_url(
                            $currentFilters,
                            ['status' => 'featured']
                        )
                    ) ?>"
                    class="admin-filter-chip <?= $status === 'featured'
                        ? 'active'
                        : '' ?>"
                >

                    <span>
                        Featured
                    </span>

                    <span class="filter-count">
                        <?= $featuredProducts ?>
                    </span>

                </a>


                <a
                    href="<?= htmlspecialchars(
                        build_admin_filter_url(
                            $currentFilters,
                            ['status' => 'standard']
                        )
                    ) ?>"
                    class="admin-filter-chip <?= $status === 'standard'
                        ? 'active'
                        : '' ?>"
                >

                    <span>
                        Standard
                    </span>

                    <span class="filter-count">
                        <?= $standardProducts ?>
                    </span>

                </a>


                <a
                    href="<?= htmlspecialchars(
                        build_admin_filter_url(
                            $currentFilters,
                            ['status' => 'in-stock']
                        )
                    ) ?>"
                    class="admin-filter-chip <?= $status === 'in-stock'
                        ? 'active'
                        : '' ?>"
                >

                    <span>
                        In Stock
                    </span>

                    <span class="filter-count">
                        <?= $healthyStockCount ?>
                    </span>

                </a>


                <a
                    href="<?= htmlspecialchars(
                        build_admin_filter_url(
                            $currentFilters,
                            ['status' => 'low-stock']
                        )
                    ) ?>"
                    class="admin-filter-chip <?= $status === 'low-stock'
                        ? 'active'
                        : '' ?>"
                >

                    <span>
                        Low Stock
                    </span>

                    <span class="filter-count">
                        <?= $lowStockCount ?>
                    </span>

                </a>


                <a
                    href="<?= htmlspecialchars(
                        build_admin_filter_url(
                            $currentFilters,
                            ['status' => 'out-of-stock']
                        )
                    ) ?>"
                    class="admin-filter-chip <?= $status === 'out-of-stock'
                        ? 'active'
                        : '' ?>"
                >

                    <span>
                        Out of Stock
                    </span>

                    <span class="filter-count">
                        <?= $outOfStockCount ?>
                    </span>

                </a>

            </div>


            <?php if ($status !== ''): ?>

                <input
                    type="hidden"
                    name="status"
                    value="<?= htmlspecialchars(
                        $status
                    ) ?>"
                >

            <?php endif; ?>


            <select
                name="category"
                aria-label="Filter by category"
                onchange="this.form.submit()"
            >

                <option value="">
                    All categories
                </option>


                <?php foreach ($categories as $category): ?>

                    <option
                        value="<?= (int) $category['id'] ?>"
                        <?= $categoryId === (int) $category['id']
                            ? 'selected'
                            : '' ?>
                    >
                        <?= htmlspecialchars(
                            $category['name']
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>


            <select
                name="sort"
                aria-label="Sort products"
                onchange="this.form.submit()"
            >

                <option
                    value="updated_desc"
                    <?= $sort === 'updated_desc'
                        ? 'selected'
                        : '' ?>
                >
                    Recently updated
                </option>

                <option
                    value="updated_asc"
                    <?= $sort === 'updated_asc'
                        ? 'selected'
                        : '' ?>
                >
                    Oldest updated
                </option>

                <option
                    value="name_asc"
                    <?= $sort === 'name_asc'
                        ? 'selected'
                        : '' ?>
                >
                    Name A–Z
                </option>

                <option
                    value="name_desc"
                    <?= $sort === 'name_desc'
                        ? 'selected'
                        : '' ?>
                >
                    Name Z–A
                </option>

                <option
                    value="price_asc"
                    <?= $sort === 'price_asc'
                        ? 'selected'
                        : '' ?>
                >
                    Price low to high
                </option>

                <option
                    value="price_desc"
                    <?= $sort === 'price_desc'
                        ? 'selected'
                        : '' ?>
                >
                    Price high to low
                </option>

                <option
                    value="stock_asc"
                    <?= $sort === 'stock_asc'
                        ? 'selected'
                        : '' ?>
                >
                    Stock low to high
                </option>

                <option
                    value="stock_desc"
                    <?= $sort === 'stock_desc'
                        ? 'selected'
                        : '' ?>
                >
                    Stock high to low
                </option>

            </select>


            <div class="admin-search-group">

                <input
                    type="search"
                    name="q"
                    placeholder="Search products..."
                    value="<?= htmlspecialchars(
                        $search
                    ) ?>"
                    aria-label="Search products"
                >

                <button type="submit">
                    Search
                </button>


                <?php if ($hasActiveFilters): ?>

                    <a
                        href="/product-catalog/admin/#products"
                        class="clear-search"
                    >
                        Clear
                    </a>

                <?php endif; ?>

            </div>

        </form>


        <!-- ==================================================
             ACTIVE FILTERS
        ================================================== -->

        <?php if ($hasActiveFilters): ?>

            <div class="active-filters">

                <span class="active-filters-label">
                    Active filters:
                </span>


                <?php if ($search !== ''): ?>

                    <a
                        href="<?= htmlspecialchars(
                            build_admin_filter_url(
                                $currentFilters,
                                ['q' => '']
                            )
                        ) ?>"
                        class="active-filter-tag"
                    >

                        Search:
                        <?= htmlspecialchars(
                            $search
                        ) ?>

                        <span>
                            ×
                        </span>

                    </a>

                <?php endif; ?>


                <?php if ($status !== ''): ?>

                    <a
                        href="<?= htmlspecialchars(
                            build_admin_filter_url(
                                $currentFilters,
                                ['status' => '']
                            )
                        ) ?>"
                        class="active-filter-tag"
                    >

                        <?= htmlspecialchars(
                            match ($status) {
                                'featured' =>
                                    'Featured',

                                'standard' =>
                                    'Standard',

                                'in-stock' =>
                                    'In Stock',

                                'low-stock' =>
                                    'Low Stock',

                                'out-of-stock' =>
                                    'Out of Stock',

                                default =>
                                    $status,
                            }
                        ) ?>

                        <span>
                            ×
                        </span>

                    </a>

                <?php endif; ?>


                <?php if (
                    $categoryId
                    && $activeCategoryName !== null
                ): ?>

                    <a
                        href="<?= htmlspecialchars(
                            build_admin_filter_url(
                                $currentFilters,
                                ['category' => '']
                            )
                        ) ?>"
                        class="active-filter-tag"
                    >

                        <?= htmlspecialchars(
                            $activeCategoryName
                        ) ?>

                        <span>
                            ×
                        </span>

                    </a>

                <?php endif; ?>


                <?php if ($sort !== 'updated_desc'): ?>

                    <a
                        href="<?= htmlspecialchars(
                            build_admin_filter_url(
                                $currentFilters,
                                ['sort' => '']
                            )
                        ) ?>"
                        class="active-filter-tag"
                    >

                        <?= htmlspecialchars(
                            match ($sort) {

                                'updated_asc' =>
                                    'Oldest updated',

                                'name_asc' =>
                                    'Name A–Z',

                                'name_desc' =>
                                    'Name Z–A',

                                'price_asc' =>
                                    'Price low to high',

                                'price_desc' =>
                                    'Price high to low',

                                'stock_asc' =>
                                    'Stock low to high',

                                'stock_desc' =>
                                    'Stock high to low',

                                default =>
                                    'Recently updated',
                            }
                        ) ?>

                        <span>
                            ×
                        </span>

                    </a>

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- ==================================================
             LOW STOCK ALERT
        ================================================== -->

        <?php if (!empty($lowStockProducts)): ?>

            <div class="inventory-alert-panel">

                <div class="inventory-alert-heading">

                    <div>

                        <span class="section-label">
                            INVENTORY ALERTS
                        </span>

                        <h2>
                            Low Stock Products
                        </h2>

                    </div>


                    <span class="inventory-alert-count">

                        <?= $lowStockCount ?>

                        <?= $lowStockCount === 1
                            ? 'item'
                            : 'items' ?>

                    </span>

                </div>


                <div class="inventory-alert-list">


                    <?php foreach ($lowStockProducts as $lowStockProduct): ?>

                        <div class="inventory-alert-row">

                            <div>

                                <strong>
                                    <?= htmlspecialchars(
                                        $lowStockProduct['name']
                                    ) ?>
                                </strong>

                                <span class="low-stock">

                                    Only
                                    <?= (int) $lowStockProduct['stock'] ?>
                                    left

                                </span>

                            </div>


                            <a
                                href="/product-catalog/edit.php?id=<?= (int) $lowStockProduct['id'] ?>&return=<?= urlencode($returnUrl) ?>"
                                class="button-secondary"
                            >
                                Update Stock
                            </a>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endif; ?>


        <!-- ==================================================
             PRODUCT TABLE
        ================================================== -->

        <?php if (empty($products)): ?>

            <div class="empty-state">

                <h3>
                    No products found
                </h3>


                <p>

                    <?php if ($search !== ''): ?>

                        No products match
                        "<strong><?= htmlspecialchars(
                            $search
                        ) ?></strong>".

                    <?php elseif ($status === 'featured'): ?>

                        There are no featured products.

                    <?php elseif ($status === 'standard'): ?>

                        There are no standard products.

                    <?php elseif ($status === 'in-stock'): ?>

                        There are no products currently in stock.

                    <?php elseif ($status === 'low-stock'): ?>

                        There are no low-stock products.

                    <?php elseif ($status === 'out-of-stock'): ?>

                        There are no out-of-stock products.

                    <?php elseif (
                        $categoryId
                        && $activeCategoryName
                    ): ?>

                        There are no products in
                        <strong>
                            <?= htmlspecialchars(
                                $activeCategoryName
                            ) ?>
                        </strong>.

                    <?php else: ?>

                        Try changing your search or filters.

                    <?php endif; ?>

                </p>


                <?php if ($hasActiveFilters): ?>

                    <a
                        href="/product-catalog/admin/#products"
                        class="empty-button"
                    >
                        Clear filters
                    </a>

                <?php endif; ?>

            </div>


        <?php else: ?>


            <div class="admin-table-wrapper">

                <table class="admin-table">

                    <thead>

                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach ($products as $product): ?>

                            <tr>


                                <td>

                                    <a
                                        href="/product-catalog/product.php?id=<?= (int) $product['id'] ?>"
                                        class="admin-table-product admin-table-product-link"
                                    >

                                        <img
                                            src="<?= htmlspecialchars(
                                                $product['image_url']
                                                ?: 'https://placehold.co/100x75?text=No+Image'
                                            ) ?>"
                                            alt="<?= htmlspecialchars(
                                                $product['name']
                                            ) ?>"
                                        >

                                        <strong>
                                            <?= htmlspecialchars(
                                                $product['name']
                                            ) ?>
                                        </strong>

                                    </a>

                                </td>


                                <td>

                                    <?php if ($product['category_id']): ?>

                                        <a
                                            href="<?= htmlspecialchars(
                                                build_admin_filter_url(
                                                    $currentFilters,
                                                    [
                                                        'category' =>
                                                            (int) $product['category_id'],
                                                    ]
                                                )
                                            ) ?>"
                                            class="admin-category-link"
                                        >
                                            <?= htmlspecialchars(
                                                $product['category_name']
                                                ?? 'Uncategorised'
                                            ) ?>
                                        </a>

                                    <?php else: ?>

                                        <span class="admin-category-empty">
                                            Uncategorised
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>
                                    £<?= number_format(
                                        (float) $product['price'],
                                        2
                                    ) ?>
                                </td>


                                <td>

                                    <?php

                                    $stockStatus = get_stock_status(
                                        (int) $product['stock']
                                    );

                                    $stockFilter = match (
                                        $stockStatus['class']
                                    ) {

                                        'in-stock' =>
                                            'in-stock',

                                        'low-stock' =>
                                            'low-stock',

                                        'out-of-stock' =>
                                            'out-of-stock',

                                        default =>
                                            '',
                                    };

                                    ?>


                                    <div class="admin-stock-cell">

                                        <strong>
                                            <?= (int) $product['stock'] ?>
                                        </strong>


                                        <?php if ($stockFilter !== ''): ?>

                                            <a
                                                href="<?= htmlspecialchars(
                                                    build_admin_filter_url(
                                                        $currentFilters,
                                                        [
                                                            'status' =>
                                                                $stockFilter,
                                                        ]
                                                    )
                                                ) ?>"
                                                class="stock <?= htmlspecialchars(
                                                    $stockStatus['class']
                                                ) ?> admin-stock-link"
                                            >
                                                <?= htmlspecialchars(
                                                    $stockStatus['label']
                                                ) ?>
                                            </a>

                                        <?php else: ?>

                                            <span
                                                class="stock <?= htmlspecialchars(
                                                    $stockStatus['class']
                                                ) ?>"
                                            >
                                                <?= htmlspecialchars(
                                                    $stockStatus['label']
                                                ) ?>
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <td>

                                    <?php if (
                                        (int) $product['featured'] === 1
                                    ): ?>

                                        <a
                                            href="<?= htmlspecialchars(
                                                build_admin_filter_url(
                                                    $currentFilters,
                                                    ['status' => 'featured']
                                                )
                                            ) ?>"
                                            class="badge badge-featured admin-status-link"
                                        >
                                            Featured
                                        </a>

                                    <?php else: ?>

                                        <a
                                            href="<?= htmlspecialchars(
                                                build_admin_filter_url(
                                                    $currentFilters,
                                                    ['status' => 'standard']
                                                )
                                            ) ?>"
                                            class="admin-status-normal admin-status-link"
                                        >
                                            Standard
                                        </a>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <div class="admin-date-cell">

                                        <strong>
                                            <?= date(
                                                'd M Y',
                                                strtotime(
                                                    $product['updated_at']
                                                )
                                            ) ?>
                                        </strong>

                                        <span>
                                            <?= date(
                                                'H:i',
                                                strtotime(
                                                    $product['updated_at']
                                                )
                                            ) ?>
                                        </span>

                                    </div>

                                </td>


                                <td>

                                    <div class="admin-actions-menu">

                                        <details>

                                            <summary
                                                class="admin-actions-trigger"
                                                aria-label="Product actions"
                                            >
                                                ⋯
                                            </summary>


                                            <div class="admin-actions-dropdown">

                                                <a
                                                    href="/product-catalog/product.php?id=<?= (int) $product['id'] ?>"
                                                >
                                                    View
                                                </a>


                                                <a
                                                    href="/product-catalog/edit.php?id=<?= (int) $product['id'] ?>&return=<?= urlencode($returnUrl) ?>"
                                                >
                                                    Edit
                                                </a>


                                                <form
                                                    method="post"
                                                    action="/product-catalog/delete.php"
                                                    onsubmit="return confirm('Are you sure you want to delete this product?');"
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
                                                        name="id"
                                                        value="<?= (int) $product['id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="redirect"
                                                        value="dashboard"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="admin-dropdown-delete"
                                                    >
                                                        Delete
                                                    </button>

                                                </form>

                                            </div>

                                        </details>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>


    <!-- ==================================================
         RECENT ACTIVITY
    ================================================== -->

    <section class="dashboard-panel recent-activity-panel">

        <div class="dashboard-panel-heading">

            <div>

                <span class="section-label">
                    RECENT ACTIVITY
                </span>

                <h2>
                    Recent Product Activity
                </h2>

            </div>


            <a
                href="/product-catalog/admin/#products"
                class="recent-activity-link"
            >
                View all products →
            </a>

        </div>


        <?php if (empty($recentProducts)): ?>

            <div class="empty-state">

                <h3>
                    No recent activity
                </h3>

                <p>
                    Product changes will appear here.
                </p>

            </div>


        <?php else: ?>


            <div class="recent-activity-list">


                <?php foreach ($recentProducts as $product): ?>

                    <a
                        href="/product-catalog/product.php?id=<?= (int) $product['id'] ?>"
                        class="recent-activity-row"
                    >

                        <img
                            src="<?= htmlspecialchars(
                                $product['image_url']
                                ?: 'https://placehold.co/160x120?text=No+Image'
                            ) ?>"
                            alt="<?= htmlspecialchars(
                                $product['name']
                            ) ?>"
                        >


                        <div class="recent-activity-info">

                            <strong>
                                <?= htmlspecialchars(
                                    $product['name']
                                ) ?>
                            </strong>

                            <span>

                                <?php if (
                                    $product['activity_type'] === 'Added'
                                ): ?>

                                    Added to
                                    <?= htmlspecialchars(
                                        $product['category_name']
                                        ?? 'Uncategorised'
                                    ) ?>

                                <?php else: ?>

                                    Updated product details

                                <?php endif; ?>

                            </span>

                        </div>


                        <?php

                        $activityClass = strtolower(
                            $product['activity_type']
                        );

                        ?>


                        <span
                            class="activity-badge activity-<?= htmlspecialchars(
                                $activityClass
                            ) ?>"
                        >
                            <?= htmlspecialchars(
                                $product['activity_type']
                            ) ?>
                        </span>


                        <span class="recent-activity-date">
                            <?= htmlspecialchars(
                                time_ago(
                                    $product['updated_at']
                                )
                            ) ?>
                        </span>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</section>


<?php require __DIR__ . '/../includes/footer.php'; ?>