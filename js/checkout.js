document.addEventListener("DOMContentLoaded", () => {
  console.log("checkout.js loaded");

  const CART_KEY = "belowdreams_cart";

  const checkoutItems = document.getElementById("checkout-items");
  const subtotalElement = document.getElementById("checkout-subtotal");
  const totalElement = document.getElementById("checkout-total");
  const checkoutForm = document.getElementById("checkout-form");
  const checkoutCartInput = document.getElementById("checkout-cart-input");

  const getCart = () => {
    try {
      return JSON.parse(localStorage.getItem(CART_KEY)) || [];
    } catch (error) {
      return [];
    }
  };

  const formatPrice = (price) => {
    return Number(price).toFixed(2).replace(".", ",") + " €";
  };

  if (checkoutForm && checkoutCartInput) {
    checkoutForm.addEventListener("submit", (event) => {
      const cart = getCart();

      if (cart.length === 0) {
        event.preventDefault();
        alert("Votre panier est vide.");
        window.location.href = "cart.php";
        return;
      }

      checkoutCartInput.value = JSON.stringify(cart);
      console.log("Panier envoyé :", cart);
    });
  }

  if (!checkoutItems) return;

  const cart = getCart();

  if (cart.length === 0) {
    checkoutItems.innerHTML = `
      <div class="cart-empty">
        <h2>Votre panier est vide</h2>
        <p>Ajoutez un produit avant de finaliser votre commande.</p>
        <a href="shop.php" class="btn-primary">Retour à la boutique</a>
      </div>
    `;

    return;
  }

  let subtotal = 0;
  checkoutItems.innerHTML = "";

  cart.forEach((item) => {
    const itemTotal = item.price * item.quantity;
    subtotal += itemTotal;

    checkoutItems.innerHTML += `
      <article class="checkout-item">
        <img src="${item.image}" alt="${item.name}" class="checkout-item-img">

        <div>
          <h3>${item.name}</h3>
          <p>Taille : ${item.size}</p>
          <p>Quantité : ${item.quantity}</p>
          <p>${item.availability}</p>
        </div>

        <strong>${formatPrice(itemTotal)}</strong>
      </article>
    `;
  });

  if (subtotalElement) subtotalElement.textContent = formatPrice(subtotal);
  if (totalElement) totalElement.textContent = formatPrice(subtotal);
});