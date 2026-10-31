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
  var menuTimer = null;
  var menuEndHandler = null;

  // Подписи берём из шаблона: они переводятся в PHP.
  var menuLabelOpen = menuToggle ? menuToggle.getAttribute("aria-label") || "" : "";
  var menuLabelClose = menuToggle ? menuToggle.getAttribute("data-label-close") || menuLabelOpen : "";

  function clearMenuEnd() {
    if (menuEndHandler && menu) {
      menu.removeEventListener("animationend", menuEndHandler);
    }

    menuEndHandler = null;
  }

  function finishMenuClose() {
    if (!menu) {
      return;
    }

    clearMenuEnd();

    if (menuTimer) {
      window.clearTimeout(menuTimer);
      menuTimer = null;
    }

    menu.classList.remove("is-closing");
    menu.hidden = true;
  }

  function setMenu(open, returnFocus) {
    if (!menu || !menuToggle) {
      return;
    }

    menuToggle.setAttribute("aria-expanded", open ? "true" : "false");

    if (menuLabelOpen || menuLabelClose) {
      menuToggle.setAttribute("aria-label", open ? menuLabelClose : menuLabelOpen);
    }
    document.body.classList.toggle("is-menu-open", open);

    if (menuTimer) {
      window.clearTimeout(menuTimer);
      menuTimer = null;
    }

    // Обработчик закрытия мог остаться от прерванной анимации: без снятия
    // его animationend спрячет уже открытое меню.
    clearMenuEnd();

    if (open) {
      menu.hidden = false;
      menu.classList.remove("is-closing");
      return;
    }

    if (returnFocus) {
      menuToggle.focus();
    }

    // Закрываем только видимое меню: повторный клик во время анимации
    // не должен запускать её заново.
    if (menu.hidden || menu.classList.contains("is-closing")) {
      return;
    }

    menu.classList.add("is-closing");

    menuEndHandler = function (event) {
      if (event && event.target !== menu) {
        return;
      }

      clearMenuEnd();
      finishMenuClose();
    };

    menu.addEventListener("animationend", menuEndHandler);

    // Страховка: у анимации может не быть события (reduced motion),
    // но меню обязано закрыться.
    menuTimer = window.setTimeout(finishMenuClose, 300);
  }

  if (menu && menuToggle) {
    menuToggle.addEventListener("click", function () {
      setMenu(menu.hidden || menu.classList.contains("is-closing"), false);
    });

    menu.addEventListener("click", function (event) {
      if (event.target.closest("a")) {
        setMenu(false, false);
      }
    });

    document.addEventListener("click", function (event) {
      if (menu.hidden || header && header.contains(event.target)) {
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

  /* Reveal on scroll ----------------------------------------------------- */

  var revealEls = Array.prototype.slice.call(document.querySelectorAll(".reveal"));
  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  // Совпадает с --dur-slow в style.css.
  var REVEAL_DURATION = 800;
  var REVEAL_STEP = 70;
  var REVEAL_MAX_STEPS = 5;

  function finishReveal(node) {
    node.classList.add("is-revealed");
    node.style.removeProperty("--reveal-delay");
    node.style.willChange = "";
  }

  function showReveal(node, delay) {
    if (node.classList.contains("is-revealed")) {
      return;
    }

    node.style.setProperty("--reveal-delay", delay + "ms");
    node.style.willChange = "opacity, transform";
    node.classList.add("is-visible");

    /*
     * Пока элемент не получил .is-revealed, на нём действует переход
     * появления (и он перебивает hover-переходы компонента). После
     * завершения класс снимает это правило, и дальше работают обычные
     * transition самого элемента — уже без задержки.
     */
    window.setTimeout(function () {
      finishReveal(node);
    }, REVEAL_DURATION + delay + 120);
  }

  function compareDocumentOrder(a, b) {
    if (a === b) {
      return 0;
    }

    var position = a.compareDocumentPosition(b);

    if (position & Node.DOCUMENT_POSITION_FOLLOWING) {
      return -1;
    }

    if (position & Node.DOCUMENT_POSITION_PRECEDING) {
      return 1;
    }

    return 0;
  }

  if (!revealEls.length) {
    return;
  }

  if (!("IntersectionObserver" in window) || reducedMotion) {
    revealEls.forEach(function (node) {
      node.classList.add("is-visible");
      finishReveal(node);
    });
    return;
  }

  var observerFired = false;

  var observer = new IntersectionObserver(
    function (entries) {
      var groups = [];

      observerFired = true;

      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }

        observer.unobserve(entry.target);

        // Карточки лежат в отдельных <li>, поэтому шаг лесенки считаем
        // по общему списку, а не по непосредственному родителю.
        var parent = entry.target.closest("[data-reveal-group]") || entry.target.parentNode;
        var group = null;

        groups.forEach(function (candidate) {
          if (candidate.parent === parent) {
            group = candidate;
          }
        });

        if (!group) {
          group = { parent: parent, nodes: [] };
          groups.push(group);
        }

        group.nodes.push(entry.target);
      });

      // Элементы, попавшие в экран одной пачкой, появляются лесенкой.
      groups.forEach(function (group) {
        group.nodes.sort(compareDocumentOrder);
        group.nodes.forEach(function (node, index) {
          showReveal(node, Math.min(index, REVEAL_MAX_STEPS) * REVEAL_STEP);
        });
      });
    },
    { threshold: 0.15, rootMargin: "0px 0px -8% 0px" }
  );

  revealEls.forEach(function (node) {
    observer.observe(node);
  });

  /*
   * Страховка: контент не должен остаться невидимым при сбое наблюдателя.
   * Если наблюдатель работал, показываем только то, что уже попало на экран,
   * иначе секции ниже первого экрана теряют анимацию появления.
   */
  window.setTimeout(function () {
    revealEls.forEach(function (node) {
      if (node.classList.contains("is-revealed") || node.classList.contains("is-visible")) {
        return;
      }

      if (observerFired && node.getBoundingClientRect().top >= window.innerHeight) {
        return;
      }

      if (observerFired) {
        observer.unobserve(node);
      }

      node.classList.add("is-visible");
      finishReveal(node);
    });
  }, 2500);
})();
