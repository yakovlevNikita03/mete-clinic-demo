/**
 * MÉTE CLINIC — админка.
 *
 * Задачи: вкладки настроек, автоподстановка телефона, проверка ссылок,
 * зависимые поля цены, изображение категории, быстрое редактирование,
 * массовая смена категории и перетаскивание порядка.
 */
(function () {
  "use strict";

  var config = window.mccAdmin || {};
  var strings = config.strings || {};

  function str(key, fallback) {
    return strings[key] || fallback || "";
  }

  /* Вкладки настроек ----------------------------------------------------- */

  var tabs = document.querySelectorAll("[data-mcc-tab]");

  Array.prototype.forEach.call(tabs, function (tab) {
    tab.addEventListener("click", function (event) {
      event.preventDefault();

      var name = tab.getAttribute("data-mcc-tab");

      Array.prototype.forEach.call(tabs, function (item) {
        item.classList.toggle("nav-tab-active", item === tab);
      });

      Array.prototype.forEach.call(document.querySelectorAll("[data-mcc-panel]"), function (panel) {
        panel.hidden = panel.getAttribute("data-mcc-panel") !== name;
      });

      if (window.history && window.history.replaceState) {
        window.history.replaceState(null, "", tab.href);
      }
    });
  });

  /* Телефон: tel-ссылка и предпросмотр ---------------------------------- */

  var phoneInput = document.querySelector('[data-mcc-setting="phone"]');

  if (phoneInput) {
    var telInput = document.querySelector('[data-mcc-setting="phone_tel"]');
    var preview = document.querySelector("[data-mcc-phone-preview]");

    var digits = function (value) {
      var clean = String(value || "").replace(/[^0-9+]/g, "");
      clean = clean.replace(/(?!^)\+/g, "");
      return clean;
    };

    // Своя ссылка не перезаписывается: синхронизируем, только пока значение
    // совпадает с телефоном (в том числе при первом открытии страницы).
    var telManual = !!(
      telInput &&
      telInput.value.trim() !== "" &&
      telInput.value.trim() !== digits(phoneInput.value)
    );

    var syncPhone = function () {
      var value = phoneInput.value;

      if (preview) {
        preview.textContent = value;
      }

      if (!telInput || telManual) {
        return;
      }

      telInput.value = digits(value);
    };

    phoneInput.addEventListener("input", syncPhone);

    if (telInput) {
      telInput.addEventListener("input", function () {
        telManual = telInput.value.trim() !== "";
      });
    }
  }

  /* Проверка ссылок ------------------------------------------------------ */

  Array.prototype.forEach.call(document.querySelectorAll(".mcc-check-link"), function (button) {
    button.addEventListener("click", function (event) {
      event.preventDefault();

      var source = document.getElementById(button.getAttribute("data-mcc-link-source"));
      var wrap = button.parentNode;
      var result = wrap ? wrap.querySelector("[data-mcc-link-result]") : null;

      if (!source) {
        return;
      }

      var url = source.value.trim();

      if (!result) {
        return;
      }

      if (url === "") {
        result.textContent = str("emptyUrl");
        result.className = "mcc-link-result is-error";
        return;
      }

      if (!window.fetch) {
        return;
      }

      result.textContent = str("checking");
      result.className = "mcc-link-result";

      var body = new window.URLSearchParams();
      body.append("action", "mcc_check_link");
      body.append("nonce", config.nonce || "");
      body.append("url", url);

      window.fetch(config.ajaxUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString()
      }).then(function (response) {
        return response.json();
      }).then(function (payload) {
        var data = payload && payload.data ? payload.data : {};

        result.textContent = data.message || (payload && payload.success ? str("linkOk") : str("linkFail"));
        result.className = "mcc-link-result " + (payload && payload.success ? "is-ok" : "is-error");
      }).catch(function () {
        result.textContent = str("linkFail");
        result.className = "mcc-link-result is-error";
      });
    });
  });

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
    var previewImage = wrap.querySelector("[data-mcc-media-preview]");
    var remove = wrap.querySelector("[data-mcc-media-remove]");

    if (input) {
      input.value = id || "";
    }

    if (previewImage) {
      if (url) {
        previewImage.src = url;
        previewImage.hidden = false;
      } else {
        previewImage.removeAttribute("src");
        previewImage.hidden = true;
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

  /* Быстрое редактирование услуги ---------------------------------------- */

  if (window.jQuery) {
    var $ = window.jQuery;

    $(document).on("click", ".editinline", function () {
      var row = $(this).closest("tr");
      var id = (row.attr("id") || "").replace("post-", "");
      var data = row.find(".mcc-qe-data").first();

      if (!id || !data.length) {
        return;
      }

      window.setTimeout(function () {
        var form = $("#edit-" + id);

        if (!form.length) {
          return;
        }

        form.find('select[name="mcc_qe_price_type"]').val(data.attr("data-type") || "fixed");
        form.find('input[name="mcc_qe_price"]').val(data.attr("data-price") || "");
        form.find('input[name="mcc_qe_price_max"]').val(data.attr("data-price-max") || "");
        form.find('input[name="mcc_qe_duration"]').val(data.attr("data-duration") || "");
        form.find('input[name="mcc_qe_home_order"]').val(data.attr("data-home-order") || "0");
        form.find('input[name="mcc_qe_show_home"]').prop("checked", data.attr("data-show-home") === "1");
        form.find('input[name="mcc_qe_price"]').prop("disabled", false);
        form.find('input[name="mcc_qe_price_max"]').prop("disabled", false);
      }, 60);
    });
  }

  /* Массовая смена категории --------------------------------------------- */

  if (config.categories && config.categories.length) {
    Array.prototype.forEach.call(
      document.querySelectorAll("#bulk-action-selector-top, #bulk-action-selector-bottom"),
      function (select) {
        var form = select.form;

        if (!form || form.querySelector("[data-mcc-bulk-cat]")) {
          return;
        }

        var category = document.createElement("select");
        var empty = document.createElement("option");

        category.name = "mcc_bulk_cat";
        category.setAttribute("data-mcc-bulk-cat", "1");
        category.className = "mcc-bulk-cat";
        category.hidden = true;

        empty.value = "";
        empty.textContent = str("bulkCategory", "Категория");
        category.appendChild(empty);

        config.categories.forEach(function (item) {
          var option = document.createElement("option");
          option.value = String(item.id);
          option.textContent = item.name;
          category.appendChild(option);
        });

        select.parentNode.insertBefore(category, select.nextSibling);

        var toggle = function () {
          category.hidden = select.value !== "mcc_change_category";
        };

        select.addEventListener("change", toggle);
        toggle();

        form.addEventListener("submit", function (event) {
          if (select.value === "mcc_change_category" && category.value === "") {
            event.preventDefault();
            window.alert(str("chooseCat"));
            category.focus();
          }
        });
      }
    );
  }

  /* Перетаскивание порядка ----------------------------------------------- */

  if (window.jQuery && config.sort && config.sort.postType) {
    var sort = config.sort;
    var list = window.jQuery("#the-list");
    var params = new window.URLSearchParams(window.location.search);
    var manualSort = params.has("orderby") || params.has("s");

    if (list.length && !manualSort) {
      window.jQuery("body").addClass("mcc-sortable-list");
      list.find("tr").each(function () {
        var cell = window.jQuery(this).find("td.column-title, td.column-name").first();

        if (cell.length && !cell.find(".mcc-drag-handle").length) {
          cell.prepend(
            '<span class="mcc-drag-handle dashicons dashicons-menu" aria-hidden="true" title="' +
              str("sortHandle", "Перетащите, чтобы изменить порядок.") +
              '"></span>'
          );
        }
      });

      list.sortable({
        items: "> tr",
        handle: ".mcc-drag-handle",
        axis: "y",
        cursor: "move",
        update: function () {
          var ids = [];
          list.find("tr").each(function () {
            var id = (window.jQuery(this).attr("id") || "").replace("post-", "");

            if (id) {
              ids.push(id);
            }
          });

          var body = new window.URLSearchParams();
          body.append("action", "mcc_reorder");
          body.append("nonce", config.reorderNonce || "");
          body.append("post_type", sort.postType);
          body.append("field", sort.field || "menu_order");
          body.append("paged", params.get("paged") || "1");
          ids.forEach(function (id) {
            body.append("ids[]", id);
          });

          window.fetch(config.ajaxUrl, {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
            body: body.toString()
          }).then(function (response) {
            return response.json();
          }).then(function () {
            list.addClass("mcc-sort-saved");
            window.setTimeout(function () {
              list.removeClass("mcc-sort-saved");
            }, 1200);
          }).catch(function () {
            window.alert(str("sortFailed"));
          });
        }
      });
    }
  }
})();
