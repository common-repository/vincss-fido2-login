function setDefaultValue(value) {
  if (value === "passwordless") {
    jQuery("#des_passwordless_only").show();
  } else {
    jQuery("#des_passwordless_only").hide();
  }
}

jQuery(document).ready(function () {
  const login_method_value = jQuery("select#login_method").val();
  setDefaultValue(login_method_value);
  jQuery("select#login_method").change(function () {
    const value = jQuery(this).val();
    setDefaultValue(value);
  });

  //   // Check config
  jQuery("#v_passwordless_environment_check").click(function () {
    jQuery("#v_passwordless_environment_check").addClass("button-primary");
    jQuery("#v_error_list").text("");
    jQuery("#v_error_list").hide();
    const _wpnonce = jQuery(this).attr("data-nonce");
    jQuery("#v_loading_env_check").show();
    jQuery("#v_txt_environment_check").text("Checking");
    jQuery.ajax({
      type: "GET",
      dataType: "json",
      url: variables_pwl_settings.ajax_uri,
      data: {
        action: "v_pwl_check_environment",
        _wpnonce,
      },
      complete: function () {
        jQuery("#v_txt_environment_check").text("Checked");
        jQuery("#v_loading_env_check").css("display", "none");
        jQuery("#env_check_desc").css("display", "none");
        jQuery("#v_passwordless_environment_check").removeClass(
          "button-primary"
        );
      },
      success: function () {
        jQuery("#v_env_success").show();
      },
      error: function (data) {
        if (
          data.responseJSON &&
          data.responseJSON.data &&
          data.responseJSON.data.error
        ) {
          jQuery("#v_passwordless_opton").attr("disabled", true);
          const errors = data.responseJSON.data.error;
          errors.forEach((e) => {
            jQuery("#v_error_list").append(
              `<p><strong>Error:</strong> ${e}</p>`
            );
          });
          jQuery("#v_error_list").show();
        }
      },
    });
  });
});

jQuery(() => {
  let div = document.getElementById("vincss_fido2_login_log");
  if (div !== null) {
    div.scrollTop = div.scrollHeight;
    if (jQuery("#vincss-secure-remove-log").length === 0) {
      setInterval(() => {
        updateLog();
      }, 5000);
    }
  }
});

// Update log
function updateLog() {
  if (jQuery("#vincss_fido2_login_log").length === 0) {
    return;
  }
  jQuery.ajax({
    url: variables_pwl_settings.ajax_uri,
    type: "GET",
    data: {
      action: "v_pwl_get_logs",
    },
    success: function (data) {
      if (typeof data === "string") {
        console.warn(data);
        jQuery("#vincss_fido2_login_log").text(variables_pwl_settings.i18n_8);
        return;
      }
      if (data.length === 0) {
        document.getElementById("clear_log").disabled = true;
        jQuery("#vincss_fido2_login_log").text("");
        jQuery("#vincss-secure-remove-log").remove();
        jQuery("#log-count").text(variables_pwl_settings.log_count + "0");
        return;
      }
      document.getElementById("clear_log").disabled = false;
      let data_str = data.join("\n");
      if (data_str !== jQuery("#vincss_fido2_login_log").text()) {
        jQuery("#vincss_fido2_login_log").text(data_str);
        jQuery("#log-count").text(
          variables_pwl_settings.log_count + data.length
        );
        let div = document.getElementById("vincss_fido2_login_log");
        div.scrollTop = div.scrollHeight;
      }
    },
    error: function () {
      jQuery("#vincss_fido2_login_log").text(variables_pwl_settings.i18n_8);
    },
  });
}

// Clear log
jQuery("button#clear_log").click((e) => {
  console.log(e);
  e.preventDefault();
  // document.getElementById("clear_log").disabled = true;
  jQuery.ajax({
    url: variables_pwl_settings.ajax_uri,
    type: "GET",
    data: {
      action: "v_pwl_clear_logs",
    },
    success: function () {
      updateLog();
    },
    error: function (data) {
      document.getElementById("clear_log").disabled = false;
      alert(`Error: ${data}`);
      updateLog();
    },
  });
});
