console.log("shop.js loaded");

document.addEventListener("DOMContentLoaded", () => {
    const shopGrid = document.querySelector(".shop-grid");
    const countValue = document.querySelector(".shop-count span");

    const featuredBtn = document.getElementById("sort-featured");
    const priceSelect = document.getElementById("sort-select");
    const resetBtn = document.querySelector(".shop-reset");

    const filterInputs = Array.from(
        document.querySelectorAll('.shop-filters input[type="checkbox"]')
    );

    if (!shopGrid) return;

    const getCards = () => Array.from(shopGrid.querySelectorAll(".product-card"));

    const toBool = (value) => String(value).toLowerCase() === "true";

    const toNumber = (value) => {
        const number = Number(value);
        return Number.isFinite(number) ? number : 0;
    };

    const getCheckedValues = (name) => {
        return filterInputs
            .filter((input) => input.name === name && input.checked)
            .map((input) => input.value.toLowerCase());
    };

    const state = {
        featuredOnly: false,
        priceMode: priceSelect ? priceSelect.value : "default",
    };

    const cardMatchesFilters = (card) => {
        const selectedCategories = getCheckedValues("category");
        const selectedAvailability = getCheckedValues("availability");
        const selectedSizes = getCheckedValues("size");

        const cardFeatured = toBool(card.dataset.featured);

        const cardCategory = (card.dataset.category || "")
            .toLowerCase()
            .split("|")
            .map((category) => category.trim())
            .filter(Boolean);

        const cardAvailability = (card.dataset.availability || "").toLowerCase();

        const cardSizes = (card.dataset.sizes || "")
            .toLowerCase()
            .split(",")
            .map((size) => size.trim())
            .filter(Boolean);

        if (state.featuredOnly && !cardFeatured) {
            return false;
        }

        if (selectedCategories.length > 0) {
            const hasCategory = selectedCategories.some((category) =>
                cardCategory.includes(category)
            );

            if (!hasCategory) return false;
        }

        if (
            selectedAvailability.length > 0 &&
            !selectedAvailability.includes(cardAvailability)
        ) {
            return false;
        }

        if (selectedSizes.length > 0) {
            const hasSize = selectedSizes.some((size) => cardSizes.includes(size));

            if (!hasSize) return false;
        }

        return true;
    };

    const applyFilters = () => {
        getCards().forEach((card) => {
            if (cardMatchesFilters(card)) {
                card.removeAttribute("hidden");
            } else {
                card.setAttribute("hidden", "");
            }
        });
    };

    const compareCards = (a, b) => {
        const aPrice = toNumber(a.dataset.price);
        const bPrice = toNumber(b.dataset.price);

        if (state.priceMode === "price-asc") return aPrice - bPrice;
        if (state.priceMode === "price-desc") return bPrice - aPrice;

        return 0;
    };

    const applySort = () => {
        getCards()
            .sort(compareCards)
            .forEach((card) => shopGrid.appendChild(card));
    };

    const updateCount = () => {
        if (!countValue) return;

        const visibleCount = getCards().filter(
            (card) => !card.hasAttribute("hidden")
        ).length;

        countValue.textContent = String(visibleCount);
    };

    const refresh = () => {
        applyFilters();
        applySort();
        updateCount();
    };

    filterInputs.forEach((input) => {
        input.addEventListener("change", refresh);
    });

    if (featuredBtn) {
        featuredBtn.addEventListener("click", () => {
            state.featuredOnly = !state.featuredOnly;
            featuredBtn.setAttribute("aria-pressed", String(state.featuredOnly));
            refresh();
        });
    }

    if (priceSelect) {
        priceSelect.addEventListener("change", (event) => {
            state.priceMode = event.target.value;
            refresh();
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener("click", () => {
            filterInputs.forEach((input) => {
                input.checked = false;
            });

            state.featuredOnly = false;

            if (featuredBtn) {
                featuredBtn.setAttribute("aria-pressed", "false");
            }

            if (priceSelect) {
                priceSelect.value = "default";
                state.priceMode = "default";
            }

            refresh();
        });
    }

    refresh();
});