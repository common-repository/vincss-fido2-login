jQuery(document).ready(function () {
  const $ = jQuery;
  const { ajaxURL } = variables_2fa;

  $(document).on("click", "#v_enable_2fa", function (e) {
    $("#setup_2fa_loading").css("display", "inline-block");
    const _wpnonce = $(this).attr("data-nonce");
    const payload = {};
    $.each($("#setup-2fa-form input").serializeArray(), function (_, input) {
      payload[input.name] = input.value;
    });
    window.location.href;
    $.ajax({
      type: "POST",
      dataType: "json",
      url: ajaxURL,
      data: {
        action: "vincss_verify_2fa_otp",
        payload,
        _wpnonce,
      },
      complete: function (res) {
        $("#setup_2fa_loading").hide();
        if (res.responseJSON.success) {
          $("#vincss-2fa-modal-setup").modal("hide");
          window.location.reload();
        } else {
          $("#error_2fa_setup").text(res.responseJSON.data.error);
        }
      },
    });
  });

  $(document).on("click", "#v_remove_2fa", function (e) {
    $("#remove_2fa_loading").css("display", "inline-block");
    e.preventDefault();
    const _wpnonce = $(this).attr("data-nonce");
    const user_id = $("#v_remove_2fa").attr("user-id");
    window.location.href;
    $.ajax({
      type: "POST",
      dataType: "json",
      url: ajaxURL,
      data: {
        action: "vincss_remove_2fa",
        user_id,
        _wpnonce,
      },
      complete: function (res) {
        $("#remove_2fa_loading").hide();
        if (res.responseJSON.success) {
          window.location.reload();
        } else {
          $("#error_2fa_remove").text(res.responseJSON.data.error);
        }
      },
    });
  });

  const login_method_enforce = $("select[name=enforce_2fa]").val();
  if (login_method_enforce === "roles") {
    $("#role_enforce_2fa_list").css("display", "block");
  }

  $("select[name=enforce_2fa]").change(function () {
    const value = $(this).val();
    if (value === "roles") {
      $("#role_enforce_2fa_list").css("display", "block");
    } else {
      $("#role_enforce_2fa_list").hide();
    }
  });

  $(document).on("click", "#v_recovery_2fa", function (e) {
    $(".v_list_code_recovery ul").empty();
    $(".v_list_code_recovery").hide();

    $("#vincss-2fa-generate-backup-codes").modal("show");
    $("#loading_recovery_code").css("display", "flex");
    const user_id = $("#v_recovery_2fa").attr("user-id");
    const _wpnonce = $(this).attr("data-nonce");
    $.ajax({
      type: "POST",
      dataType: "json",
      url: ajaxURL,
      data: {
        action: "vincss_generate_code_recovery",
        user_id,
        _wpnonce,
      },
      complete: function (res) {
        $("#loading_recovery_code").hide();
        if (res.responseJSON.success) {
          const { codes } = res.responseJSON.data;
          codes.forEach((code) => {
            $(".v_list_code_recovery ul").append(`<li>${code}</li>`);
          });
          $(".v_list_code_recovery").css("display", "block");
        } else {
          $("#error_2fa_recovery").text(res.responseJSON.data.error);
        }
      },
    });
  });

  $("#is_backup_code").click(function () {
    const value = $(this).attr("v-value");
    $("input[name=is_backup_code]").val(value === "true" ? "false" : "true");
    if (value === "true") {
      $(this).text("Disable");
      $(this).removeClass("button-primary");
      $(this).attr("v-value", "true");
    } else {
      $(this).text("Enable");
      $(this).addClass("button-primary");
      $(this).attr("v-value", "false");
    }
  });
});
