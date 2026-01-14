/**
 *@param url string
 */
function ajaxConfigSelect2(url) {
  return {
    url,
    dataType: "json",
    delay: 250,
    data: function (params) {
      var query = {
        search: params.term,
        page: params.page || 1,
      };
      // Query parameters will be ?search=[term]&page=[page]
      return query;
    },
  };
}

/**
 * Function to initialize single select
 *
 * @param string id
 * @param object config
 */
function initSelect2Single(id, config = {}) {
  const selectElement = $(`#${id}`);

  const singleSelectConfig = {
    allowClear: true,
    tags: false,
    placeholder: "",
  };

  if (config.url) {
    singleSelectConfig.ajax = ajaxConfigSelect2(config.url);
  }

  selectElement.selectmenu("destroy").select2(singleSelectConfig);

  selectElement.on("select2:select", function (e) {
    var selectedText = e.params.data.text;
    $(".select2-selection__rendered").attr("title", selectedText);
  });

  // Prevent open on clear
  if (config.preventOpenOnClear) {
    selectElement.on("select2:unselecting", function (e) {
      $(this).data("unselecting", true);
    });

    selectElement.on("select2:opening", function (e) {
      if ($(this).data("unselecting")) {
        $(this).removeData("unselecting");
        e.preventDefault(); // Prevent dropdown from opening
      }
    });
  }

  selectElement.on("select2:close", function (e) {
    config.triggerFormSubmit();
  });

  selectElement.on("select2:unselect", function (e) {
    config.triggerFormSubmit();
  });
}

/**
 * Function to initialize multi-select
 *
 * @param string id
 * @param object config
 */
function initSelect2Multi(id, config = {}) {
  const selectElement = $(`#${id}`);

  if (selectElement.data("ui-selectmenu")) {
    selectElement.selectmenu("destroy");
  }

  selectElement.next(".ui-selectmenu-button").remove();
  selectElement.css("display", "inline-block");

  const multiSelectConfig = {
    allowClear: true,
    tags: false,
    placeholder: "",
    closeOnSelect: false,
    maximumSelectionLength: 10,
  };

  if (config.url) {
    multiSelectConfig.ajax = ajaxConfigSelect2(config.url);
  }

  selectElement.select2(multiSelectConfig);

  if (config.preventOpenOnClear) {
    selectElement.on("select2:unselecting", function (e) {
      $(this).data("unselecting", true);
    });

    selectElement.on("select2:opening", function (e) {
      if ($(this).data("unselecting")) {
        $(this).removeData("unselecting");
        e.preventDefault(); // Prevent dropdown from opening
      }
    });
  }

  selectElement.on("select2:close", function (e) {
    if (selectElement.val().length) {
      config.triggerFormSubmit();
    }
  });

  selectElement.on("select2:unselect", function (e) {
    config.triggerFormSubmit();
  });
}
