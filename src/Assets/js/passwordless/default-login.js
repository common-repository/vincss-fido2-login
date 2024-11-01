document.addEventListener("DOMContentLoaded", () => {
  if (
    $("#lostpasswordform, #registerform, .admin-email-confirm-form").length > 0
  ) {
    return;
  }
  $(".pwl-title").show();

  const queryDOM = (selector, callback = () => {}) => {
    const dom_list = $(selector);
    for (let dom of dom_list) {
      callback(dom);
    }
  };
  console.log(default_pwl_login_vars.remember_login);

  window.onload = () => {
    if (default_pwl_login_vars.passwordless_only === "true") {
      if (
        !window.PublicKeyCredential ||
        !navigator.credentials.create ||
        typeof navigator.credentials.create !== "function"
      ) {
        // Browser not support, show a message
        if (document.querySelectorAll("#login > h1").length > 0) {
          const message = `<p id="login_error" >${default_pwl_login_vars.not_support}</p>`;
          $(message).insertAfter("#login > h1");
        }
      }

      if ($(".user-pass-wrap").length > 0) {
        queryDOM(".user-pass-wrap, #wp-submit", (dom) => {
          dom.parentNode.removeChild(dom);
        });
        if (default_pwl_login_vars.remember_login === "false") {
          $(".forgetmenot").hide();
        }
      } else {
        queryDOM("#wp-submit", (dom) => {
          dom.parentNode.removeChild(dom);
        });
        if (default_pwl_login_vars.remember_login === "false") {
          $(".forgetmenot").hide();
        }
        const targetDOM = document
          .getElementById("loginform")
          .getElementsByTagName("p")[1];
        targetDOM.parentNode.removeChild(targetDOM);
      }
    }
    if (
      !(
        !window.PublicKeyCredential ||
        !navigator.credentials.create ||
        typeof navigator.credentials.create !== "function"
      ) ||
      default_pwl_login_vars.passwordless_only === "true"
    ) {
      if (default_pwl_login_vars.passwordless_only !== "true") {
        if ($(".user-pass-wrap").length > 0) {
          $(".user-pass-wrap, #wp-submit").hide();
          if (default_pwl_login_vars.remember_login === "false") {
            $(".forgetmenot").hide();
          }
        } else {
          $("#wp-submit").hide();
          if (default_pwl_login_vars.remember_login === "false") {
            $(".forgetmenot").hide();
          }
          $("#loginform p").get(1).hide();
        }
      }
      $("#pwl-title").show();
      queryDOM("#pwl_login", (dom) => {
        dom.style.cssText = `${dom.style.cssText}display: block !important`;
      });
      $("#user_login").focus();
      $("#wp-submit").attr("disabled", true);
    }
    if ($("#lostpasswordform, #registerform").length > 0) {
      return;
    }
    $("#user_pass").attr("disabled", true);

    let dom = $("#loginform label");
    if (dom.length > 0) {
      if (dom[0].getElementsByTagName("input").length > 0) {
        dom[0].innerHTML = `<span id="vincss-secure-username-label">${
          default_pwl_login_vars.label_username
        }</span>${dom[0].innerHTML.split("<br>")[1]}`;
      } else {
        dom[0].innerText = default_pwl_login_vars.label_username;
      }
    }
  };
});
