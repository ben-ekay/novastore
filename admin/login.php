<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';

if (admin_is_logged_in()) {
    header('Location: /product-catalog/admin/');
    exit;
}

$pdo = get_db();

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT id, name, email, password, role
         FROM users
         WHERE email = :email
         LIMIT 1'
    );

    $stmt->execute([
        'email' => $email
    ]);

    $user = $stmt->fetch();

    if (
        $user
        && password_verify($password, $user['password'])
        && $user['role'] === 'admin'
    ) {

        admin_login($user);

        header('Location: /product-catalog/admin/');
        exit;

    } else {

        $error = 'Invalid email or password.';
    }
}
?>

<?php require __DIR__ . '/../includes/header.php'; ?>

<section class="admin-login-page">

    <div class="admin-login-card">

        <div class="admin-login-heading">

            <span class="eyebrow">
                NOVASTORE ADMIN
            </span>

            <h1>Welcome back</h1>

            <p>
                Sign in to manage products,
                inventory and your NovaStore catalog.
            </p>

        </div>

        <?php if ($error !== ''): ?>

            <div class="error-box">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form
            method="post"
            action="/product-catalog/admin/login.php"
            class="product-form"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(csrf_token()) ?>"
            >

            <div class="form-group">

                <label for="email">
                    Email address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    autocomplete="email"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >

            </div>

            <button
                type="submit"
                class="button-primary admin-login-button"
            >
                Sign In
            </button>

        </form>

    </div>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>