document.addEventListener("DOMContentLoaded", () => {
  const STORAGE_KEY = "belowdreams_cookie_consent";
  const CONSENT_DURATION = 180 * 24 * 60 * 60 * 1000;

  const consentModal = document.getElementById("cookie-consent");
  const mainView = document.getElementById("cookie-consent-main-view");
  const settingsView = document.getElementById(
    "cookie-consent-settings-view"
  );

  const acceptAllButton = document.getElementById("cookie-accept-all");
  const refuseAllButton = document.getElementById("cookie-refuse-all");
  const customizeButton = document.getElementById("cookie-customize");

  const saveSettingsButton = document.getElementById(
    "cookie-save-settings"
  );

  const settingsRefuseAllButton = document.getElementById(
    "cookie-settings-refuse-all"
  );

  const settingsBackButton = document.getElementById(
    "cookie-settings-back"
  );

  const openSettingsButton = document.getElementById(
    "open-cookie-settings"
  );

  const closeButton = document.getElementById(
    "cookie-consent-close"
  );

  const analyticsCheckbox = document.getElementById(
    "cookie-analytics"
  );

  const marketingCheckbox = document.getElementById(
    "cookie-marketing"
  );

  if (!consentModal) {
    return;
  }

  let previouslyFocusedElement = null;
  let isSettingsMode = false;

  const createConsent = (analytics, marketing) => {
    return {
      necessary: true,
      analytics: Boolean(analytics),
      marketing: Boolean(marketing),
      savedAt: Date.now(),
      expiresAt: Date.now() + CONSENT_DURATION,
      version: "2026-07-18",
    };
  };

  const getStoredConsent = () => {
    try {
      const storedValue = localStorage.getItem(STORAGE_KEY);

      if (!storedValue) {
        return null;
      }

      const consent = JSON.parse(storedValue);

      if (
        !consent ||
        typeof consent !== "object" ||
        typeof consent.expiresAt !== "number"
      ) {
        localStorage.removeItem(STORAGE_KEY);
        return null;
      }

      if (Date.now() >= consent.expiresAt) {
        localStorage.removeItem(STORAGE_KEY);
        return null;
      }

      return consent;
    } catch (error) {
      console.error(
        "Impossible de lire les préférences cookies.",
        error
      );

      localStorage.removeItem(STORAGE_KEY);

      return null;
    }
  };

  const storeConsent = (consent) => {
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify(consent)
    );

    applyConsent(consent);

    window.dispatchEvent(
      new CustomEvent("belowdreams:cookie-consent-updated", {
        detail: consent,
      })
    );
  };

  const applyConsent = (consent) => {
    document.documentElement.dataset.cookieAnalytics =
      consent.analytics ? "granted" : "denied";

    document.documentElement.dataset.cookieMarketing =
      consent.marketing ? "granted" : "denied";

    /*
     * Les futurs scripts facultatifs doivent être chargés ici
     * uniquement si le consentement correspondant est accordé.
     *
     * Exemple :
     *
     * if (consent.analytics) {
     *   loadAnalyticsScript();
     * }
     *
     * if (consent.marketing) {
     *   loadMarketingScript();
     * }
     */
  };

  const showMainView = () => {
    isSettingsMode = false;

    if (mainView) {
      mainView.hidden = false;
    }

    if (settingsView) {
      settingsView.hidden = true;
    }
  };

  const showSettingsView = () => {
    isSettingsMode = true;

    const storedConsent = getStoredConsent();

    if (analyticsCheckbox) {
      analyticsCheckbox.checked =
        storedConsent?.analytics ?? false;
    }

    if (marketingCheckbox) {
      marketingCheckbox.checked =
        storedConsent?.marketing ?? false;
    }

    if (mainView) {
      mainView.hidden = true;
    }

    if (settingsView) {
      settingsView.hidden = false;
    }

    settingsBackButton?.focus();
  };

  const openConsentModal = ({
    settings = false,
    allowClose = false,
  } = {}) => {
    previouslyFocusedElement =
      document.activeElement instanceof HTMLElement
        ? document.activeElement
        : null;

    consentModal.hidden = false;
    document.body.classList.add("cookie-consent-open");

    if (closeButton) {
      closeButton.hidden = !allowClose;
    }

    if (settings) {
      showSettingsView();
    } else {
      showMainView();
      acceptAllButton?.focus();
    }
  };

  const closeConsentModal = () => {
    consentModal.hidden = true;
    document.body.classList.remove("cookie-consent-open");

    if (previouslyFocusedElement) {
      previouslyFocusedElement.focus();
    }
  };

  const saveAndClose = (analytics, marketing) => {
    const consent = createConsent(
      analytics,
      marketing
    );

    storeConsent(consent);
    closeConsentModal();
  };

  const trapFocus = (event) => {
    if (
      event.key !== "Tab" ||
      consentModal.hidden
    ) {
      return;
    }

    const focusableElements = consentModal.querySelectorAll(
      [
        "button:not([disabled]):not([hidden])",
        "a[href]",
        "input:not([disabled])",
      ].join(",")
    );

    const visibleFocusableElements = Array.from(
      focusableElements
    ).filter((element) => {
      return element.offsetParent !== null;
    });

    if (visibleFocusableElements.length === 0) {
      return;
    }

    const firstElement = visibleFocusableElements[0];
    const lastElement =
      visibleFocusableElements[
        visibleFocusableElements.length - 1
      ];

    if (
      event.shiftKey &&
      document.activeElement === firstElement
    ) {
      event.preventDefault();
      lastElement.focus();
    }

    if (
      !event.shiftKey &&
      document.activeElement === lastElement
    ) {
      event.preventDefault();
      firstElement.focus();
    }
  };

  acceptAllButton?.addEventListener("click", () => {
    saveAndClose(true, true);
  });

  refuseAllButton?.addEventListener("click", () => {
    saveAndClose(false, false);
  });

  settingsRefuseAllButton?.addEventListener("click", () => {
    saveAndClose(false, false);
  });

  customizeButton?.addEventListener("click", () => {
    showSettingsView();
  });

  settingsBackButton?.addEventListener("click", () => {
    showMainView();
    customizeButton?.focus();
  });

  saveSettingsButton?.addEventListener("click", () => {
    saveAndClose(
      analyticsCheckbox?.checked ?? false,
      marketingCheckbox?.checked ?? false
    );
  });

  openSettingsButton?.addEventListener("click", () => {
    openConsentModal({
      settings: true,
      allowClose: true,
    });
  });

  closeButton?.addEventListener("click", () => {
    closeConsentModal();
  });

  consentModal.addEventListener("keydown", (event) => {
    trapFocus(event);

    if (
      event.key === "Escape" &&
      closeButton &&
      !closeButton.hidden
    ) {
      closeConsentModal();
    }
  });

  document
    .querySelectorAll("[data-cookie-close]")
    .forEach((element) => {
      element.addEventListener("click", () => {
        if (
          closeButton &&
          !closeButton.hidden
        ) {
          closeConsentModal();
        }
      });
    });

  const storedConsent = getStoredConsent();

  if (storedConsent) {
    applyConsent(storedConsent);
  } else {
    openConsentModal({
      settings: false,
      allowClose: false,
    });
  }

  window.BelowDreamsCookies = {
    getConsent: getStoredConsent,

    openSettings: () => {
      openConsentModal({
        settings: true,
        allowClose: true,
      });
    },

    resetConsent: () => {
      localStorage.removeItem(STORAGE_KEY);

      openConsentModal({
        settings: false,
        allowClose: false,
      });
    },

    hasAnalyticsConsent: () => {
      return getStoredConsent()?.analytics === true;
    },

    hasMarketingConsent: () => {
      return getStoredConsent()?.marketing === true;
    },
  };
});