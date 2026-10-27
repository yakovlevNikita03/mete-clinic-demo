/**
 * METE CLINIC — поведение страницы и рендер контента из конфигурации.
 * Никаких сторонних библиотек: только DOM API.
 */
(function () {
  "use strict";

  var config = window.SITE_CONFIG || {};
  var content = window.SITE_CONTENT || {};

  /* Helpers --------------------------------------------------------------- */

  function clean(value) {
    return typeof value === "string" ? value.trim() : "";
  }

  function has(value) {
    return clean(value) !== "";
  }

  function httpUrl(value) {
    var raw = clean(value);
    if (!raw) return "";
    try {
      var url = new URL(raw, window.location.href);
      if (url.protocol !== "http:" && url.protocol !== "https:") return "";
      return url.href;
    } catch (error) {
      return "";
    }
  }

  function localAsset(value) {
    var raw = clean(value);
    if (!raw) return "";
    // Только относительные пути внутри проекта: без схем и protocol-relative URL.
    if (/^[a-z][a-z0-9+.-]*:/i.test(raw) || raw.indexOf("//") === 0) return "";
    return raw;
  }

  function el(tag, className, textValue) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (textValue !== undefined && textValue !== null) node.textContent = textValue;
    return node;
  }

  // Внешние ссылки открываем в новой вкладке и без доступа к window.opener.
  function setLinkTarget(link, url) {
    if (!link) return;
    if (/^https?:/i.test(url)) {
      link.target = "_blank";
      link.rel = "noopener noreferrer";
    } else {
      link.removeAttribute("target");
      link.removeAttribute("rel");
    }
  }

  function icon(name) {
    var icons = {
      pin:
        '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.6"/></svg>',
      clock:
        '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 7.5V12l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
      phone:
        '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z" stroke-linecap="round" stroke-linejoin="round"/></svg>',
      chat:
        '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4.5 5.5h15v10h-8l-4.5 3v-3H4.5Z" stroke-linejoin="round"/></svg>',
      arrow:
        '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 12h15M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    };
    return icons[name] || icons.chat;
  }

  /* Phone ---------------------------------------------------------------- */

  var phoneDisplay = clean(config.phoneDisplay);
  var phoneDigits = clean(config.phoneHref)
    .replace(/^tel:/i, "")
    .replace(/[^\d+]/g, "");

  if (!/^\+?\d{10,15}$/.test(phoneDigits)) {
    var displayDigits = phoneDisplay.replace(/[^\d]/g, "");
    phoneDigits = /^\d{10,15}$/.test(displayDigits) ? "+" + displayDigits : "";
  }

  var telHref = phoneDigits ? "tel:" + phoneDigits : "";
  var phoneLabel = phoneDisplay || (telHref ? telHref.replace(/^tel:/, "") : "");

  Array.prototype.forEach.call(document.querySelectorAll("[data-phone-link]"), function (link) {
    if (!telHref) {
      link.remove();
      return;
    }
    link.setAttribute("href", telHref);
    link.hidden = false;
    Array.prototype.forEach.call(link.querySelectorAll("[data-phone-text]"), function (node) {
      node.textContent = phoneLabel;
    });
  });

  /* MAX ------------------------------------------------------------------ */

  var maxUrl = httpUrl(config.maxUrl);
  var maxLabel = "Написать в MAX";

  if (maxUrl) {
    Array.prototype.forEach.call(document.querySelectorAll("[data-max]"), function (slot) {
      if (slot.querySelector("a")) return;

      var link = el("a", "social-link");
      var logo = el("span", "max-logo");
      logo.setAttribute("aria-hidden", "true");
      link.appendChild(logo);
      link.href = maxUrl;
      setLinkTarget(link, maxUrl);
      link.setAttribute("aria-label", maxLabel);
      link.title = maxLabel;
      slot.appendChild(link);
      slot.hidden = false;
    });
  }

  /* Booking -------------------------------------------------------------- */

  var bookingUrl = httpUrl(config.bookingUrl);
  var messengerItems = (Array.isArray(config.messengers) ? config.messengers : [])
    .map(function (item) {
      return {
        name: clean(item && item.name),
        label: clean(item && item.label) || clean(item && item.name),
        url: httpUrl(item && item.url)
      };
    })
    .filter(function (item) {
      return item.url !== "";
    });

  Array.prototype.forEach.call(document.querySelectorAll("[data-booking]"), function (link) {
    var targetUrl = bookingUrl || "#contacts";
    link.setAttribute("href", targetUrl);
    setLinkTarget(link, targetUrl);
    if (bookingUrl) {
      link.removeAttribute("data-fallback");
    } else {
      link.setAttribute("data-fallback", "true");
    }
  });

  if (!bookingUrl) {
    var noteText = "Онлайн-запись Yclients подключается.";
    if (telHref && messengerItems.length) {
      noteText += " Пока можно записаться по телефону или написать в мессенджере.";
    } else if (telHref) {
      noteText += " Пока можно записаться по телефону.";
    } else if (messengerItems.length) {
      noteText += " Пока можно написать в мессенджере.";
    } else {
      noteText += " Контакты для записи уточняются.";
    }
    Array.prototype.forEach.call(document.querySelectorAll("[data-booking-note]"), function (note) {
      note.textContent = noteText;
      note.hidden = false;
    });
  }

  /* Messengers ----------------------------------------------------------- */

  var messengersEl = document.querySelector("[data-messengers]");
  if (messengersEl && messengerItems.length) {
    messengerItems.forEach(function (item) {
      var link = el("a", "chip");
      link.href = item.url;
      link.target = "_blank";
      link.rel = "noopener noreferrer";
      link.setAttribute("aria-label", item.label);
      link.innerHTML = icon("chat");
      link.appendChild(document.createTextNode(item.name || item.label));
      messengersEl.appendChild(link);
    });
    messengersEl.hidden = false;
  }

  /* Services ------------------------------------------------------------- */

  var servicesEl = document.querySelector("[data-services]");
  if (servicesEl && Array.isArray(content.services)) {
    content.services.forEach(function (service) {
      var link = el("a", "service-card reveal");
      var serviceUrl = httpUrl(service.url) || bookingUrl || "#contacts";
      link.href = serviceUrl;
      setLinkTarget(link, serviceUrl);

      var media = el("span", "service-card__media");
      var imagePath = localAsset(service.image);
      if (imagePath) {
        var img = document.createElement("img");
        img.src = imagePath;
        img.alt = clean(service.alt);
        img.width = 800;
        img.height = 600;
        img.loading = "lazy";
        img.decoding = "async";
        media.appendChild(img);
      }
      link.appendChild(media);

      var body = el("span", "service-card__body");
      body.appendChild(el("h3", "service-card__title", clean(service.title)));
      body.appendChild(el("span", "service-card__text", clean(service.description)));

      var arrow = el("span", "service-card__arrow");
      arrow.innerHTML = icon("arrow");
      body.appendChild(arrow);
      link.appendChild(body);

      servicesEl.appendChild(link);
    });
  }

  /* Prices --------------------------------------------------------------- */

  var pricesEl = document.querySelector("[data-prices]");
  if (pricesEl && Array.isArray(content.prices)) {
    content.prices.forEach(function (item) {
      var row = el("li", "price-row");

      var name = el("span", "price-row__name", clean(item.name));
      if (has(item.detail)) {
        name.appendChild(el("small", null, clean(item.detail)));
      }
      row.appendChild(name);
      row.appendChild(el("span", "price-row__value", clean(item.price)));
      pricesEl.appendChild(row);
    });
  }

  /* Team ----------------------------------------------------------------- */

  var teamEl = document.querySelector("[data-team]");
  var teamHasPhotos = false;
  if (teamEl && Array.isArray(content.team)) {
    content.team.forEach(function (person) {
      var profileUrl = httpUrl(person.profileUrl);
      var card = profileUrl
        ? el("a", "team-card team-card--link reveal")
        : el("article", "team-card reveal");
      if (profileUrl) {
        card.href = profileUrl;
        setLinkTarget(card, profileUrl);
      }

      var media = el("div", "team-card__media");
      var photo = localAsset(person.photo);

      if (photo) {
        teamHasPhotos = true;
        var img = document.createElement("img");
        img.className = "team-card__photo";
        img.src = photo;
        img.alt = "Фото: " + clean(person.name);
        img.width = 480;
        img.height = 600;
        img.loading = "lazy";
        img.decoding = "async";
        media.appendChild(img);
      } else {
        var monogram = el("span", "team-card__monogram", clean(person.initials));
        monogram.setAttribute("aria-hidden", "true");
        media.appendChild(monogram);
      }

      card.appendChild(media);
      card.appendChild(el("h3", "team-card__name", clean(person.name)));
      card.appendChild(el("p", "team-card__role", clean(person.role)));

      if (profileUrl) {
        var profile = el("span", "team-card__profile");
        profile.appendChild(document.createTextNode("Отзывы и профиль"));
        var profileArrow = el("span", "team-card__profile-arrow");
        profileArrow.innerHTML = icon("arrow");
        profile.appendChild(profileArrow);
        card.appendChild(profile);
      }

      teamEl.appendChild(card);
    });
  }

  var teamNote = document.querySelector(".team-note");
  if (teamNote && teamHasPhotos) teamNote.hidden = true;

  /* Reviews -------------------------------------------------------------- */

  var reviewsSection = document.querySelector("[data-reviews-section]");
  var reviewsWidget = document.querySelector("[data-reviews-widget]");
  var reviewsEmbed = httpUrl(config.reviewsEmbedUrl);
  if (reviewsSection && reviewsWidget && reviewsEmbed) {
    var reviewsFrame = document.createElement("iframe");
    reviewsFrame.src = reviewsEmbed;
    reviewsFrame.title = "Отзывы о METE CLINIC на Яндекс Картах";
    reviewsFrame.loading = "lazy";
    reviewsFrame.setAttribute("allowfullscreen", "");
    reviewsWidget.appendChild(reviewsFrame);
    reviewsSection.hidden = false;
  }

  var reviewsLink = document.querySelector("[data-reviews-link]");
  var reviewsPage = httpUrl(config.reviewsUrl);
  if (reviewsLink) {
    if (reviewsPage) {
      reviewsLink.href = reviewsPage;
      reviewsLink.hidden = false;
    } else {
      reviewsLink.remove();
    }
  }

  /* Map ------------------------------------------------------------------ */

  var mapCard = document.querySelector("[data-map-card]");
  var mapFrame = document.querySelector("[data-map-frame]");
  var mapEmbed = httpUrl(config.mapEmbedUrl);
  if (mapCard && mapFrame && mapEmbed) {
    var mapIframe = document.createElement("iframe");
    mapIframe.src = mapEmbed;
    mapIframe.loading = "lazy";
    mapIframe.title = has(config.address)
      ? "Карта: METE CLINIC, " + clean(config.address)
      : "Карта: METE CLINIC";
    mapIframe.setAttribute("allowfullscreen", "");
    mapIframe.setAttribute(
      "sandbox",
      "allow-modals allow-forms allow-scripts allow-same-origin allow-popups allow-top-navigation-by-user-activation"
    );
    mapIframe.setAttribute("referrerpolicy", "no-referrer-when-downgrade");
    mapFrame.appendChild(mapIframe);
    mapCard.hidden = false;

    var provider = clean(config.mapProvider);
    var providerLabel = document.querySelector("[data-map-provider]");
    if (providerLabel) {
      if (provider === "2gis") providerLabel.textContent = "2ГИС";
      else if (provider === "yandex") providerLabel.textContent = "Яндекс Карты";
      else providerLabel.textContent = "";
    }
  }

  var yandexLink = document.querySelector("[data-yandex-link]");
  var yandexUrl = httpUrl(config.yandexMapsUrl);
  if (yandexLink) {
    if (yandexUrl) {
      yandexLink.href = yandexUrl;
      yandexLink.hidden = false;
    } else {
      yandexLink.remove();
    }
  }

  var twoGisLink = document.querySelector("[data-2gis-link]");
  var twoGisUrl = httpUrl(config.twoGisUrl);
  if (twoGisLink) {
    if (twoGisUrl) {
      twoGisLink.href = twoGisUrl;
      twoGisLink.hidden = false;
    } else {
      twoGisLink.remove();
    }
  }

  var contactsGrid = document.querySelector("[data-contacts-grid]");
  if (contactsGrid && mapCard && mapCard.hidden) {
    contactsGrid.classList.add("contacts__grid--single");
  }

  /* Contacts ------------------------------------------------------------- */

  var contactList = document.querySelector("[data-contact-list]");
  var contactsEmpty = document.querySelector("[data-contacts-empty]");
  var contactRows = 0;

  function addContactRow(iconName, label, value, href, linkLabel) {
    var row = el("div", "contact-row");
    var iconBox = el("span", "contact-row__icon");
    if (iconName === "max") {
      var maxLogo = el("span", "max-logo");
      maxLogo.setAttribute("aria-hidden", "true");
      iconBox.appendChild(maxLogo);
    } else {
      iconBox.innerHTML = icon(iconName);
    }
    row.appendChild(iconBox);

    var body = el("div", "contact-row__body");
    body.appendChild(el("span", "contact-row__label", label));

    var dd = el("span", "contact-row__value");
    if (href) {
      var link = el("a", null, value);
      link.href = href;
      if (/^https?:/.test(href)) {
        link.target = "_blank";
        link.rel = "noopener noreferrer";
      }
      if (linkLabel) link.setAttribute("aria-label", linkLabel);
      dd.appendChild(link);
    } else {
      dd.textContent = value;
    }
    body.appendChild(dd);
    row.appendChild(body);
    contactList.appendChild(row);
    contactRows += 1;
  }

  if (contactList) {
    if (has(config.address)) {
      addContactRow("pin", "Адрес", clean(config.address));
    }
    if (has(config.workingHours)) {
      addContactRow("clock", "Часы работы", clean(config.workingHours));
    }
    if (telHref) {
      addContactRow("phone", "Телефон", phoneLabel, telHref);
    }
    if (maxUrl) {
      addContactRow("max", "Мессенджер", "Написать нам", maxUrl, "Написать нам в MAX");
    }
    if (!contactRows && contactsEmpty) {
      contactsEmpty.hidden = false;
    }
  }

  /* Social --------------------------------------------------------------- */

  var socialEl = document.querySelector("[data-social]");
  var socialItems = (Array.isArray(config.socialLinks) ? config.socialLinks : [])
    .map(function (item) {
      return {
        name: clean(item && item.name),
        label: clean(item && item.label) || clean(item && item.name),
        url: httpUrl(item && item.url)
      };
    })
    .filter(function (item) {
      return item.url !== "";
    });

  if (socialEl && socialItems.length) {
    socialItems.forEach(function (item) {
      var link = el("a", "social-link", item.name);
      link.href = item.url;
      link.target = "_blank";
      link.rel = "noopener noreferrer";
      link.setAttribute("aria-label", item.label);
      link.title = item.label;
      socialEl.appendChild(link);
    });
    socialEl.hidden = false;
  }

  /* Year ----------------------------------------------------------------- */

  Array.prototype.forEach.call(document.querySelectorAll("[data-year]"), function (node) {
    node.textContent = String(new Date().getFullYear());
  });

  /* Header state --------------------------------------------------------- */

  var header = document.querySelector("[data-header]");
  function onScroll() {
    if (header) header.classList.toggle("is-scrolled", window.scrollY > 8);
  }
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  /* Mobile menu ---------------------------------------------------------- */

  var menuToggle = document.querySelector("[data-menu-toggle]");
  var menu = document.querySelector("[data-menu]");

  function setMenu(open, returnFocus) {
    if (!menu || !menuToggle) return;
    menuToggle.setAttribute("aria-expanded", open ? "true" : "false");
    menuToggle.setAttribute("aria-label", open ? "Закрыть меню" : "Открыть меню");
    menu.hidden = !open;
    document.body.classList.toggle("is-menu-open", open);
    if (!open && returnFocus) menuToggle.focus();
  }

  if (menu && menuToggle) {
    menuToggle.addEventListener("click", function () {
      setMenu(menu.hidden, false);
    });

    menu.addEventListener("click", function (event) {
      if (event.target.closest("a")) setMenu(false, false);
    });

    document.addEventListener("click", function (event) {
      if (menu.hidden) return;
      if (header && header.contains(event.target)) return;
      setMenu(false, false);
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && !menu.hidden) {
        setMenu(false, true);
      }
    });

    var desktop = window.matchMedia("(min-width: 1000px)");
    var onDesktop = function (event) {
      if (event.matches) setMenu(false, false);
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

  if (!revealEls.length) {
    // nothing to do
  } else if (!("IntersectionObserver" in window) || reducedMotion) {
    revealEls.forEach(function (node) {
      node.classList.add("is-visible");
    });
  } else {
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
  }
})();

