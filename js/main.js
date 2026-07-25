/* ==========================================================
   NAV — MENU BURGER (accessible + robuste)
   ========================================================== */

document.addEventListener("DOMContentLoaded", () => {
  const toggleBtn = document.querySelector(".nav-toggle");
  const menu = document.getElementById("nav-menu");
  const nav = document.querySelector(".nav");

  // Si la page n'a pas de burger/menu, on ne fait rien
  if (!toggleBtn || !menu || !nav) return;

  /**
   * Définit l'état du menu
   * @param {boolean} open
   */
  const setMenuState = (open) => {
    toggleBtn.setAttribute("aria-expanded", String(open));
    toggleBtn.classList.toggle("is-open", open);
    menu.classList.toggle("is-open", open);

    // UX : bloquer le scroll + état global
    document.body.classList.toggle("nav-open", open);
  };

  /**
   * Retourne true si le menu est ouvert
   * @returns {boolean}
   */
  const isMenuOpen = () => toggleBtn.getAttribute("aria-expanded") === "true";

  // État initial : fermé
  setMenuState(false);

  // Toggle sur clic burger
  toggleBtn.addEventListener("click", (e) => {
    e.stopPropagation(); // évite fermeture immédiate via click-outside
    setMenuState(!isMenuOpen());
  });

  // Fermer au clic sur un lien du menu
  menu.addEventListener("click", (e) => {
    if (e.target?.classList?.contains("nav-link")) {
      setMenuState(false);
    }
  });

  // Fermer au clic en dehors du menu (mobile)
  document.addEventListener("click", (e) => {
    if (!isMenuOpen()) return;

    const clickedInsideNav = nav.contains(e.target);
    if (!clickedInsideNav) setMenuState(false);
  });

  // Fermer avec Escape
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") setMenuState(false);
  });

  // Empêcher le menu de rester ouvert quand on repasse en desktop
  window.addEventListener("resize", () => {
    // Breakpoint aligné avec le CSS (à adapter si tu choisis un autre)
    if (window.innerWidth >= 900 && isMenuOpen()) {
      setMenuState(false);
    }
  });
});