
document.addEventListener("DOMContentLoaded", () => {
  "use strict";

  const CART_KEY = "belowdreams_cart";
  const addToCartBtn = document.querySelector(".add-to-cart");
  const cartItemsContainer = document.getElementById("cart-items");
  const cartSummary = document.querySelector(".cart-summary");
  const subtotalElement = document.getElementById("cart-subtotal");
  const totalElement = document.getElementById("cart-total");
  const checkoutLink = document.querySelector(".cart-checkout");

  let stockData = null;
  let checking = false;
  let revision = 0;

  const getCart = () => {
    try {
      const data = JSON.parse(
        localStorage.getItem(CART_KEY) || "[]"
      );

      if (!Array.isArray(data)) return [];

      return data
        .filter(item =>
          item &&
          typeof item === "object" &&
          typeof item.id === "string" &&
          Number.isSafeInteger(Number(item.product_id)) &&
          Number(item.product_id) > 0 &&
          Number.isSafeInteger(Number(item.quantity)) &&
          Number(item.quantity) > 0 &&
          Number.isFinite(Number(item.price)) &&
          Number(item.price) >= 0
        )
        .map(item => ({
          ...item,
          product_id: Number(item.product_id),
          quantity: Number(item.quantity),
          price: Number(item.price),
          size: String(item.size || "")
        }));
    } catch {
      return [];
    }
  };

  const saveCart = cart => {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    revision++;
  };

  const formatPrice = amount =>
    Number(amount).toFixed(2).replace(".", ",") + " €";

  const totalFor = (cart, productId) =>
    cart
      .filter(item => item.product_id === productId)
      .reduce((sum, item) => sum + item.quantity, 0);

  const updateCartCount = () => {
    const count = getCart().reduce(
      (sum, item) => sum + item.quantity,
      0
    );

    document.querySelectorAll(".cart-count").forEach(node => {
      node.textContent = String(count);
      node.hidden = count === 0;
    });
  };

  const setCheckoutEnabled = (enabled, label) => {
    if (!checkoutLink) return;

    checkoutLink.setAttribute(
      "aria-disabled",
      enabled ? "false" : "true"
    );

    checkoutLink.tabIndex = enabled ? 0 : -1;
    checkoutLink.style.pointerEvents = enabled ? "" : "none";
    checkoutLink.style.opacity = enabled ? "" : "0.5";

    checkoutLink.textContent =
      label ||
      (enabled ? "Commander" : "Commande indisponible");
  };

  const createElement = (
    tag,
    className = "",
    content = null
  ) => {
    const node = document.createElement(tag);

    if (className) node.className = className;

    if (content !== null) {
      node.textContent = String(content);
    }

    return node;
  };

  const renderEmptyCart = () => {
    const wrapper = createElement("div", "cart-empty");

    const icon = createElement(
      "div",
      "cart-empty-icon",
      "🛒"
    );

    const title = createElement(
      "h2",
      "",
      "Votre panier est vide"
    );

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

  const productState = item => {
    const product = stockData?.[String(item.product_id)];

    if (
      !product ||
      product.available !== true ||
      !Number.isSafeInteger(Number(product.stock)) ||
      Number(product.stock) < 0 ||
      !Number.isFinite(Number(product.price)) ||
      Number(product.price) < 0
    ) {
      return null;
    }

    return product;
  };

  const renderCartItem = (item, cart) => {
    const article = createElement(
      "article",
      "cart-item"
    );

    article.dataset.id = item.id;

    const image = createElement(
      "img",
      "cart-item-img"
    );

    const path = String(item.image || "");

    if (
      path &&
      !path.startsWith("/") &&
      !path.includes("..") &&
      /^[a-zA-Z0-9_./-]+$/.test(path)
    ) {
      image.src = path;
    }

    image.alt = String(
      item.name || "Produit Below Dreams"
    );

    const info = createElement(
      "div",
      "cart-item-info"
    );

    info.appendChild(
      createElement(
        "h2",
        "",
        item.name || "Produit Below Dreams"
      )
    );

    if (item.size) {
      info.appendChild(
        createElement(
          "p",
          "",
          `Taille : ${item.size}`
        )
      );
    }

    const product = productState(item);

    const quantityTotal = totalFor(
      cart,
      item.product_id
    );

    const inStock =
      product && Number(product.stock) > 0;

    const validQuantity =
      inStock &&
      quantityTotal <= Number(product.stock);

    let message = "";

    if (!stockData) {
      message = "Vérification du stock indisponible";
    } else if (!product) {
      message = "Produit indisponible";
    } else if (!inStock) {
      message = "Rupture de stock";
    } else if (!validQuantity) {
      message =
        `Stock insuffisant : ${product.stock} disponible(s)`;
    }

    if (message) {
      const warning = createElement(
        "p",
        "cart-stock-warning",
        message
      );

      warning.style.color = "#e35b5b";
      warning.setAttribute("role", "status");

      info.appendChild(warning);
    }

    const quantityWrapper = createElement(
      "div",
      "cart-item-quantity"
    );

    const decrease = createElement(
      "button",
      "cart-decrease",
      "−"
    );

    decrease.type = "button";
    decrease.setAttribute(
      "aria-label",
      "Diminuer la quantité"
    );

    const quantity = createElement(
      "span",
      "",
      item.quantity
    );

    const increase = createElement(
      "button",
      "cart-increase",
      "+"
    );

    increase.type = "button";
    increase.setAttribute(
      "aria-label",
      "Augmenter la quantité"
    );

    increase.disabled =
      !product ||
      quantityTotal >= Number(product.stock) ||
      checking;

    quantityWrapper.append(
      decrease,
      quantity,
      increase
    );

    const price = createElement(
      "p",
      "cart-item-price",
      product
        ? formatPrice(
            Number(product.price) * item.quantity
          )
        : "Prix à vérifier"
    );

    const remove = createElement(
      "button",
      "cart-remove",
      "Supprimer"
    );

    remove.type = "button";

    article.append(
      image,
      info,
      quantityWrapper,
      price,
      remove
    );

    return article;
  };

  const renderCart = () => {
    if (!cartItemsContainer) return;

    const cart = getCart();

    cartItemsContainer.replaceChildren();
    updateCartCount();

    if (!cart.length) {
      if (cartSummary) {
        cartSummary.style.display = "none";
      }

      setCheckoutEnabled(false, "Panier vide");
      renderEmptyCart();
      return;
    }

    if (cartSummary) {
      cartSummary.style.display = "flex";
    }

    const fragment = document.createDocumentFragment();

    cart.forEach(item => {
      fragment.appendChild(
        renderCartItem(item, cart)
      );
    });

    cartItemsContainer.appendChild(fragment);

    const allValid =
      !!stockData &&
      !checking &&
      cart.every(item => {
        const product = productState(item);

        return (
          product &&
          Number(product.stock) >=
            totalFor(cart, item.product_id) &&
          Number(product.stock) > 0
        );
      });

    const total = stockData
      ? cart.reduce((sum, item) => {
          const product = productState(item);

          return sum + (
            product
              ? Number(product.price) * item.quantity
              : 0
          );
        }, 0)
      : 0;

    if (subtotalElement) {
      subtotalElement.textContent = allValid
        ? formatPrice(total)
        : "À vérifier";
    }

    if (totalElement) {
      totalElement.textContent = allValid
        ? formatPrice(total)
        : "À vérifier";
    }

    setCheckoutEnabled(
      allValid,
      checking
        ? "Vérification des stocks..."
        : undefined
    );
  };

  const verifyStock = async () => {
    if (!cartItemsContainer) return;

    const cart = getCart();

    if (!cart.length) {
      stockData = null;
      checking = false;
      renderCart();
      return;
    }

    const requestRevision = revision;

    checking = true;
    stockData = null;

    renderCart();

    try {
      const response = await fetch(
        "api/cart-stock.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify({
            product_ids: [
              ...new Set(
                cart.map(item => item.product_id)
              )
            ]
          }),
          cache: "no-store",
          credentials: "same-origin"
        }
      );

      if (!response.ok) {
        throw new Error(
          "HTTP " + response.status
        );
      }

      const result = await response.json();

      if (
        result?.success !== true ||
        !result.products ||
        typeof result.products !== "object" ||
        Array.isArray(result.products)
      ) {
        throw new Error(
          "Réponse API invalide"
        );
      }

      if (revision !== requestRevision) {
        return;
      }

      stockData = result.products;

    } catch (error) {
      if (revision !== requestRevision) {
        return;
      }

      console.error(
        "Vérification des stocks impossible :",
        error
      );

      stockData = null;

    } finally {
      if (revision === requestRevision) {
        checking = false;
        renderCart();
      }
    }
  };

  const removeProduct = id => {
    saveCart(
      getCart().filter(item => item.id !== id)
    );

    verifyStock();
  };

  const updateQuantity = (id, action) => {
    const cart = getCart();

    const item = cart.find(
      product => product.id === id
    );

    if (!item) return;

    if (action === "increase") {
      const product = productState(item);

      if (
        checking ||
        !product ||
        totalFor(cart, item.product_id) >=
          Number(product.stock)
      ) {
        alert(
          "Vous avez atteint la quantité maximale disponible pour ce produit."
        );
        return;
      }

      item.quantity++;

    } else if (action === "decrease") {
      item.quantity--;

      if (item.quantity <= 0) {
        return removeProduct(id);
      }

    } else {
      return;
    }

    saveCart(cart);
    verifyStock();
  };

  const addProductToCart = () => {
    const quantityInput = document.querySelector(
      ".product-quantity"
    );

    const selectedSize = document.querySelector(
      'input[name="size"]:checked'
    );

    const sizeOptions = document.querySelectorAll(
      'input[name="size"]'
    );

    if (
      !addToCartBtn ||
      !quantityInput ||
      addToCartBtn.disabled
    ) {
      return;
    }

    if (
      sizeOptions.length &&
      !selectedSize
    ) {
      alert(
        "Choisissez une taille avant d'ajouter au panier."
      );
      return;
    }

    const size = selectedSize
      ? selectedSize.value
      : "";

    const quantity = Number(
      quantityInput.value
    );

    const maxStock = Number(
      quantityInput.max
    );

    const productId = Number(
      addToCartBtn.dataset.id
    );

    const price = Number(
      addToCartBtn.dataset.price
    );

    if (
      !Number.isSafeInteger(quantity) ||
      quantity < 1 ||
      !Number.isSafeInteger(maxStock) ||
      maxStock < 1 ||
      quantity > maxStock
    ) {
      alert(
        "La quantité demandée dépasse le stock disponible."
      );
      return;
    }

    if (
      !Number.isSafeInteger(productId) ||
      productId < 1 ||
      !Number.isFinite(price) ||
      price < 0
    ) {
      alert(
        "Ce produit ne peut pas être ajouté au panier."
      );
      return;
    }

    const cart = getCart();

    if (
      totalFor(cart, productId) + quantity >
      maxStock
    ) {
      alert(
        "Vous avez déjà atteint la quantité disponible pour ce produit."
      );
      return;
    }

    const id = `${productId}-${size}`;

    const existing = cart.find(
      item => item.id === id
    );

    if (existing) {
      existing.quantity += quantity;
      existing.maxStock = maxStock;
    } else {
      cart.push({
        id,
        product_id: productId,
        slug: addToCartBtn.dataset.slug || "",
        name: addToCartBtn.dataset.name || "",
        price,
        image: addToCartBtn.dataset.image || "",
        size,
        quantity,
        maxStock
      });
    }

    saveCart(cart);
    updateCartCount();

    window.location.href = "cart.php";
  };

  if (addToCartBtn) {
    const input = document.querySelector(
      ".product-quantity"
    );

    if (input) {
      const normalize = () => {
        const max = Number(input.max);
        const value = Number(input.value);

        if (
          !Number.isSafeInteger(max) ||
          max < 1
        ) {
          return;
        }

        if (
          !Number.isSafeInteger(value) ||
          value < 1
        ) {
          input.value = "1";
        } else if (value > max) {
          input.value = String(max);
        }
      };

      input.addEventListener("input", () => {
        const max = Number(input.max);

        if (
          Number.isSafeInteger(max) &&
          max >= 1 &&
          Number(input.value) > max
        ) {
          input.value = String(max);
        }
      });

      input.addEventListener(
        "change",
        normalize
      );

      input.addEventListener(
        "blur",
        normalize
      );
    }

    addToCartBtn.addEventListener(
      "click",
      addProductToCart
    );
  }

  if (cartItemsContainer) {
    cartItemsContainer.addEventListener(
      "click",
      event => {
        const button = event.target.closest(
          "button"
        );

        if (!button) return;

        const item = button.closest(
          ".cart-item"
        );

        if (!item) return;

        if (
          button.classList.contains(
            "cart-increase"
          )
        ) {
          updateQuantity(
            item.dataset.id,
            "increase"
          );
        } else if (
          button.classList.contains(
            "cart-decrease"
          )
        ) {
          updateQuantity(
            item.dataset.id,
            "decrease"
          );
        } else if (
          button.classList.contains(
            "cart-remove"
          )
        ) {
          removeProduct(
            item.dataset.id
          );
        }
      }
    );

    window.addEventListener(
      "pageshow",
      event => {
        if (event.persisted) {
          verifyStock();
        }
      }
    );

    window.addEventListener(
      "storage",
      event => {
        if (event.key === CART_KEY) {
          revision++;
          verifyStock();
        }
      }
    );

    verifyStock();
  }

  updateCartCount();
});
