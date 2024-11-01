const SessionStateKey = "vincss_fido2_login_login_state_oauth2";

function randomStr(length) {
  return Array.from({ length }, () => Math.random().toString(36)[2]).join("");
}

function showError(error) {
  let notice = document.createElement("div");
  notice.id = "login_error";
  notice.innerHTML = `Error: ${error}`;
  document
    .querySelector("#login")
    .insertBefore(notice, document.querySelector("form[name=loginform]"));
}

function getRedirectUrl() {
  const { client_id, redirect_uri, authorize_url } = variables_oauth2;

  const state = `security_token=${randomStr(13)}`;

  const params = new URLSearchParams({
    client_id: client_id,
    response_type: "code",
    redirect_uri,
    state,
  });
  sessionStorage.setItem(SessionStateKey, state);
  return `${authorize_url}?${params.toString()}`;
}

jQuery(document).ready(function () {
  const {
    ajaxURL,
    _wpnonce,
    is_enable_oauth2,
    title_oauth2,
    title_login_other,
  } = variables_oauth2;

  if (is_enable_oauth2) {
    const thirdparty_login = document.querySelector(".thirdparty_login");

    let btn_oauth2 = document.createElement("div");
    btn_oauth2.className = "v_oauth2_wrapper";
    btn_oauth2.innerHTML = `<button type="button" id="login_with_oauth2" class="button button-large button-primary mt-2">${title_oauth2}</button>`;

    thirdparty_login.insertBefore(btn_oauth2, thirdparty_login.lastChild);
    thirdparty_login.style.display = "block";
    jQuery("#login_with_oauth2").click(() => {
      const url = getRedirectUrl();
      window.location.replace(url);
    });
    const searchParam = new URLSearchParams(window.location.search);
    const oauth2_code = searchParam.get("code");
    const state = searchParam.get("state");
    if (oauth2_code) {
      // check state
      const session_state = sessionStorage.getItem(SessionStateKey);
      if (state !== session_state) {
        showError("Invalid state parameter");
        sessionStorage.removeItem(SessionStateKey);
        return;
      }
      sessionStorage.removeItem(SessionStateKey);
      jQuery.ajax({
        type: "POST",
        dataType: "json",
        url: ajaxURL,
        data: {
          action: "vincss_verify_user_oauth2",
          code: oauth2_code,
          _wpnonce,
        },
        success: function (responseJSON) {
          const { redirect_to } = responseJSON.data;
          location.replace(redirect_to);
        },
        error: function ({ responseJSON }) {
          showError(responseJSON?.data?.error || "Login error!");
        },
      });
    }
  }
});
