<?php

require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

$pdo = get_db();

$flash = flash_get();


/*
|--------------------------------------------------------------------------
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$search = trim(
    $_GET['q'] ?? ''
);

$categoryId = filter_input(
    INPUT_GET,
    'category',
    FILTER_VALIDATE_INT
);


/*
|--------------------------------------------------------------------------
| SORT
|--------------------------------------------------------------------------
*/

$allowedSorts = [
    'newest',
    'price_asc',
    'price_desc',
    'name_asc',
    'name_desc',
];

$sort = $_GET['sort'] ?? 'newest';

if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'newest';
}


/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = get_categories(
    $pdo
);


/*
|--------------------------------------------------------------------------
| VALIDATE CATEGORY FILTER
|--------------------------------------------------------------------------
*/

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

    if ($activeCategoryName === null) {
        $categoryId = null;
    }
}


/*
|--------------------------------------------------------------------------
| PRODUCT QUERY
|--------------------------------------------------------------------------
*/

$sql = '
    SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.stock,
        p.featured,
        p.image_url,
        p.created_at,
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
            OR p.description LIKE :search_description
            OR c.name LIKE :search_category
        )
    ';

    $term =
        '%' . $search . '%';

    $params['search_name'] =
        $term;

    $params['search_description'] =
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
| ORDER
|--------------------------------------------------------------------------
*/

$orderBy = match ($sort) {

    'price_asc' =>
        'p.price ASC, p.id DESC',

    'price_desc' =>
        'p.price DESC, p.id DESC',

    'name_asc' =>
        'p.name ASC, p.id DESC',

    'name_desc' =>
        'p.name DESC, p.id DESC',

    default =>
        'p.created_at DESC, p.id DESC',
};

$sql .= '
    ORDER BY ' . $orderBy;


/*
|--------------------------------------------------------------------------
| EXECUTE QUERY
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    $sql
);

$stmt->execute(
    $params
);

$products =
    $stmt->fetchAll();

$productCount =
    count($products);


/*
|--------------------------------------------------------------------------
| CATALOG TITLE
|--------------------------------------------------------------------------
*/

$catalogTitle = 'All Products';

if ($activeCategoryName !== null) {
    $catalogTitle = $activeCategoryName;
}

if ($search !== '') {
    $catalogTitle = 'Search Results';
}


/*
|--------------------------------------------------------------------------
| CATALOG SUBTITLE
|--------------------------------------------------------------------------
*/

$catalogSubtitle = $productCount === 1
    ? '1 product'
    : $productCount . ' products';

if (
    $search !== ''
    && $activeCategoryName !== null
) {

    $catalogSubtitle .=
        ' found in ' . $activeCategoryName;

} elseif ($search !== '') {

    $catalogSubtitle .=
        ' found';
}


/*
|--------------------------------------------------------------------------
| CATEGORY URL PARAMETERS
|--------------------------------------------------------------------------
*/

$categoryQuery = [];

if ($search !== '') {
    $categoryQuery['q'] =
        $search;
}

if ($sort !== 'newest') {
    $categoryQuery['sort'] =
        $sort;
}


/*
|--------------------------------------------------------------------------
| ALL PRODUCTS URL
|--------------------------------------------------------------------------
*/

$allProductsParams = [];

if ($search !== '') {
    $allProductsParams['q'] =
        $search;
}

if ($sort !== 'newest') {
    $allProductsParams['sort'] =
        $sort;
}

$allProductsUrl =
    '/product-catalog/index.php';

if (!empty($allProductsParams)) {

    $allProductsUrl .=
        '?' . http_build_query(
            $allProductsParams
        );
}

$allProductsUrl .=
    '#catalog';


/*
|--------------------------------------------------------------------------
| CLEAR SEARCH URL
|--------------------------------------------------------------------------
*/

$clearSearchParams = [];

if ($categoryId) {
    $clearSearchParams['category'] =
        (int) $categoryId;
}

if ($sort !== 'newest') {
    $clearSearchParams['sort'] =
        $sort;
}

$clearSearchUrl =
    '/product-catalog/index.php';

if (!empty($clearSearchParams)) {

    $clearSearchUrl .=
        '?' . http_build_query(
            $clearSearchParams
        );
}

$clearSearchUrl .=
    '#catalog';


/*
|--------------------------------------------------------------------------
| CLEAR CATEGORY URL
|--------------------------------------------------------------------------
*/

$clearCategoryParams = [];

if ($search !== '') {
    $clearCategoryParams['q'] =
        $search;
}

if ($sort !== 'newest') {
    $clearCategoryParams['sort'] =
        $sort;
}

$clearCategoryUrl =
    '/product-catalog/index.php';

if (!empty($clearCategoryParams)) {

    $clearCategoryUrl .=
        '?' . http_build_query(
            $clearCategoryParams
        );
}

$clearCategoryUrl .=
    '#catalog';


/*
|--------------------------------------------------------------------------
| RESET SORT URL
|--------------------------------------------------------------------------
*/

$resetSortParams = [];

if ($search !== '') {
    $resetSortParams['q'] =
        $search;
}

if ($categoryId) {
    $resetSortParams['category'] =
        (int) $categoryId;
}

$resetSortUrl =
    '/product-catalog/index.php';

if (!empty($resetSortParams)) {

    $resetSortUrl .=
        '?' . http_build_query(
            $resetSortParams
        );
}

$resetSortUrl .=
    '#catalog';

?>


<?php require __DIR__ . '/includes/header.php'; ?>


<!-- ==================================================
     HERO
================================================== -->

<section class="hero">

    <div class="hero-content">

        <span class="eyebrow">
            PREMIUM PRODUCT COLLECTION
        </span>

        <h1>
            Discover products designed for everyday life.
        </h1>

        <p class="hero-text">
            Explore our curated collection of quality products,
            carefully selected for modern living.
        </p>

    </div>

</section>


<!-- ==================================================
     CATALOG
================================================== -->

<section
    class="catalog-section"
    id="catalog"
>


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
         CATALOG HEADING
    ================================================== -->

    <div class="catalog-toolbar">

        <div class="catalog-heading">

            <p class="section-label">
                COLLECTION
            </p>

            <h2 class="section-title">
                <?= htmlspecialchars(
                    $catalogTitle
                ) ?>
            </h2>

            <p class="product-count">
                <?= htmlspecialchars(
                    $catalogSubtitle
                ) ?>
            </p>


            <?php if (
                $search !== ''
                || $activeCategoryName !== null
            ): ?>

                <p class="catalog-context">

                    <?php if (
                        $search !== ''
                        && $activeCategoryName !== null
                    ): ?>

                        Showing results for
                        "<strong><?= htmlspecialchars(
                            $search
                        ) ?></strong>"
                        in
                        <strong><?= htmlspecialchars(
                            $activeCategoryName
                        ) ?></strong>

                    <?php elseif ($search !== ''): ?>

                        Showing results for
                        "<strong><?= htmlspecialchars(
                            $search
                        ) ?></strong>"

                    <?php elseif (
                        $activeCategoryName !== null
                    ): ?>

                        Showing category
                        <strong><?= htmlspecialchars(
                            $activeCategoryName
                        ) ?></strong>

                    <?php endif; ?>

                </p>

            <?php endif; ?>

        </div>

    </div>


    <!-- ==================================================
         CATALOG CONTROLS
    ================================================== -->

    <div class="catalog-controls">


        <!-- ==================================================
             CATEGORY CHIPS
        ================================================== -->

        <div class="category-chips">

            <a
                href="<?= htmlspecialchars(
                    $allProductsUrl
                ) ?>"
                class="category-chip <?= !$categoryId
                    ? 'active'
                    : '' ?>"
            >
                All
            </a>


            <?php foreach ($categories as $category): ?>

                <?php

                $categoryUrlParams =
                    $categoryQuery;

                $categoryUrlParams['category'] =
                    (int) $category['id'];

                $categoryUrl =
                    '/product-catalog/index.php?'
                    . http_build_query(
                        $categoryUrlParams
                    )
                    . '#catalog';

                ?>

                <a
                    href="<?= htmlspecialchars(
                        $categoryUrl
                    ) ?>"
                    class="category-chip <?= $categoryId === (int) $category['id']
                        ? 'active'
                        : '' ?>"
                >
                    <?= htmlspecialchars(
                        $category['name']
                    ) ?>
                </a>

            <?php endforeach; ?>

        </div>


        <!-- ==================================================
             SEARCH & SORT
        ================================================== -->

        <form
            class="search-form-premium"
            action="/product-catalog/index.php#catalog"
            method="get"
        >


            <?php if ($categoryId): ?>

                <input
                    type="hidden"
                    name="category"
                    value="<?= (int) $categoryId ?>"
                >

            <?php endif; ?>


            <input
                type="search"
                name="q"
                placeholder="Search products..."
                value="<?= htmlspecialchars(
                    $search
                ) ?>"
                class="<?= $search !== ''
                    ? 'search-active'
                    : '' ?>"
                aria-label="Search products"
            >


            <select
                name="sort"
                aria-label="Sort products"
                onchange="this.form.submit()"
            >

                <option
                    value="newest"
                    <?= $sort === 'newest'
                        ? 'selected'
                        : '' ?>
                >
                    Newest
                </option>

                <option
                    value="price_asc"
                    <?= $sort === 'price_asc'
                        ? 'selected'
                        : '' ?>
                >
                    Price: Low to High
                </option>

                <option
                    value="price_desc"
                    <?= $sort === 'price_desc'
                        ? 'selected'
                        : '' ?>
                >
                    Price: High to Low
                </option>

                <option
                    value="name_asc"
                    <?= $sort === 'name_asc'
                        ? 'selected'
                        : '' ?>
                >
                    Name: A–Z
                </option>

                <option
                    value="name_desc"
                    <?= $sort === 'name_desc'
                        ? 'selected'
                        : '' ?>
                >
                    Name: Z–A
                </option>

            </select>


            <button type="submit">
                Search
            </button>


            <?php if ($search !== ''): ?>

                <a
                    href="<?= htmlspecialchars(
                        $clearSearchUrl
                    ) ?>"
                    class="clear-search"
                >
                    Clear search
                </a>

            <?php endif; ?>


            <?php if (
                $categoryId
                && $search === ''
            ): ?>

                <a
                    href="<?= htmlspecialchars(
                        $clearCategoryUrl
                    ) ?>"
                    class="clear-search"
                >
                    Clear category
                </a>

            <?php endif; ?>


            <?php if ($sort !== 'newest'): ?>

                <a
                    href="<?= htmlspecialchars(
                        $resetSortUrl
                    ) ?>"
                    class="clear-search"
                >
                    Reset sort
                </a>

            <?php endif; ?>

        </form>

    </div>


    <!-- ==================================================
         EMPTY STATE
    ================================================== -->

    <?php if (empty($products)): ?>

        <div class="empty-state">

            <h3>
                No products found
            </h3>


            <p>

                <?php if (
                    $search !== ''
                    && $categoryId
                    && $activeCategoryName !== null
                ): ?>

                    No products match
                    "<strong><?= htmlspecialchars(
                        $search
                    ) ?></strong>"
                    in
                    <strong><?= htmlspecialchars(
                        $activeCategoryName
                    ) ?></strong>.


                <?php elseif ($search !== ''): ?>

                    No products match
                    "<strong><?= htmlspecialchars(
                        $search
                    ) ?></strong>".


                <?php elseif (
                    $categoryId
                    && $activeCategoryName !== null
                ): ?>

                    There are no products in
                    <strong><?= htmlspecialchars(
                        $activeCategoryName
                    ) ?></strong>.


                <?php else: ?>

                    There are currently no products available.

                <?php endif; ?>

            </p>


            <a
                href="/product-catalog/index.php#catalog"
                class="empty-button"
            >
                View All Products
            </a>

        </div>


    <?php else: ?>


        <!-- ==================================================
             PRODUCT GRID
        ================================================== -->

        <div class="grid">


            <?php foreach ($products as $product): ?>


                <?php

                $stockStatus = get_stock_status(
                    (int) $product['stock']
                );

                ?>


                <article class="card">


                    <!-- ==================================================
                         PRODUCT IMAGE
                    ================================================== -->

                    <div class="card-image">

                        <a
                            href="/product-catalog/product.php?id=<?= (int) $product['id'] ?>"
                            class="card-image-link"
                            aria-label="View <?= htmlspecialchars(
                                $product['name']
                            ) ?>"
                        >

                            <img
                                src="<?= htmlspecialchars(
                                    $product['image_url']
                                    ?: 'https://placehold.co/600x450?text=No+Image'
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $product['name']
                                ) ?>"
                            >

                        </a>


                        <!-- ==================================================
                             BADGES
                        ================================================== -->

                        <div class="card-badges">


                            <?php if (
                                (int) $product['featured'] === 1
                            ): ?>

                                <span class="badge badge-featured">
                                    Featured
                                </span>

                            <?php endif; ?>


                            <?php if (
                                !empty($product['category_name'])
                                && !empty($product['category_id'])
                            ): ?>

                                <?php

                                $badgeParams = [
                                    'category' =>
                                        (int) $product['category_id'],
                                ];

                                if ($search !== '') {
                                    $badgeParams['q'] =
                                        $search;
                                }

                                if ($sort !== 'newest') {
                                    $badgeParams['sort'] =
                                        $sort;
                                }

                                $badgeUrl =
                                    '/product-catalog/index.php?'
                                    . http_build_query(
                                        $badgeParams
                                    )
                                    . '#catalog';

                                ?>

                                <a
                                    href="<?= htmlspecialchars(
                                        $badgeUrl
                                    ) ?>"
                                    class="badge badge-category badge-category-link"
                                >
                                    <?= htmlspecialchars(
                                        $product['category_name']
                                    ) ?>
                                </a>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- ==================================================
                         PRODUCT CONTENT
                    ================================================== -->

                    <div class="card-body">


                        <h3>

                            <a
                                href="/product-catalog/product.php?id=<?= (int) $product['id'] ?>"
                                class="card-title-link"
                            >
                                <?= htmlspecialchars(
                                    $product['name']
                                ) ?>
                            </a>

                        </h3>


                        <p>
                            <?= htmlspecialchars(
                                $product['description']
                            ) ?>
                        </p>


                        <!-- ==================================================
                             STOCK STATUS
                        ================================================== -->

                        <div class="inventory-status">

                            <span
                                class="stock <?= htmlspecialchars(
                                    $stockStatus['class']
                                ) ?>"
                            >
                                <?= htmlspecialchars(
                                    $stockStatus['label']
                                ) ?>
                            </span>

                        </div>


                        <!-- ==================================================
                             PRODUCT FOOTER
                        ================================================== -->

                        <div class="card-footer">

                            <span class="price">
                                £<?= number_format(
                                    (float) $product['price'],
                                    2
                                ) ?>
                            </span>

                            <a
                                href="/product-catalog/product.php?id=<?= (int) $product['id'] ?>"
                                class="view-product"
                            >
                                View →
                            </a>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


<?php require __DIR__ . '/includes/footer.php'; ?>