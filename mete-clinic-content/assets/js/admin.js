/**
 * MÉTE CLINIC — админка.
 *
 * Две задачи: показать только те поля цены, которые нужны выбранному типу,
 * и дать выбрать изображение категории из «Медиафайлов».
 */
(function () {
  "use strict";

  var config = window.mccAdmin || {};

  /* Зависимые поля цены -------------------------------------------------- */

  var typeSelect = document.querySelector("[data-mcc-price-type]");

  if (typeSelect) {
    var priceLabel = document.querySelector("[data-mcc-price-label]");
    var priceHint = document.querySelector("[data-mcc-price-hint]");
    var priceField = document.querySelector("[data-mcc-price-field]");
    var maxField = document.querySelector('[data-mcc-price-extra="range"]');
    var labels = config.priceLabels || {};
    var hints = config.priceHints || {};

    var syncPrice = function () {
      var type = typeSelect.value;

      if (priceField) {
        priceField.hidden = type === "free";
      }

      if (maxField) {
        maxField.hidden = type !== "range";
      }

      if (priceLabel && labels[type]) {
        priceLabel.textContent = labels[type];
      }

      if (priceHint && hints[type]) {
        priceHint.textContent = hints[type];
      }
    };

    typeSelect.addEventListener("change", syncPrice);
    syncPrice();
  }

  /* Изображение категории ------------------------------------------------ */

  var mediaFrames = {};

  function closestWrap(node) {
    return node && node.closest ? node.closest("[data-mcc-media]") : null;
  }

  function setImage(wrap, id, url) {
    var input = wrap.querySelector("[data-mcc-media-input]");
    var preview = wrap.querySelector("[data-mcc-media-preview]");
    var remove = wrap.querySelector("[data-mcc-media-remove]");

    if (input) {
      input.value = id || "";
    }

    if (preview) {
      if (url) {
        preview.src = url;
        preview.hidden = false;
      } else {
        preview.removeAttribute("src");
        preview.hidden = true;
      }
    }

    if (remove) {
      remove.hidden = !url;
    }
  }

  Array.prototype.forEach.call(document.querySelectorAll("[data-mcc-media-select]"), function (button) {
    button.addEventListener("click", function (event) {
      event.preventDefault();

      if (!window.wp || !window.wp.media) {
        return;
      }

      var wrap = closestWrap(button);

      if (!wrap) {
        return;
      }

      if (!mediaFrames.frame) {
        mediaFrames.frame = window.wp.media({
          title: config.mediaTitle || "Изображение",
          button: { text: config.mediaButton || "Использовать изображение" },
          multiple: false,
          library: { type: "image" }
        });

        mediaFrames.frame.on("select", function () {
          var selection = mediaFrames.frame.state().get("selection").first();
          var attachment = selection ? selection.toJSON() : null;

          if (!attachment) {
            return;
          }

          var target = mediaFrames.target;
          var size = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium : null;
          var url = size ? size.url : attachment.url;

          if (target && target.isConnected) {
            setImage(target, attachment.id, url);
          }
        });
      }

      mediaFrames.target = wrap;
      mediaFrames.frame.open();
    });
  });

  Array.prototype.forEach.call(document.querySelectorAll("[data-mcc-media-remove]"), function (button) {
    button.addEventListener("click", function (event) {
      event.preventDefault();

      var wrap = closestWrap(button);

      if (wrap) {
        setImage(wrap, "", "");
      }
    });
  });
})();
