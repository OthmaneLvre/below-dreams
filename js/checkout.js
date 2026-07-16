document.addEventListener("DOMContentLoaded", () => {
  const CART_KEY = "belowdreams_cart";

  const checkoutItems = document.getElementById("checkout-items");
  const subtotalElement = document.getElementById("checkout-subtotal");
  const shippingPriceElement = document.getElementById(
    "checkout-shipping-price"
  );
  const shippingSavingElement = document.getElementById(
    "checkout-shipping-saving"
  );
  const totalElement = document.getElementById("checkout-total");

  const checkoutForm = document.getElementById("checkout-form");
  const checkoutCartInput = document.getElementById("checkout-cart-input");
  const shippingMethodInput = document.getElementById(
    "checkout-shipping-method-input"
  );

  const shippingOptions = document.querySelectorAll(
    'input[name="shipping_method_display"]'
  );

  const getCart = () => {
    try {
      const cart = JSON.parse(localStorage.getItem(CART_KEY));

      return Array.isArray(cart) ? cart : [];
    } catch (error) {
      return [];
    }
  };

  const formatPrice = (price) => {
    return (
      Number(price).toLocaleString("fr-FR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }) + " €"
    );
  };

  const escapeHtml = (value) => {
    const div = document.createElement("div");
    div.textContent = String(value ?? "");

    return div.innerHTML;
  };

  const cart = getCart();

  let subtotal = 0;

  const calculateSubtotal = () => {
    subtotal = cart.reduce((total, item) => {
      const price = Number(item.price);
      const quantity = Number.parseInt(item.quantity, 10);

      if (
        !Number.isFinite(price) ||
        !Number.isInteger(quantity) ||
        quantity <= 0
      ) {
        return total;
      }

      return total + price * quantity;
    }, 0);

    return subtotal;
  };

  const getSelectedShippingOption = () => {
    return document.querySelector(
      'input[name="shipping_method_display"]:checked'
    );
  };

  const updateShippingSummary = () => {
    const selectedOption = getSelectedShippingOption();

    if (!selectedOption) {
      if (shippingPriceElement) {
        shippingPriceElement.textContent = "À sélectionner";
      }

      if (totalElement) {
        totalElement.textContent = formatPrice(subtotal);
      }

      if (shippingMethodInput) {
        shippingMethodInput.value = "";
      }

      return;
    }

    const shippingMethodId = selectedOption.value;
    const normalShippingPrice = Number(
      selectedOption.dataset.shippingPrice || 0
    );

    const thresholdValue = selectedOption.dataset.freeThreshold;
    const freeThreshold =
      thresholdValue === "" ? null : Number(thresholdValue);

    let appliedShippingPrice = normalShippingPrice;
    let isFreeFromThreshold = false;

    if (
      freeThreshold !== null &&
      Number.isFinite(freeThreshold) &&
      subtotal >= freeThreshold
    ) {
      appliedShippingPrice = 0;
      isFreeFromThreshold = normalShippingPrice > 0;
    }

    if (shippingMethodInput) {
      shippingMethodInput.value = shippingMethodId;
    }

    if (shippingPriceElement) {
      shippingPriceElement.textContent =
        appliedShippingPrice === 0
          ? "Gratuite"
          : formatPrice(appliedShippingPrice);
    }

    if (shippingSavingElement) {
      if (isFreeFromThreshold) {
        shippingSavingElement.hidden = false;
        shippingSavingElement.textContent =
          "Vous économisez " + formatPrice(normalShippingPrice);
      } else {
        shippingSavingElement.hidden = true;
        shippingSavingElement.textContent = "";
      }
    }

    if (totalElement) {
      totalElement.textContent = formatPrice(
        subtotal + appliedShippingPrice
      );
    }

    document
      .querySelectorAll(".checkout-shipping-option")
      .forEach((option) => {
        option.classList.remove("is-selected");
      });

    const selectedLabel = selectedOption.closest(
      ".checkout-shipping-option"
    );

    if (selectedLabel) {
      selectedLabel.classList.add("is-selected");

      const displayedPrice = selectedLabel.querySelector(
        ".checkout-shipping-price"
      );

      if (displayedPrice) {
        if (appliedShippingPrice === 0) {
          displayedPrice.textContent = "Gratuit";
        } else {
          displayedPrice.textContent = formatPrice(
            normalShippingPrice
          );
        }
      }
    }
  };

  if (checkoutItems) {
    if (cart.length === 0) {
      checkoutItems.innerHTML = `
        <div class="cart-empty">
          <h2>Votre panier est vide</h2>
          <p>Ajoutez un produit avant de finaliser votre commande.</p>
          <a href="shop.php" class="btn-primary">
            Retour à la boutique
          </a>
        </div>
      `;
    } else {
      checkoutItems.innerHTML = "";

      cart.forEach((item) => {
        const price = Number(item.price);
        const quantity = Number.parseInt(item.quantity, 10);

        if (
          !Number.isFinite(price) ||
          !Number.isInteger(quantity) ||
          quantity <= 0
        ) {
          return;
        }

        const itemTotal = price * quantity;

        const image = escapeHtml(item.image || "");
        const name = escapeHtml(item.name || "Produit");
        const size = escapeHtml(item.size || "");
        const availability = escapeHtml(item.availability || "");

        checkoutItems.insertAdjacentHTML(
          "beforeend",
          `
            <article class="checkout-item">
              <img
                src="${image}"
                alt="${name}"
                class="checkout-item-img"
              >

              <div>
                <h3>${name}</h3>
                <p>Taille : ${size}</p>
                <p>Quantité : ${quantity}</p>
                ${availability ? `<p>${availability}</p>` : ""}
              </div>

              <strong>${formatPrice(itemTotal)}</strong>
            </article>
          `
        );
      });
    }
  }

  calculateSubtotal();

  if (subtotalElement) {
    subtotalElement.textContent = formatPrice(subtotal);
  }

  shippingOptions.forEach((option) => {
    option.addEventListener("change", updateShippingSummary);
  });

  updateShippingSummary();

  if (checkoutForm && checkoutCartInput) {
    checkoutForm.addEventListener("submit", (event) => {
      const currentCart = getCart();
      const selectedShippingOption = getSelectedShippingOption();

      if (currentCart.length === 0) {
        event.preventDefault();
        alert("Votre panier est vide.");
        window.location.href = "cart.php";
        return;
      }

      if (!selectedShippingOption) {
        event.preventDefault();
        alert("Veuillez sélectionner un mode de livraison.");
        return;
      }

      checkoutCartInput.value = JSON.stringify(currentCart);

      if (shippingMethodInput) {
        shippingMethodInput.value = selectedShippingOption.value;
      }
    });
  }
});