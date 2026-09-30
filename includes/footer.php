</main>

<footer class="site-footer">
    <div class="footer-container">

        <div>
            <strong>NovaStore</strong>
            <p>Modern product management made simple.</p>
        </div>

        <p class="footer-copy">
            &copy; <?= date('Y') ?> NovaStore
        </p>

    </div>
</footer>

<script>
document.addEventListener('click', function (event) {

    document
        .querySelectorAll('.admin-actions-menu details[open]')
        .forEach(function (menu) {

            if (!menu.contains(event.target)) {
                menu.removeAttribute('open');
            }

        });

});
</script>

<script>
document
    .querySelectorAll('.admin-actions-menu details')
    .forEach(function (menu) {

        menu.addEventListener('toggle', function () {

            if (!menu.open) {
                return;
            }

            document
                .querySelectorAll('.admin-actions-menu details[open]')
                .forEach(function (otherMenu) {

                    if (otherMenu !== menu) {
                        otherMenu.removeAttribute('open');
                    }

                });

        });

    });
</script>

<script>
document.addEventListener('click', function (event) {

    const openMenus = document.querySelectorAll(
        '.admin-actions-menu details[open]'
    );

    openMenus.forEach(function (menu) {

        if (!menu.contains(event.target)) {
            menu.removeAttribute('open');
        }

    });

});

document.querySelectorAll(
    '.admin-actions-menu details'
).forEach(function (menu) {

    menu.addEventListener('toggle', function () {

        if (!menu.open) {
            return;
        }

        document.querySelectorAll(
            '.admin-actions-menu details[open]'
        ).forEach(function (otherMenu) {

            if (otherMenu !== menu) {
                otherMenu.removeAttribute('open');
            }

        });

    });

});
</script>

</body>
</html>