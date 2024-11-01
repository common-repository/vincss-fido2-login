const queryDOM = (selector, callback = () => {}) => {
  const dom_list = $(selector);
  for (let dom of dom_list) {
    callback(dom);
  }
};

const initRequest = function () {
  let xmlHttpReq = new XMLHttpRequest();
  return {
    get: (url, data = "", callback = () => {}) => {
      xmlHttpReq.open("GET", url + data, true);
      xmlHttpReq.send();
      xmlHttpReq.onreadystatechange = () => {
        if (xmlHttpReq.readyState === 4 && xmlHttpReq.status === 200) {
          callback(xmlHttpReq.responseText, true);
        } else if (xmlHttpReq.readyState === 4) {
          callback("Network Error.", false);
        }
      };
    },
    post: (url, data = "", callback = () => {}) => {
      xmlHttpReq.open("POST", url, true);
      xmlHttpReq.setRequestHeader(
        "Content-type",
        "application/x-www-form-urlencoded"
      );
      xmlHttpReq.send(data);
      xmlHttpReq.onreadystatechange = () => {
        if (xmlHttpReq.readyState === 4 && xmlHttpReq.status === 200) {
          callback(xmlHttpReq.responseText, true);
        } else if (xmlHttpReq.readyState === 4) {
          callback("Network Error.", false);
        }
      };
    },
  };
};

let isSupported = true;
$ = jQuery;
document.addEventListener("DOMContentLoaded", () => {
  if (
    $("#lostpasswordform, #registerform, .admin-email-confirm-form").length > 0
  ) {
    return;
  }

  const notice_error = `<div id="login_error" class="notice_error" style="display: none"></div>`;
  $(notice_error).insertBefore("form[name=loginform]");

  const button_check = `<button type="button" class="button button-large button-primary" id="pwl_login">${pwl_vars.login}</button>`;

  let txtToggle = "";
  if (pwl_vars.passwordless_only !== "true") {
    txtToggle = pwl_vars.toggle_pwl_login;
    if (pwl_vars.login_method === "true") {
      txtToggle = pwl_vars.toggle_username_pass_login;
    }
  }
  const button_toggle = `<div id="vincss-fido2-login"><a type="button" id="other_login_helper">${txtToggle}
  </a></div>`;

  const submit = $("p.submit")[0];
  if (submit && pwl_vars.passwordless_only !== "true") {
    $(".thirdparty_login").show();
    $(".other-login").append(button_toggle);
  }
  $(button_check).insertBefore("p.submit");

  const notice = `<div class="pwl-title" style="margin-bottom: 12px; font-size: 16px;">${pwl_vars.title_auth_with_pwl}</div>`;
  $(notice).insertBefore($("form[name=loginform] p").first());

  let btnWidth = document.getElementById("wp-submit")
    ? document.getElementById("wp-submit").clientWidth
    : 0;
  if (btnWidth < 20 || btnWidth === undefined) {
    queryDOM("#pwl_login", (dom) => {
      dom.style.width = "auto";
    });
  } else {
    queryDOM("#pwl_login", (dom) => {
      dom.style.width = btnWidth;
    });
  }

  // if browser not support
  if (
    !window.PublicKeyCredential ||
    !navigator.credentials.create ||
    typeof navigator.credentials.create !== "function"
  ) {
    isSupported = false;
    $("#vincss-fido2-login").hide();
  }

  $("#pwl_login").click(check);
  $("#vincss-fido2-login").click(toggle);
});

window.onresize = function () {
  if ($("#lostpasswordform, #registerform").length > 0) {
    return;
  }
  // auto resize
  const innerWidth = $("#wp-submit").innerWidth();
  if (innerWidth < 20 || innerWidth === undefined) {
    $("#pwl_login").css("width", "auto");
  } else {
    $("#pwl_login").css("width", innerWidth);
  }
};

document.addEventListener("keydown", onEnter, false);
function onEnter(event) {
  if (isSupported && $("#pwl_login").get(0).style.display === "block") {
    if (event.keyCode === 13) {
      event.preventDefault();
      $(".notice_error").hide();
      $("#pwl_login").click();
    }
  }
}

function array2Base64(value) {
  return btoa(String.fromCharCode(...value));
}

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

function getQueryString(name) {
  let reg = new RegExp(`(^|&)${name}=([^&]*)(&|$)`, "i");
  let reg_rewrite = new RegExp(`(^|/)${name}/([^/]*)(/|$)`, "i");
  let r = window.location.search.substr(1).match(reg);
  let q = window.location.pathname.substr(1).match(reg_rewrite);
  if (r != null) {
    return unescape(r[2]);
  } else if (q != null) {
    return unescape(q[2]);
  } else {
    return null;
  }
}

function toggle() {
  const helper_link = $("#other_login_helper");

  if ($("#lostpasswordform, #registerform").length > 0) {
    return;
  }
  if (isSupported) {
    const isShowPwlTitle = $(".pwl-title").get(0).style.display === "block";

    if (isShowPwlTitle) {
      helper_link.text(pwl_vars.toggle_pwl_login);
      if ($(".user-pass-wrap").length > 0) {
        $(".user-pass-wrap, .forgetmenot, #wp-submit").show();
      } else {
        $(".forgetmenot, #wp-submit").show();
        $("#loginform p").get(1).show();
      }

      const labelLoginForm = $("#loginform label");
      const usernameLabel = $("#vincss-secure-username-label");
      if (labelLoginForm.length > 0) {
        if (usernameLabel) {
          // For WordPress 5.2-
          usernameLabel.text(pwl_vars.label_username);
        } else {
          labelLoginForm[0].text(pwl_vars.label_username);
        }
      }

      // hide title passwordless login
      $(".pwl-title").hide();
      // hide btn check
      $("#pwl_login").hide();

      $("#user_pass").prop("disabled", false);
      $("#user_login").focus();
      $("#wp-submit").prop("disabled", false);
    } else {
      helper_link.text(pwl_vars.toggle_username_pass_login);
      if ($(".user-pass-wrap").length > 0) {
        $(".user-pass-wrap, #wp-submit").hide();
        if (pwl_vars.remember_login === "false") {
          $(".forgetmenot").hide();
        }
      } else {
        $("#wp-submit").hide();
        if (pwl_vars.remember_login === "false") {
          $(".forgetmenot").hide(1);
        }
        $("loginform p").hide();
      }
      $(".pwl-title").show();
      queryDOM("#pwl_login", (dom) => {
        dom.style.cssText = `${
          dom.style.cssText.split("display: none !important")[0]
        }display: block !important`;
      });
      $("#user_login").focus();
      $(".pwl-title").html(`<span >${pwl_vars.title_auth_with_pwl}</span>`);
      $("#wp-submit").attr("disabled", true);
      let labelLoginForm = $("#loginform label");
      const usernameLabel = $("#vincss-secure-username-label");

      if (labelLoginForm.length > 0) {
        if (usernameLabel) {
          // WordPress 5.2-
          usernameLabel.text(pwl_vars.label_username);
        } else {
          labelLoginForm[0].text(pwl_vars.label_username);
        }
      }
    }
  }
}

function resetNotice() {
  jQuery("#login_error").text("").hide();
  jQuery(".notice_error").text("").hide();
}

function check() {
  resetNotice();
  if ($("#lostpasswordform, #registerform").length > 0) {
    return;
  }
  if (isSupported) {
    // $(".pwl-title").text("");
    if (!$("#user_login").val() && pwl_vars.usernameless !== "true") {
      $(`.notice_error`)
        .text(pwl_vars.error_label + pwl_vars.username_empty)
        .show();
      $(`#pwl_login`).text(pwl_vars.login);
      return;
    }
    queryDOM("#user_login", (dom) => {
      dom.readOnly = true;
    });
    queryDOM("#pwl_login, #vincss-secure", (dom) => {
      dom.disabled = true;
    });
    $(`#pwl_login`).text(pwl_vars.hold_on);

    const request = initRequest();
    request.get(
      pwl_vars.ajax_uri,
      `?action=v_pwl_start_verify&user=${encodeURIComponent(
        $("#user_login").val()
      )}&type=auth`,
      (rawData, status) => {
        if (status) {
          $(`#pwl_login`).text(pwl_vars.please_proceed);
          let data = rawData;
          try {
            data = JSON.parse(rawData);
          } catch (e) {
            if (pwl_vars.usernameless === "true" && $("#user_login").text("")) {
              $(`.notice_error`)
                .text(pwl_vars.error_label + pwl_vars.status_failed)
                .show();
              $(`#pwl_login`).text(pwl_vars.login);

              queryDOM(".v-pwl-try-username", (dom) => {
                dom.style.transform = `translateY(-${
                  parseInt(
                    document.getElementsByClassName("pwl-title")[0].style
                      .lineHeight
                  ) - 24
                }px)`;
              });
            } else {
              $(`.notice_error`)
                .text(pwl_vars.error_label + pwl_vars.status_failed)
                .show();
              $(`#pwl_login`).text(pwl_vars.login);
            }
            queryDOM("#user_login", (dom) => {
              dom.readOnly = false;
            });
            queryDOM("#pwl_login, #vincss-secure", (dom) => {
              dom.disabled = false;
            });
            return;
          }
          data.challenge = Uint8Array.from(
            window.atob(base64UrlToBase64(data.challenge)),
            (c) => c.charCodeAt(0)
          );

          if (data.allowCredentials) {
            data.allowCredentials = data.allowCredentials.map((item) => {
              item.id = Uint8Array.from(
                window.atob(base64UrlToBase64(item.id)),
                (c) => c.charCodeAt(0)
              );
              return item;
            });
          }
          // if (data.allowCredentials && data.user_login) {
          //   for (let credential of data.allowCredentials) {
          //     console.log(data.user_login);
          //     if (
          //       data.user_login[credential.id].authenticator_type ===
          //       "cross-platform"
          //     ) {
          //       credential.transports = ["usb", "nfc", "ble"];
          //     } else if (
          //       data.user_login[credential.id].authenticator_type === "platform"
          //     ) {
          //       credential.transports = ["internal"];
          //     }
          //   }
          // }
          const clientID = data.clientID;
          delete data.clientID;

          navigator.credentials
            .get({ publicKey: data })
            .then((credentialInfo) => {
              jQuery(`#pwl_login`).text(pwl_vars.status_authenticating);
              return credentialInfo;
            })
            .then((data) => {
              const publicKeyCredential = {
                id: data.id,
                type: data.type,
                rawId: array2Base64(new Uint8Array(data.rawId)),
                response: {
                  authenticatorData: array2Base64(
                    new Uint8Array(data.response.authenticatorData)
                  ),
                  clientDataJSON: array2Base64(
                    new Uint8Array(data.response.clientDataJSON)
                  ),
                  signature: array2Base64(
                    new Uint8Array(data.response.signature)
                  ),
                  userHandle: data.response.userHandle
                    ? array2Base64(new Uint8Array(data.response.userHandle))
                    : null,
                },
              };
              return publicKeyCredential;
            })
            .then(JSON.stringify)
            .then((AuthenticatorResponse) => {
              let response = initRequest();
              response.post(
                `${pwl_vars.ajax_uri}?action=v_pwl_check_authen`,
                `data=${encodeURIComponent(
                  window.btoa(AuthenticatorResponse)
                )}&type=auth&clientid=${clientID}&user=${encodeURIComponent(
                  document.getElementById("user_login").value
                )}&remember=${
                  pwl_vars.remember_login === "false"
                    ? "false"
                    : document.getElementById("rememberme")
                    ? document.getElementById("rememberme").checked
                      ? "true"
                      : "false"
                    : "false"
                }`,
                (data, status) => {
                  if (status) {
                    if (data === "true") {
                      jQuery(`#pwl_login`).text(pwl_vars.status_authenticated);
                      if (
                        document.querySelectorAll(
                          'p.submit input[name="redirect_to"]'
                        ).length > 0
                      ) {
                        setTimeout(() => {
                          window.location.href = document.querySelectorAll(
                            'p.submit input[name="redirect_to"]'
                          )[0].value;
                        }, 200);
                      } else {
                        if (getQueryString("redirect_to")) {
                          setTimeout(() => {
                            window.location.href =
                              getQueryString("redirect_to");
                          }, 200);
                        } else {
                          setTimeout(() => {
                            window.location.href = pwl_vars.admin_url;
                          }, 200);
                        }
                      }
                    } else {
                      jQuery(`.notice_error`)
                        .text(pwl_vars.error_label + data.toString())
                        .show();
                      jQuery(`#pwl_login`).text(pwl_vars.login);

                      queryDOM("#user_login", (dom) => {
                        dom.readOnly = false;
                      });
                      queryDOM("#pwl_login, #vincss-secure", (dom) => {
                        dom.disabled = false;
                      });
                    }
                  } else {
                    if (
                      pwl_vars.usernameless === "true" &&
                      $("#user_login").val() === ""
                    ) {
                      jQuery(`.notice_error`)
                        .text(
                          pwl_vars.error_label +
                            `${pwl_vars.status_failed}: ${pwl_vars.status_try_enter_name}`
                        )
                        .show();
                      jQuery(`#pwl_login`).text(pwl_vars.login);

                      queryDOM(".v-pwl-try-username", (dom) => {
                        dom.style.transform = `translateY(-${
                          parseInt(
                            document.getElementsByClassName("pwl-title")[0]
                              .style.lineHeight
                          ) - 24
                        }px)`;
                      });
                    } else {
                      jQuery(`.notice_error`)
                        .text(pwl_vars.error_label + pwl_vars.status_failed)
                        .show();
                      jQuery(`#pwl_login`).text(pwl_vars.login);
                    }
                    queryDOM("#user_login", (dom) => {
                      dom.readOnly = false;
                    });
                    queryDOM("#pwl_login, #vincss-secure", (dom) => {
                      dom.disabled = false;
                    });
                  }
                }
              );
            })
            .catch((error) => {
              console.warn(error);
              if (
                pwl_vars.usernameless === "true" &&
                document.getElementById("user_login").value === ""
              ) {
                jQuery(`.notice_error`)
                  .text(pwl_vars.error_label + pwl_vars.status_failed)
                  .show();
                jQuery(`#pwl_login`).text(pwl_vars.login);

                queryDOM(".v-pwl-try-username", (dom) => {
                  dom.style.transform = `translateY(-${
                    parseInt(
                      document.getElementsByClassName("pwl-title")[0].style
                        .lineHeight
                    ) - 24
                  }px)`;
                });
              } else {
                jQuery(`.notice_error`)
                  .text(pwl_vars.error_label + pwl_vars.status_failed)
                  .show();
                jQuery(`#pwl_login`).text(pwl_vars.login);
              }
              queryDOM("#user_login", (dom) => {
                dom.readOnly = false;
              });
              queryDOM("#pwl_login, #vincss-secure", (dom) => {
                dom.disabled = false;
              });
            });
        } else {
          if (
            pwl_vars.usernameless === "true" &&
            document.getElementById("user_login").value === ""
          ) {
            jQuery(`.notice_error`)
              .text(pwl_vars.error_label + pwl_vars.status_failed)
              .show();
            jQuery(`#pwl_login`).text(pwl_vars.login);

            queryDOM(".v-pwl-try-username", (dom) => {
              dom.style.transform = `translateY(-${
                parseInt(
                  document.getElementsByClassName("pwl-title")[0].style
                    .lineHeight
                ) - 24
              }px)`;
            });
          } else {
            jQuery(`.notice_error`)
              .text(pwl_vars.error_label + pwl_vars.status_failed)
              .show();
            jQuery(`#pwl_login`).text(pwl_vars.login);
          }
          queryDOM("#user_login", (dom) => {
            dom.readOnly = false;
          });
          queryDOM("#pwl_login, #vincss-secure", (dom) => {
            dom.disabled = false;
          });
        }
      }
    );
  }
}
