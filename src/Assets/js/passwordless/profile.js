const $ = jQuery;
const checkSupport = () => {
  if (
    !window.PublicKeyCredential ||
    !navigator.credentials.create ||
    typeof navigator.credentials.create !== "function"
  ) {
    $("#v-pwl-btn-start-reg").attr("disabled", "disabled");
    $("#v-pwl-show-progress").text(variables_pwl_profile.browser_not_support);
  }
};

function base64UrlToBase64(payload) {
  payload = payload.replace(/=/g, "").replace(/-/g, "+").replace(/_/g, "/");
  const pad = payload.length % 4;
  if (pad) {
    if (pad === 1) {
      throw new Error("Payload not invalid");
    }
    payload += new Array(5 - pad).join("=");
  }
  return payload;
}

function array2Base64(value) {
  return btoa(String.fromCharCode(...value));
}

function onRename(id, current_name) {
  let new_name = prompt(
    variables_pwl_profile.rename_authenticator,
    current_name
  );

  if (!new_name || !new_name.trim()) {
    alert(variables_pwl_profile.enter_name);
  } else if (new_name !== null && new_name !== current_name) {
    $(`#${id}`).text(variables_pwl_profile.renaming);
    $.ajax({
      url: variables_pwl_profile.ajax_uri,
      type: "GET",
      data: {
        action: "v_pwl_update_authenticator",
        id: id,
        name: new_name,
        target: "rename",
        user_id: variables_pwl_profile.user_id,
      },
      success: function () {
        updateList();
      },
      error: function (data) {
        alert(`Error: ${data}`);
        updateList();
      },
    });
  }
}

function onDelete(id, name) {
  if (
    confirm(
      variables_pwl_profile.confirm_delete +
        name +
        ($("#v-pwl-list > tr").length === 1
          ? "\n" + variables_pwl_profile.after_removing
          : "")
    )
  ) {
    $(`#${id}`).text(variables_pwl_profile.removing);
    $.ajax({
      url: variables_pwl_profile.ajax_uri,
      type: "GET",
      data: {
        action: "v_pwl_update_authenticator",
        id: id,
        target: "remove",
        user_id: variables_pwl_profile.user_id,
      },
      success: function () {
        updateList();
      },
      error: function (data) {
        alert(`Error: ${data}`);
        updateList();
      },
    });
  }
}

window.addEventListener("load", () => {
  if (document.getElementById("v-pwl-error-container")) {
    document
      .getElementById("v-pwl-error-container")
      .insertBefore(document.getElementById("v-pwl-error"), null);
  }
});

const table_column =
  $(".username-less-th").css("display") === "none" ? "5" : "6";

// Update authenticator list
function updateList() {
  $.ajax({
    url: variables_pwl_profile.ajax_uri,
    type: "GET",
    data: {
      action: "v_pwl_get_list_authenticator",
      user_id: variables_pwl_profile.user_id,
    },
    success: function (data) {
      if (typeof data === "string") {
        // add column
        $("#v-pwl-list").html(
          `<tr><td colspan="${table_column}">${variables_pwl_profile.load_failed}</td></tr>`
        );
        return;
      }
      if (!data.length) {
        if (configs.usernameless === "true") {
          $(".username-less-th, .username-less-td").show();
        } else {
          $(".username-less-th, .username-less-td").hide();
        }
        $("#v-pwl-list").html(
          `<tr><td colspan="${table_column}">${variables_pwl_profile.no_registered}</td></tr>`
        );
        $("#v_pwl_usernameless_tip").text("").hide();
        return;
      }

      let is_usernameless = false;
      let auth_list_html = "";
      for (item of data) {
        let item_type_disabled = false;
        if (item.usernameless) {
          is_usernameless = true;
        }

        auth_list_html += `<tr>
                          <td>${item.name}</td>
                          <td>
                              ${
                                item.type === "none"
                                  ? variables_pwl_profile.any_type
                                  : item.type === "platform"
                                  ? variables_pwl_profile.platform_type
                                  : variables_pwl_profile.roaming_type
                              }${
          item_type_disabled ? variables_pwl_profile.disabled : ""
        }
                          </td>
                          <td class="username-less-td">
                              ${
                                item.usernameless
                                  ? variables_pwl_profile.ready +
                                    (configs.usernameless === "true"
                                      ? ""
                                      : variables_pwl_profile.unavailable)
                                  : variables_pwl_profile.no
                              }
                          </td>
                          <td>${item.added}</td>
                          <td id="${item.key}">
            <a href="javascript:onRename('${item.key}', '${item.name}')">${
          variables_pwl_profile.rename
        }</a> | <a href="javascript:onDelete('${item.key}', '${item.name}')">${
          variables_pwl_profile.remove
        }</a>
        </td>
        </tr>`;
      }

      // append data to tabled
      $("#v-pwl-list").html(auth_list_html);

      //show username less
      if (is_usernameless || configs.usernameless === "true") {
        $(".username-less-th, .username-less-td").show();
      } else {
        $(".username-less-th, .username-less-td").hide();
      }
      if (is_usernameless && configs.usernameless !== "true") {
        $("#v_pwl_usernameless_tip").text(
          variables_pwl_profile.disabled_usernameless
        );
        $("#v_pwl_usernameless_tip").show();
      } else {
        $("#v_pwl_usernameless_tip").text("");
        $("#v_pwl_usernameless_tip").hide();
      }
    },
    error: function () {
      $("#v-pwl-list").html(
        `<tr><td colspan="${table_column}">${variables_pwl_profile.load_failed}</td></tr>`
      );
    },
  });
}

// onEnter
$("#v-pwl-authenticator-name").keydown((event) => {
  if (event.keyCode === 13) {
    $("#v-pwl-btn-start-reg").trigger("click");
    event.preventDefault();
  }
});

$("#v-pwl-reg-new-btn").click((event) => {
  event.preventDefault();
  $("#vincss-secure-new-block").show();
  $("#vincss-secure-verify-block").hide();
});

$(".vincss-secure-cancel").click((event) => {
  event.preventDefault();
  $("#vincss-secure-new-block").hide();
  $("#vincss-secure-verify-block").hide();
});

$("#vincss-secure-verify-btn").click((event) => {
  event.preventDefault();
  $("#vincss-secure-new-block").hide();
  $("#vincss-secure-verify-block").show();
});

$("#v-pwl-btn-start-reg").click((event) => {
  event.preventDefault();
  if (!$("#v-pwl-authenticator-name").val()?.trim()) {
    alert(variables_pwl_profile.enter_name);
    return;
  }

  // disable when loading
  $("#v-pwl-show-progress").html(variables_pwl_profile.initializing);
  $("#v-pwl-btn-start-reg").attr("disabled", true);
  $("#v-pwl-authenticator-name").attr("disabled", true);
  $(".vincss_fido2_login_authenticator_usernameless").attr("disabled", true);
  $("#v-pwl-type").attr("disabled", true);

  $.ajax({
    url: variables_pwl_profile.ajax_uri,
    type: "GET",
    data: {
      action: "v_pwl_create_request",
      name: $("#v-pwl-authenticator-name").val(),
      type: $("#v-pwl-type").val(),
      usernameless: $(
        ".vincss_fido2_login_authenticator_usernameless:checked"
      ).val()
        ? $(".vincss_fido2_login_authenticator_usernameless:checked").val()
        : "false",
      user_id: variables_pwl_profile.user_id,
    },
    success: function (data) {
      if (typeof data === "string") {
        console.warn(data);
        $("#v-pwl-show-progress").html(
          `${variables_pwl_profile.registration_failed}: ${data}`
        );
        $("#v-pwl-btn-start-reg").removeAttr("disabled");
        $("#v-pwl-authenticator-name").removeAttr("disabled");
        $(".vincss_fido2_login_authenticator_usernameless").removeAttr(
          "disabled"
        );
        $("#v-pwl-type").removeAttr("disabled");
        updateList();
        return;
      }
      $("#v-pwl-show-progress").text(variables_pwl_profile.flow_instructions);
      let challenge = new Uint8Array(32);
      let user_id = new Uint8Array(32);
      challenge = Uint8Array.from(
        window.atob(base64UrlToBase64(data.challenge)),
        (c) => c.charCodeAt(0)
      );
      user_id = Uint8Array.from(
        window.atob(base64UrlToBase64(data.user.id)),
        (c) => c.charCodeAt(0)
      );

      let public_key = {
        challenge: challenge,
        rp: {
          id: data.rp.id,
          name: data.rp.name,
        },
        user: {
          id: user_id,
          name: data.user.name,
          displayName: data.user.displayName,
        },
        pubKeyCredParams: data.pubKeyCredParams,
        authenticatorSelection: data.authenticatorSelection,
        timeout: data.timeout,
      };

      // If some authenticators are already registered, exclude
      if (data.excludeCredentials) {
        public_key.excludeCredentials = data.excludeCredentials.map((item) => {
          item.id = Uint8Array.from(
            window.atob(base64UrlToBase64(item.id)),
            (c) => c.charCodeAt(0)
          );
          return item;
        });
      }

      // Save client ID
      const clientID = data.clientID;
      delete data.clientID;

      // Create, a pop-up window should appear
      navigator.credentials
        .create({ publicKey: public_key })
        .then((newCredentialInfo) => {
          $("#v-pwl-show-progress").html(variables_pwl_profile.registrating);
          return newCredentialInfo;
        })
        .then((data) => {
          // Code Uint8Array into string for transmission
          const publicKeyCredential = {
            id: data.id,
            type: data.type,
            rawId: array2Base64(new Uint8Array(data.rawId)),
            response: {
              clientDataJSON: array2Base64(
                new Uint8Array(data.response.clientDataJSON)
              ),
              attestationObject: array2Base64(
                new Uint8Array(data.response.attestationObject)
              ),
            },
          };
          return publicKeyCredential;
        })
        .then(JSON.stringify)
        .then((auth_res) => {
          $.ajax({
            url: `${variables_pwl_profile.ajax_uri}?action=v_pwl_create_request_response`,
            type: "POST",
            data: {
              data: window.btoa(auth_res),
              name: $("#v-pwl-authenticator-name").val(),
              type: $("#v-pwl-type").val(),
              usernameless: $(
                ".vincss_fido2_login_authenticator_usernameless:checked"
              ).val()
                ? $(
                    ".vincss_fido2_login_authenticator_usernameless:checked"
                  ).val()
                : "false",
              clientid: clientID,
              user_id: variables_pwl_profile.user_id,
            },
            success: function (data) {
              if (data === "true") {
                // Registered
                $("#v-pwl-show-progress").html("");
                $("#v-pwl-btn-start-reg").removeAttr("disabled");
                $("#v-pwl-authenticator-name").removeAttr("disabled");
                $("#v-pwl-authenticator-name").val("");
                $(".vincss_fido2_login_authenticator_usernameless").removeAttr(
                  "disabled"
                );
                $("#v-pwl-type").removeAttr("disabled");
                $("#vincss-secure-new-block").hide();
                updateList();
              } else {
                // Register failed
                $("#v-pwl-show-progress").html(
                  variables_pwl_profile.registration_failed
                );
                $("#v-pwl-btn-start-reg").removeAttr("disabled");
                $("#v-pwl-authenticator-name").removeAttr("disabled");
                $(".vincss_fido2_login_authenticator_usernameless").removeAttr(
                  "disabled"
                );
                $("#v-pwl-type").removeAttr("disabled");
                updateList();
              }
            },
            error: function () {
              $("#v-pwl-show-progress").html(
                variables_pwl_profile.registration_failed
              );
              $("#v-pwl-btn-start-reg").removeAttr("disabled");
              $("#v-pwl-authenticator-name").removeAttr("disabled");
              $(".vincss_fido2_login_authenticator_usernameless").removeAttr(
                "disabled"
              );
              $("#v-pwl-type").removeAttr("disabled");
              updateList();
            },
          });
        })
        .catch((error) => {
          // Creation abort
          console.warn(error);
          $("#v-pwl-show-progress").html(
            `${variables_pwl_profile.registration_failed}: ${error}`
          );
          $("#v-pwl-btn-start-reg").removeAttr("disabled");
          $("#v-pwl-authenticator-name").removeAttr("disabled");
          $(".vincss_fido2_login_authenticator_usernameless").removeAttr(
            "disabled"
          );
          $("#v-pwl-type").removeAttr("disabled");
          updateList();
        });
    },
    error: function () {
      $("#v-pwl-show-progress").html(variables_pwl_profile.registration_failed);
      $("#v-pwl-btn-start-reg").removeAttr("disabled");
      $("#v-pwl-authenticator-name").removeAttr("disabled");
      $(".vincss_fido2_login_authenticator_usernameless").removeAttr(
        "disabled"
      );
      $("#v-pwl-type").removeAttr("disabled");
      updateList();
    },
  });
});

$(() => {
  checkSupport();
  updateList();
});
