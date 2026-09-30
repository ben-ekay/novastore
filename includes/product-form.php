<?php
/*
|--------------------------------------------------------------------------
| EXPECTED VARIABLES
|--------------------------------------------------------------------------
|
| $formAction
| $submitLabel
| $cancelUrl
| $values
| $categories
| $errors
| $currentImage
|
*/
?>

<div class="product-form-card">

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


    <form
        class="product-form"
        method="post"
        action="<?= htmlspecialchars($formAction) ?>"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(csrf_token()) ?>"
        >


        <div class="form-group">

            <label for="name">
                Product name
            </label>

            <input
                type="text"
                id="name"
                name="name"
                value="<?= htmlspecialchars($values['name'] ?? '') ?>"
                placeholder="e.g. Wireless Keyboard"
                required
            >

        </div>


        <div class="form-group">

            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                rows="5"
                placeholder="Describe the product..."
                required
            ><?= htmlspecialchars($values['description'] ?? '') ?></textarea>

        </div>


        <div class="form-group">

            <label for="category_id">
                Category
            </label>

            <select
                id="category_id"
                name="category_id"
                required
            >

                <option value="">
                    Select a category
                </option>

                <?php foreach ($categories as $category): ?>

                    <option
                        value="<?= (int) $category['id'] ?>"
                        <?= (string) $category['id'] === ($values['category_id'] ?? '')
                            ? 'selected'
                            : '' ?>
                    >
                        <?= htmlspecialchars($category['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <div class="form-row">

            <div class="form-group">

                <label for="price">
                    Price
                </label>

                <div class="price-input">

                    <span>
                        £
                    </span>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        step="0.01"
                        min="0"
                        value="<?= htmlspecialchars($values['price'] ?? '') ?>"
                        placeholder="0.00"
                        required
                    >

                </div>

            </div>


            <div class="form-group">

                <label for="stock">
                    Stock
                </label>

                <input
                    type="number"
                    id="stock"
                    name="stock"
                    min="0"
                    step="1"
                    value="<?= htmlspecialchars($values['stock'] ?? '') ?>"
                    placeholder="0"
                    required
                >

            </div>

        </div>


        <?php if (!empty($currentImage)): ?>

            <div class="current-image-panel">

                <span class="field-help">
                    Current image
                </span>

                <img
                    src="<?= htmlspecialchars($currentImage) ?>"
                    alt="<?= htmlspecialchars(
                        'Current image for '
                        . ($values['name'] ?? 'product')
                    ) ?>"
                >

            </div>

        <?php endif; ?>

        <?php if (!empty($returnUrl)): ?>

    <input
        type="hidden"
        name="return"
        value="<?= htmlspecialchars($returnUrl) ?>"
    >

<?php endif; ?>


        <div class="form-group">

            <label for="product_image">
                <?= !empty($currentImage)
                    ? 'Replace product image'
                    : 'Product image' ?>
            </label>

            <input
                type="file"
                id="product_image"
                name="product_image"
                accept="image/jpeg,image/png,image/webp"
            >

            <span class="field-help">

                <?php if (!empty($currentImage)): ?>
                    Leave empty to keep the current image.
                <?php endif; ?>

                JPG, PNG or WebP. Maximum 5 MB.

            </span>

        </div>


        <label class="featured-option">

            <input
                type="checkbox"
                name="featured"
                value="1"
                <?= ($values['featured'] ?? '0') === '1'
                    ? 'checked'
                    : '' ?>
            >

            <span>

                <strong>
                    Featured product
                </strong>

                <small>
                    Highlight this product in the NovaStore collection.
                </small>

            </span>

        </label>


        <div class="form-actions">

            <a
                href="<?= htmlspecialchars($cancelUrl) ?>"
                class="button-secondary"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="button-primary"
            >
                <?= htmlspecialchars($submitLabel) ?>
            </button>

        </div>

    </form>

</div>