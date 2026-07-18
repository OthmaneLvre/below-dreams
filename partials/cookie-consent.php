<div
    class="cookie-consent"
    id="cookie-consent"
    role="dialog"
    aria-modal="true"
    aria-labelledby="cookie-consent-title"
    aria-describedby="cookie-consent-description"
    hidden
>
    <div
        class="cookie-consent-overlay"
        data-cookie-close
    ></div>

    <div class="cookie-consent-panel">

        <button
            type="button"
            class="cookie-consent-close"
            id="cookie-consent-close"
            aria-label="Fermer la fenêtre de gestion des cookies"
            hidden
        >
            ×
        </button>

        <div
            class="cookie-consent-view"
            id="cookie-consent-main-view"
        >
            <p class="cookie-consent-eyebrow">
                Respect de votre vie privée
            </p>

            <h2 id="cookie-consent-title">
                Gestion des cookies
            </h2>

            <p
                class="cookie-consent-description"
                id="cookie-consent-description"
            >
                Below Dreams utilise des cookies strictement nécessaires
                au fonctionnement du site. Avec votre accord, des cookies
                facultatifs pourront également être utilisés pour mesurer
                l’audience ou améliorer les campagnes publicitaires.
            </p>

            <p class="cookie-consent-details-link">
                Consultez notre
                <a href="<?= $basePath ?? '' ?>politique-cookies.php">
                    politique relative aux cookies
                </a>.
            </p>

            <div class="cookie-consent-actions">

                <button
                    type="button"
                    class="btn-primary cookie-consent-accept"
                    id="cookie-accept-all"
                >
                    Tout accepter
                </button>

                <button
                    type="button"
                    class="cookie-consent-button"
                    id="cookie-refuse-all"
                >
                    Tout refuser
                </button>

                <button
                    type="button"
                    class="cookie-consent-button"
                    id="cookie-customize"
                >
                    Personnaliser
                </button>

            </div>
        </div>

        <div
            class="cookie-consent-view"
            id="cookie-consent-settings-view"
            hidden
        >
            <button
                type="button"
                class="cookie-consent-back"
                id="cookie-settings-back"
            >
                ← Retour
            </button>

            <h2>Personnaliser mes choix</h2>

            <p class="cookie-consent-description">
                Vous pouvez accepter ou refuser chaque catégorie facultative.
                Les cookies nécessaires restent toujours actifs.
            </p>

            <div class="cookie-preferences">

                <div class="cookie-preference">

                    <div class="cookie-preference-content">
                        <h3>Cookies nécessaires</h3>

                        <p>
                            Indispensables à la connexion, au panier,
                            au checkout, à la sécurité et à la mémorisation
                            de vos choix.
                        </p>
                    </div>

                    <span class="cookie-preference-required">
                        Toujours actifs
                    </span>

                </div>

                <label class="cookie-preference">

                    <div class="cookie-preference-content">
                        <h3>Mesure d’audience</h3>

                        <p>
                            Permettrait de mesurer la fréquentation du site
                            et d’identifier les pages les plus consultées.
                        </p>
                    </div>

                    <span class="cookie-switch">
                        <input
                            type="checkbox"
                            id="cookie-analytics"
                            name="cookie_analytics"
                        >

                        <span
                            class="cookie-switch-control"
                            aria-hidden="true"
                        ></span>
                    </span>

                </label>

                <label class="cookie-preference">

                    <div class="cookie-preference-content">
                        <h3>Publicité et marketing</h3>

                        <p>
                            Permettrait de mesurer les campagnes publicitaires
                            et de proposer des contenus plus pertinents.
                        </p>
                    </div>

                    <span class="cookie-switch">
                        <input
                            type="checkbox"
                            id="cookie-marketing"
                            name="cookie_marketing"
                        >

                        <span
                            class="cookie-switch-control"
                            aria-hidden="true"
                        ></span>
                    </span>

                </label>

            </div>

            <div class="cookie-consent-actions">

                <button
                    type="button"
                    class="btn-primary"
                    id="cookie-save-settings"
                >
                    Enregistrer mes choix
                </button>

                <button
                    type="button"
                    class="cookie-consent-button"
                    id="cookie-settings-refuse-all"
                >
                    Tout refuser
                </button>

            </div>
        </div>

    </div>
</div>