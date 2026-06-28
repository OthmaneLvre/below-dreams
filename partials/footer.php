<footer class="site-footer">
    <div class="container footer-inner">

        <div class="footer-top">
            <img src="<?= $basePath ?? '' ?>assets/logos/below-dreams-white.svg"
                alt="Below Dreams" 
                class="footer-logo"    
            >

            <nav class="footer-nav" aria-label="Liens légaux">
                <a href="<?= $basePath ?? '' ?>docs/mentions-legales.html">Mentions légales</a>
                <a href="<?= $basePath ?? '' ?>docs/cgv.html">CGV</a>
                <a href="<?= $basePath ?? '' ?>docs/politique-confidentialite.html">Politique de confidentialité</a>
                <a href="<?= $basePath ?? '' ?>contact.html">Contact</a>
            </nav>
        </div>

        <div class="footer-bottom">
            <p>© Below Dreams — Tous droits réservés</p>
            <p>
                Développé par 
                <a href="https://olcreativestudio.fr" target="_blank" rel="noopener noreferrer">
                    OL Creative Studio
                </a>
            </p>
        </div>
    
    </div>
</footer>

<script src="<?= $basePath ?? '' ?>js/main.js" defer></script>
<script src="<?= $basePath ?? '' ?>js/cart.js" defer></script>

</body>
</html>