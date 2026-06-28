console.log("cart.js loaded");

document.addEventListener("DOMContentLoaded", () => {
  const CART_KEY = "belowdreams_cart";

  const addToCartBtn = document.querySelector(".add-to-cart");
  const cartItemsContainer = document.getElementById("cart-items");
  const cartSummary = document.querySelector(".cart-summary");
  const subtotalElement = document.getElementById("cart-subtotal");
  const totalElement = document.getElementById("cart-total");

  const getCart = () => {
    return JSON.parse(localStorage.getItem(CART_KEY)) || [];
  };

  const saveCart = (cart) => {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
  };

  const formatPrice = (price) => {
    return Number(price).toFixed(2).replace(".", ",") + " €";
  };

  const updateCartCount = () => {
    const cart = getCart();
    const totalQuantity = cart.reduce((sum, item) => sum + item.quantity, 0);

    document.querySelectorAll(".cart-count").forEach((count) => {
      count.textContent = String(totalQuantity);
      count.hidden = totalQuantity === 0;
    });
  };

  const updateSummary = () => {
    const cart = getCart();
    const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);

    if (subtotalElement) subtotalElement.textContent = formatPrice(total);
    if (totalElement) totalElement.textContent = formatPrice(total);
  };

  const renderCart = () => {
    if (!cartItemsContainer) return;

    const cart = getCart();

    cartItemsContainer.innerHTML = "";

    if (cart.length === 0) {
      if (cartSummary) cartSummary.style.display = "none";

      cartItemsContainer.innerHTML = `
        <div class="cart-empty">
          <div class="cart-empty-icon">🛒</div>
          <h2>Votre panier est vide</h2>
          <p>Découvrez les pièces Below Dreams disponibles en précommande.</p>
          <a href="shop.php" class="btn-primary">Retour à la boutique</a>
        </div>
      `;

      updateCartCount();
      return;
    }

    if (cartSummary) cartSummary.style.display = "flex";

    cart.forEach((item) => {
      const itemTotal = item.price * item.quantity;

      cartItemsContainer.innerHTML += `
        <article class="cart-item" data-id="${item.id}">
          <img
            src="${item.image}"
            alt="${item.name}"
            class="cart-item-img"
          >

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

          <p class="cart-item-price">${formatPrice(itemTotal)}</p>

          <button class="cart-remove" type="button">
            Supprimer
          </button>
        </article>
      `;
    });

    updateSummary();
    updateCartCount();
  };

  const removeProduct = (productId) => {
    const cart = getCart().filter((item) => item.id !== productId);

    saveCart(cart);
    renderCart();
    updateCartCount();
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
    updateCartCount();
  };

  const addProductToCart = () => {
    const selectedSize = document.querySelector('input[name="size"]:checked');
    const quantityInput = document.querySelector(".product-quantity");

    if (!selectedSize) {
      alert("Choisis une taille avant d'ajouter au panier.");
      return;
    }

    const quantity = Number(quantityInput.value) || 1;

    if (quantity < 1) {
      alert("La quantité doit être au minimum de 1.");
      return;
    }

    const product = {
      id: `${addToCartBtn.dataset.id}-${selectedSize.value}`,
      productId: addToCartBtn.dataset.id,
      name: addToCartBtn.dataset.name,
      price: Number(addToCartBtn.dataset.price),
      image: addToCartBtn.dataset.image,
      size: selectedSize.value,
      quantity: quantity,
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
    updateCartCount();

    window.location.href = "cart.php";
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

  updateCartCount();
});
