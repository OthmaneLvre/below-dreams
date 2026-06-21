console.log("shop.js loaded");

/* ==========================================================
   SHOP — SORT (featured toggle + price select)
   ========================================================== */

document.addEventListener("DOMContentLoaded", () => {
  const shopGrid = document.querySelector(".shop-grid");
  const countValue = document.querySelector(".shop-count span");

  const featuredBtn = document.getElementById("sort-featured");
  const priceSelect = document.getElementById("sort-select");
  const resetBtn = document.querySelector(".shop-reset");

  const filterInputs = Array.from(
    document.querySelectorAll('.shop-filters input[type="checkbox"]')
  );

  // Page sans boutique => on ne fait rien
  if (!shopGrid) return;

  const getCards = () => Array.from(shopGrid.querySelectorAll(".product-card"));

  const toBool = (v) => String(v).toLowerCase() === "true";
  const toNumber = (v) => {
    const n = Number(v);
    return Number.isFinite(n) ? n : 0;
  };

  const getCheckedValues = (name) =>
    filterInputs
        .filter((i) => i.name === name && i.checked)
        .map((i) => i.value);

  const updateCount = () => {
    if (!countValue) return;
    const visibleCount = getCards().filter((card) => !card.hasAttribute("hidden")).length;
    countValue.textContent = String(visibleCount);
  };

  // État gloabal
  const state = {
    featuredFirst: featuredBtn ? featuredBtn.getAttribute("aria-pressed") === "true" : false,
    priceMode: priceSelect ? priceSelect.value : "default", // default | price-asc | price-desc
  };

  /* FILTERS */ 

    const cardMatchesFilters = (card) => {
        const selectedCategories = getCheckedValues("category");
        const selectedAvailability = getCheckedValues("availability");
        const selectedSizes = getCheckedValues("size");

        const cardCategory = (card.dataset.category || "")
            .toLowerCase()
            .split("|")
            .map((category) => category.trim())
            .filter(Boolean);
        const cardAvailability = (card.dataset.availability || "").toLowerCase();
        const cardSizes = (card.dataset.sizes || "")
        .toLowerCase()
        .split(",")
        .map((s) => s.trim())
        .filter(Boolean);

        // Categorie : le produit doit correspondre à au moins une catégorie cochée
        if (selectedCategories.length > 0) {
            const hasOneCategory = selectedCategories.some((category) =>
                cardCategory.includes(category)
            );

            if (!hasOneCategory) return false;
        } 

        // Availability: idem
        if (selectedAvailability.length > 0 && !selectedAvailability.includes(cardAvailability)) {
            return false;
        }

        // Sizes: si tailles cochées, le produit doit proposer AU MOINS une des tailles
        if (selectedSizes.length > 0) {
            const hasOneSize = selectedSizes.some((size) => cardSizes.includes(size));
            if (!hasOneSize) return false;
        }

        return true;
    };

    const applyFilters = () => {
        const cards = getCards();
        cards.forEach((card) => {
            const match = cardMatchesFilters(card);
            if (match) card.removeAttribute("hidden");
            else card.setAttribute("hidden", "");
        });
        updateCount();
    };


  /**
   * Compare two cards according to current state
   * Featured (optional) then price sort (optional)
   */
  const compareCards = (a, b) => {
    const aFeatured = toBool(a.dataset.featured);
    const bFeatured = toBool(b.dataset.featured);

    const aPrice = toNumber(a.dataset.price);
    const bPrice = toNumber(b.dataset.price);

    // 1) Featured priority (if enabled)
    if (state.featuredFirst && aFeatured !== bFeatured) {
      return aFeatured ? -1 : 1;
    }

    // 2) Price sort (if selected)
    if (state.priceMode === "price-asc") return aPrice - bPrice;
    if (state.priceMode === "price-desc") return bPrice - aPrice;

    // 3) Stable fallback: keep DOM order as much as possible
    return 0;
  };

  const applySort = () => {
    // Important : ne trier que les cartes visibles ou tout trier, au choix.
    // Ici : on trie TOUT, mais hidden reste hidden.
    const cards = getCards();
    cards.sort(compareCards).forEach((card) => shopGrid.appendChild(card));
  };

  /* --------------------------
     PIPELINE (filter -> sort -> count)
     -------------------------- */

    const refresh = () => {
        applyFilters();
        applySort();
        updateCount();
    };

    // Init
    refresh();

    // Listeners filtres
    filterInputs.forEach((input) => {
        input.addEventListener("change", () => {
            refresh();
        });
    });

    // Featured toggle button
    if (featuredBtn) {
        featuredBtn.addEventListener("click", () => {
            const next = !(featuredBtn.getAttribute("aria-pressed") === "true");
            featuredBtn.setAttribute("aria-pressed", String(next));
            state.featuredFirst = next;
            refresh();
        });
    }

    // Price select
    if (priceSelect) {
        priceSelect.addEventListener("change", (e) => {
            state.priceMode = e.target.value;
            refresh();
        });
    }

    // Reset 
    if (resetBtn) {
        resetBtn.addEventListener("click", () => {
            // 1) reset checkboxes
            filterInputs.forEach((i) => (i.checked = false));

            // 2) reset sort states
            if (featuredBtn) {
                featuredBtn.setAttribute("aria-pressed", "false");
                state.featuredFirst = false;
            }
            if (priceSelect) {
                priceSelect.value = "default";
                state.priceMode = "default";
            }

            // 3) refresh 
            refresh();
        });
    }
});