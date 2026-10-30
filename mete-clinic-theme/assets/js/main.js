/**
 * MÉTE CLINIC — поведение страницы.
 *
 * Контент рендерится на сервере (WordPress), скрипт отвечает только за
 * состояние шапки, мобильное меню и анимацию появления. Без зависимостей.
 */
(function () {
  "use strict";

  /* Header state --------------------------------------------------------- */

  var header = document.querySelector("[data-header]");

  function onScroll() {
    if (header) {
      header.classList.toggle("is-scrolled", window.scrollY > 8);
    }
  }

  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  /* Mobile menu ---------------------------------------------------------- */

  var menuToggle = document.querySelector("[data-menu-toggle]");
  var menu = document.querySelector("[data-menu]");

  function setMenu(open, returnFocus) {
    if (!menu || !menuToggle) {
      return;
    }

    menuToggle.setAttribute("aria-expanded", open ? "true" : "false");
    menuToggle.setAttribute(
      "aria-label",
      open ? menuToggle.getAttribute("data-label-close") || "Закрыть меню" : menuToggle.getAttribute("data-label-open") || "Открыть меню"
    );
    menu.hidden = !open;
    document.body.classList.toggle("is-menu-open", open);

    if (!open && returnFocus) {
      menuToggle.focus();
    }
  }

  if (menu && menuToggle) {
    menuToggle.addEventListener("click", function () {
      setMenu(menu.hidden, false);
    });

    menu.addEventListener("click", function (event) {
      if (event.target.closest("a")) {
        setMenu(false, false);
      }
    });

    document.addEventListener("click", function (event) {
      if (menu.hidden) {
        return;
      }

      if (header && header.contains(event.target)) {
        return;
      }

      setMenu(false, false);
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && !menu.hidden) {
        setMenu(false, true);
      }
    });

    var desktop = window.matchMedia("(min-width: 1000px)");
    var onDesktop = function (event) {
      if (event.matches) {
        setMenu(false, false);
      }
    };

    if (typeof desktop.addEventListener === "function") {
      desktop.addEventListener("change", onDesktop);
    } else if (typeof desktop.addListener === "function") {
      desktop.addListener(onDesktop);
    }
  }

  /* Service directions: show all ----------------------------------------- */

  var servicesToggle = document.querySelector("[data-services-toggle]");
  var servicesGrid = document.getElementById("services-grid");

  if (servicesToggle && servicesGrid) {
    var extraCards = Array.prototype.slice.call(
      servicesGrid.querySelectorAll(".service-card--extra")
    );
    var noMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    servicesToggle.addEventListener("click", function (event) {
      event.preventDefault();

      var expanded = servicesToggle.getAttribute("aria-expanded") === "true";

      extraCards.forEach(function (card) {
        card.hidden = expanded;

        if (!expanded) {
          card.classList.add("is-visible");
        }
      });

      servicesToggle.setAttribute("aria-expanded", expanded ? "false" : "true");
      servicesToggle.textContent = expanded
        ? servicesToggle.getAttribute("data-label-more")
        : servicesToggle.getAttribute("data-label-less");

      if (expanded) {
        var top =
          servicesGrid.getBoundingClientRect().top + window.scrollY - 88;

        window.scrollTo({ top: top, behavior: noMotion ? "auto" : "smooth" });
      }
    });
  }

  /* Reveal on scroll ----------------------------------------------------- */

  var revealEls = Array.prototype.slice.call(document.querySelectorAll(".reveal"));
  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  if (!revealEls.length) {
    return;
  }

  if (!("IntersectionObserver" in window) || reducedMotion) {
    revealEls.forEach(function (node) {
      node.classList.add("is-visible");
    });
    return;
  }

  var observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: "0px 0px -6% 0px" }
  );

  revealEls.forEach(function (node) {
    observer.observe(node);
  });

  // Страховка: контент не должен остаться невидимым при сбое наблюдателя.
  window.setTimeout(function () {
    revealEls.forEach(function (node) {
      node.classList.add("is-visible");
    });
  }, 2500);
})();
