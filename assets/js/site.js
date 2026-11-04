// Progressive enhancement only: every feature works without JavaScript.
(() => {
  // Mobile menu
  const toggle = document.querySelector(".menu-toggle");
  const nav = document.getElementById("site-nav");
  if (toggle && nav) {
    toggle.addEventListener("click", () => {
      const open = nav.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", String(open));
    });
    nav.addEventListener("click", (e) => {
      if (e.target instanceof HTMLAnchorElement) {
        nav.classList.remove("is-open");
        toggle.setAttribute("aria-expanded", "false");
      }
    });
  }

  // Project filters
  const chips = document.querySelectorAll(".chip[data-filter]");
  const cards = document.querySelectorAll(".card[data-area]");
  chips.forEach((chip) =>
    chip.addEventListener("click", () => {
      const area = chip.dataset.filter;
      chips.forEach((c) => c.setAttribute("aria-pressed", String(c === chip)));
      cards.forEach((card) => { card.hidden = area !== "all" && card.dataset.area !== area; });
    }),
  );

  // Contact form: send with fetch and show errors next to the fields.
  const form = document.querySelector(".contact-form");
  if (!form) return;
  const status = form.querySelector(".form-status");
  const button = form.querySelector('button[type="submit"]');

  const clearErrors = () => {
    form.querySelectorAll(".field-error").forEach((p) => { p.hidden = true; p.textContent = ""; });
    form.querySelectorAll("[aria-invalid]").forEach((el) => el.removeAttribute("aria-invalid"));
  };
  const showStatus = (text, ok) => {
    status.textContent = text;
    status.classList.toggle("is-ok", ok);
    status.classList.toggle("is-error", !ok);
  };

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearErrors();
    button.disabled = true;
    showStatus("Sending…", true);
    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        headers: { Accept: "application/json" },
      });
      const result = await response.json();
      showStatus(result.message, result.ok);
      if (result.ok) {
        form.reset();
        return;
      }
      let first = null;
      for (const [field, message] of Object.entries(result.errors || {})) {
        const input = form.elements.namedItem(field);
        const error = document.getElementById(`${field}-error`);
        if (input && error) {
          input.setAttribute("aria-invalid", "true");
          error.textContent = message;
          error.hidden = false;
          first ??= input;
        }
      }
      first?.focus();
    } catch {
      showStatus("Your message could not be sent. Please check your connection and try again.", false);
    } finally {
      button.disabled = false;
    }
  });
})();
