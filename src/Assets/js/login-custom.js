document.addEventListener("DOMContentLoaded", () => {
  const thirdparty_login = document.createElement("div");
  thirdparty_login.className = "thirdparty_login";
  thirdparty_login.innerHTML = `
      <div style="clear:both"> </div>
      <div class="other-login">
          <div class="login_separator">${login_custom_vars.label_or}</div>
      </div>
   `;
  const submit_area = document.querySelector("p.submit");
  submit_area.insertBefore(thirdparty_login, submit_area.lastChild);
});
