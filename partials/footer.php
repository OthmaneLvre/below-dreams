<footer class="site-footer">
    <div class="container footer-inner">

        <div class="footer-top">
            <img
                src="<?= $basePath ?? '' ?>assets/logos/below-dreams-white.svg"
                alt="Below Dreams"
                class="footer-logo"
            >

            <nav class="footer-nav" aria-label="Liens légaux et informations">
                <a href="<?= $basePath ?? '' ?>mentions-legales.php">
                    Mentions légales
                </a>

                <a href="<?= $basePath ?? '' ?>cgv.php">
                    CGV
                </a>

                <a href="<?= $basePath ?? '' ?>politique-confidentialite.php">
                    Confidentialité
                </a>

                <a href="<?= $basePath ?? '' ?>politique-cookies.php">
                    Cookies
                </a>

                <a href="<?= $basePath ?? '' ?>retractation.php">
                    Rétractation
                </a>

                <button
                    type="button"
                    class="footer-cookie-settings"
                    id="open-cookie-settings"
                >
                    Gérer mes cookies
                </button>

                <a href="<?= $basePath ?? '' ?>contact.php">
                    Contact
                </a>
            </nav>
        </div>

        <div class="footer-bottom">
            <p>
                © <?= date('Y') ?> Below Dreams — Tous droits réservés
            </p>

            <p>
                Développé par
                <a
                    href="https://olcreativestudio.fr"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    OL Creative Studio
                </a>
            </p>
        </div>

    </div>
</footer>

<?php require __DIR__ . '/cookie-consent.php'; ?>


<script src="<?= $basePath ?? '' ?>js/main.js" defer></script>
<script src="<?= $basePath ?? '' ?>js/cart.js" defer></script>
<script src="<?= $basePath ?? '' ?>js/cookie-consent.js" defer></script>

</body>
</html>