console.log("shop.js loaded");

/* ==========================================================
   SHOP — SORT (featured toggle + price select)
   ========================================================== */

document.addEventListener("DOMContentLoaded", () => {
  const shopGrid = document.querySelector(".shop-grid");
  const countValue = document.querySelector(".shop-count span");

  const featuredBtn = document.getElementById("sort-featured");
  const priceSelect = document.getElementById("sort-select");

  // Page sans boutique => on ne fait rien
  if (!shopGrid || (!featuredBtn && !priceSelect)) return;

  const getCards = () => Array.from(shopGrid.querySelectorAll(".product-card"));

  const toBool = (v) => String(v).toLowerCase() === "true";
  const toNumber = (v) => {
    const n = Number(v);
    return Number.isFinite(n) ? n : 0;
  };

  const updateCount = () => {
    if (!countValue) return;
    const visibleCount = getCards().filter((card) => !card.hasAttribute("hidden")).length;
    countValue.textContent = String(visibleCount);
  };

  // État tri
  const state = {
    featuredFirst: featuredBtn ? featuredBtn.getAttribute("aria-pressed") === "true" : false,
    priceMode: priceSelect ? priceSelect.value : "default", // default | price-asc | price-desc
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
    const cards = getCards();
    const sorted = cards.sort(compareCards);
    sorted.forEach((card) => shopGrid.appendChild(card));
    updateCount();
  };

  // Init count + sort
  updateCount();
  applySort();

  // Featured toggle button
  if (featuredBtn) {
    featuredBtn.addEventListener("click", () => {
      const next = !(featuredBtn.getAttribute("aria-pressed") === "true");
      featuredBtn.setAttribute("aria-pressed", String(next));
      state.featuredFirst = next;

      applySort();
    });
  }

  // Price select
  if (priceSelect) {
    priceSelect.addEventListener("change", (e) => {
      state.priceMode = e.target.value;
      applySort();
    });
  }
});