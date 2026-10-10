document.addEventListener("DOMContentLoaded", () => {
  const CART_KEY = "belowdreams_cart";

  const addToCartBtn = document.querySelector(".add-to-cart");
  const cartItemsContainer = document.getElementById("cart-items");
  const cartSummary = document.querySelector(".cart-summary");
  const subtotalElement = document.getElementById("cart-subtotal");
  const totalElement = document.getElementById("cart-total");

  const getCart = () => {
    try {
      const storedCart = JSON.parse(localStorage.getItem(CART_KEY) || "[]");

      if (!Array.isArray(storedCart)) {
        return [];
      }

      return storedCart.filter((item) => {
        return (
          item !== null &&
          typeof item === "object" &&
          typeof item.id === "string" &&
          Number.isInteger(Number(item.product_id)) &&
          Number(item.product_id) > 0 &&
          Number.isInteger(Number(item.quantity)) &&
          Number(item.quantity) > 0 &&
          Number.isFinite(Number(item.price)) &&
          Number(item.price) >= 0
        );
      }).map((item) => ({
        ...item,
        product_id: Number(item.product_id),
        quantity: Number(item.quantity),
        price: Number(item.price),
        size: String(item.size || ""),
      }));
    } catch {
      return [];
    }
  };

  const saveCart = (cart) => {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
  };

  const formatPrice = (price) => {
    return Number(price).toFixed(2).replace(".", ",") + " €";
  };

  const updateCartCount = () => {
    const totalQuantity = getCart().reduce(
      (sum, item) => sum + item.quantity,
      0
    );

    document.querySelectorAll(".cart-count").forEach((count) => {
      count.textContent = String(totalQuantity);
      count.hidden = totalQuantity === 0;
    });
  };

  const updateSummary = () => {
    const total = getCart().reduce(
      (sum, item) => sum + item.price * item.quantity,
      0
    );

    if (subtotalElement) {
      subtotalElement.textContent = formatPrice(total);
    }

    if (totalElement) {
      totalElement.textContent = formatPrice(total);
    }
  };

  const createElement = (tag, className = "", text = null) => {
    const element = document.createElement(tag);

    if (className) {
      element.className = className;
    }

    if (text !== null) {
      element.textContent = String(text);
    }

    return element;
  };

  const renderEmptyCart = () => {
    if (!cartItemsContainer) return;

    const wrapper = createElement("div", "cart-empty");
    const icon = createElement("div", "cart-empty-icon", "🛒");
    const title = createElement("h2", "", "Votre panier est vide");
    const description = createElement(
      "p",
      "",
      "Découvrez les vêtements et accessoires Below Dreams."
    );

    const link = createElement(
      "a",
      "btn-primary",
      "Retour à la boutique"
    );

    link.href = "shop.php";

    wrapper.append(icon, title, description, link);
    cartItemsContainer.appendChild(wrapper);
  };

  const renderCartItem = (item) => {
    const article = createElement("article", "cart-item");
    article.dataset.id = item.id;

    const image = createElement("img", "cart-item-img");
    const imagePath = String(item.image || "");

    // Seuls les chemins d'images locaux sont acceptés.
    if (
      imagePath &&
      !imagePath.startsWith("/") &&
      !imagePath.startsWith("//") &&
      !imagePath.includes("..") &&
      /^[a-zA-Z0-9_./-]+$/.test(imagePath)
    ) {
      image.src = imagePath;
    }

    image.alt = String(item.name || "Produit Below Dreams");

    const info = createElement("div", "cart-item-info");

    const name = createElement(
      "h2",
      "",
      item.name || "Produit Below Dreams"
    );

    info.appendChild(name);

    if (item.size) {
      info.appendChild(
        createElement("p", "", `Taille : ${item.size}`)
      );
    }

    const quantityWrapper = createElement(
      "div",
      "cart-item-quantity"
    );

    const decreaseButton = createElement(
      "button",
      "cart-decrease",
      "−"
    );
    decreaseButton.type = "button";
    decreaseButton.setAttribute("aria-label", "Diminuer la quantité");

    const quantity = createElement("span", "", item.quantity);

    const increaseButton = createElement(
      "button",
      "cart-increase",
      "+"
    );
    increaseButton.type = "button";
    increaseButton.setAttribute("aria-label", "Augmenter la quantité");

    quantityWrapper.append(
      decreaseButton,
      quantity,
      increaseButton
    );

    const price = createElement(
      "p",
      "cart-item-price",
      formatPrice(item.price * item.quantity)
    );

    const removeButton = createElement(
      "button",
      "cart-remove",
      "Supprimer"
    );
    removeButton.type = "button";

    article.append(
      image,
      info,
      quantityWrapper,
      price,
      removeButton
    );

    return article;
  };

  const renderCart = () => {
    if (!cartItemsContainer) return;

    const cart = getCart();
    cartItemsContainer.replaceChildren();

    if (cart.length === 0) {
      if (cartSummary) {
        cartSummary.style.display = "none";
      }

      renderEmptyCart();
      updateCartCount();
      return;
    }

    if (cartSummary) {
      cartSummary.style.display = "flex";
    }

    const fragment = document.createDocumentFragment();

    cart.forEach((item) => {
      fragment.appendChild(renderCartItem(item));
    });

    cartItemsContainer.appendChild(fragment);

    updateSummary();
    updateCartCount();
  };

  const removeProduct = (productId) => {
    const cart = getCart().filter((item) => item.id !== productId);

    saveCart(cart);
    renderCart();
  };

  const getTotalProductQuantity = (cart, productId) => {
    return cart
      .filter((item) => item.product_id === productId)
      .reduce((total, item) => total + item.quantity, 0);
  };

  const updateQuantity = (productId, action) => {
    const cart = getCart();
    const item = cart.find((product) => product.id === productId);

    if (!item) return;

    if (action === "increase") {
      const maxStock = Number(item.maxStock);

      const totalQuantity = getTotalProductQuantity(
        cart,
        item.product_id
      );

      if (
        !Number.isInteger(maxStock) ||
        maxStock < 1 ||
        totalQuantity >= maxStock
      ) {
        alert(
          "Vous avez atteint la quantité maximale disponible pour ce produit."
        );
        return;
      }

      item.quantity += 1;
    } else if (action === "decrease") {
      item.quantity -= 1;

      if (item.quantity <= 0) {
        removeProduct(productId);
        return;
      }
    } else {
      return;
    }

    saveCart(cart);
    renderCart();
  };

  const addProductToCart = () => {
    const quantityInput = document.querySelector(".product-quantity");
    const selectedSize = document.querySelector(
      'input[name="size"]:checked'
    );
    const sizeOptions = document.querySelectorAll(
      'input[name="size"]'
    );

    if (!addToCartBtn || !quantityInput || addToCartBtn.disabled) {
      return;
    }

    if (sizeOptions.length > 0 && !selectedSize) {
      alert("Choisissez une taille avant d'ajouter au panier.");
      return;
    }

    const size = selectedSize ? selectedSize.value : "";
    const quantity = Number(quantityInput.value);
    const maxStock = Number(quantityInput.max);
    const productId = Number(addToCartBtn.dataset.id);
    const price = Number(addToCartBtn.dataset.price);

    if (
      !Number.isInteger(quantity) ||
      quantity < 1 ||
      !Number.isInteger(maxStock) ||
      maxStock < 1 ||
      quantity > maxStock
    ) {
      alert("La quantité demandée dépasse le stock disponible.");
      return;
    }

    if (
      !Number.isInteger(productId) ||
      productId < 1 ||
      !Number.isFinite(price) ||
      price < 0
    ) {
      alert("Ce produit ne peut pas être ajouté au panier.");
      return;
    }

    const cart = getCart();
    const existingQuantity = getTotalProductQuantity(
      cart,
      productId
    );

    if (existingQuantity + quantity > maxStock) {
      alert(
        "Vous avez déjà atteint la quantité disponible pour ce produit."
      );
      return;
    }

    const productKey = `${productId}-${size}`;

    const existingProduct = cart.find(
      (item) => item.id === productKey
    );

    if (existingProduct) {
      existingProduct.quantity += quantity;
      existingProduct.maxStock = maxStock;
    } else {
      cart.push({
        id: productKey,
        product_id: productId,
        slug: addToCartBtn.dataset.slug || "",
        name: addToCartBtn.dataset.name || "",
        price,
        image: addToCartBtn.dataset.image || "",
        size,
        quantity,
        maxStock,
      });
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
      const button = event.target.closest("button");
      if (!button) return;

      const cartItem = button.closest(".cart-item");
      if (!cartItem) return;

      const productId = cartItem.dataset.id;

      if (button.classList.contains("cart-increase")) {
        updateQuantity(productId, "increase");
      } else if (button.classList.contains("cart-decrease")) {
        updateQuantity(productId, "decrease");
      } else if (button.classList.contains("cart-remove")) {
        removeProduct(productId);
      }
    });

    renderCart();
  }

  updateCartCount();
});