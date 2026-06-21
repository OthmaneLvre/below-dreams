console.log("cart.js loaded");

/* ==========================================================
   BELOW DREAMS — CART
   - Ajout panier depuis produit.html
   - Stockage localStorage
   - Affichage panier sur panier.html
   ========================================================== */

document.addEventListener("DOMContentLoaded", () => {
  const CART_KEY = "belowdreams_cart";

  const addToCartBtn = document.querySelector(".add-to-cart");
  const cartItemsContainer = document.getElementById("cart-items");

  const getCart = () => {
    return JSON.parse(localStorage.getItem(CART_KEY)) || [];
  };

  const saveCart = (cart) => {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
  };

  const addProductToCart = () => {
    const title = document.querySelector(".product-detail-title")?.textContent.trim();
    const priceText = document.querySelector(".product-detail-price")?.textContent.trim();
    const quantityInput = document.querySelector(".product-quantity");
    const selectedSize = document.querySelector('input[name="size"]:checked');

    if (!selectedSize) {
      alert("Choisis une taille avant d'ajouter au panier.");
      return;
    }

    const product = {
      id: `${title}-${selectedSize.value}`,
      name: title,
      price: Number(priceText.replace("€", "").trim()),
      size: selectedSize.value,
      quantity: Number(quantityInput.value) || 1,
      availability: "Précommande",
    };

    const cart = getCart();

    const existingProduct = cart.find((item) => item.id === product.id);

    if (existingProduct) {
      existingProduct.quantity += product.quantity;
    } else {
      cart.push(product);
    }

    saveCart(cart);
    window.location.href = "panier.html";
  };

  const renderCart = () => {
    if (!cartItemsContainer) return;

    const cart = getCart();

    cartItemsContainer.innerHTML = "";

    if (cart.length === 0) {
      cartItemsContainer.innerHTML = `
        <p class="cart-empty">Votre panier est vide.</p>
      `;
      updateSummary(0);
      return;
    }

    cart.forEach((item) => {
      const itemTotal = item.price * item.quantity;

      cartItemsContainer.innerHTML += `
        <article class="cart-item" data-id="${item.id}">
          <div class="cart-item-img"></div>

          <div class="cart-item-info">
            <h2>${item.name}</h2>
            <p>Taille : ${item.size}</p>
            <p>${item.availability}</p>
          </div>

          <div class="cart-item-quantity">
            <button type="button" class="cart-decrease">-</button>
            <span>${item.quantity}</span>
            <button type="button" class="cart-increase">+</button>
          </div>

          <p class="cart-item-price">${itemTotal} €</p>

          <button class="cart-remove" type="button">
            Supprimer
          </button>
        </article>
      `;
    });

    updateSummary();
  };

  const updateSummary = () => {
    const cart = getCart();
    const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);

    const summaryValues = document.querySelectorAll(".cart-summary strong");

    if (summaryValues[0]) summaryValues[0].textContent = `${total} €`;
    if (summaryValues[2]) summaryValues[2].textContent = `${total} €`;
  };

  const updateQuantity = (productId, action) => {
    const cart = getCart();
    const item = cart.find((product) => product.id === productId);

    if (!item) return;

    if (action === "increase") {
      item.quantity += 1;
    }

    if (action === "decrease") {
      item.quantity -= 1;

      if (item.quantity <= 0) {
        removeProduct(productId);
        return;
      }
    }

    saveCart(cart);
    renderCart();
  };

  const removeProduct = (productId) => {
    const cart = getCart().filter((item) => item.id !== productId);
    saveCart(cart);
    renderCart();
  };

  if (addToCartBtn) {
    addToCartBtn.addEventListener("click", addProductToCart);
  }

  if (cartItemsContainer) {
    cartItemsContainer.addEventListener("click", (event) => {
      const cartItem = event.target.closest(".cart-item");
      if (!cartItem) return;

      const productId = cartItem.dataset.id;

      if (event.target.classList.contains("cart-increase")) {
        updateQuantity(productId, "increase");
      }

      if (event.target.classList.contains("cart-decrease")) {
        updateQuantity(productId, "decrease");
      }

      if (event.target.classList.contains("cart-remove")) {
        removeProduct(productId);
      }
    });

    renderCart();
  }
});