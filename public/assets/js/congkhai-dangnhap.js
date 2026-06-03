const yearEl = document.getElementById("year");
if (yearEl) {
  yearEl.textContent = new Date().getFullYear();
}

const form = document.getElementById("loginForm");
const identifier = document.getElementById("identifier");
const password = document.getElementById("password");
const remember = document.getElementById("remember");
const alertBox = document.getElementById("alert");
const submitBtn = document.getElementById("submitBtn");
const togglePw = document.getElementById("togglePw");
const demoBtn = document.getElementById("demoBtn");

function showAlert(message) {
  alertBox.hidden = false;
  alertBox.textContent = message;
}

function hideAlert() {
  alertBox.hidden = true;
  alertBox.textContent = "";
}

function setLoading(isLoading) {
  submitBtn.classList.toggle("is-loading", isLoading);
  submitBtn.disabled = isLoading;
}

function setInvalid(el, isInvalid) {
  el.setAttribute("aria-invalid", isInvalid ? "true" : "false");
}

togglePw.addEventListener("click", () => {
  const isPw = password.type === "password";
  password.type = isPw ? "text" : "password";
  togglePw.textContent = isPw ? "🙈" : "👁";
});

demoBtn.addEventListener("click", () => {
  identifier.value = "admin01";
  password.value = "123456";
  remember.checked = true;
  hideAlert();
});

function roleToHome(role) {
  const map = {
    ADMIN: "/admin",
    BAN_TO_CHUC: "/ban-to-chuc",
    TRONG_TAI: "/trong-tai",
    HUAN_LUYEN_VIEN: "/huan-luyen-vien",
    VAN_DONG_VIEN: "/van-dong-vien"
  };

  return map[role] || "/dashboard";
}

async function loginRequest(payload) {
  const response = await fetch("/api/auth/login", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  });

  const result = await response.json().catch(() => ({}));

  if (!response.ok || !result.success) {
    throw new Error(result.message || "Đăng nhập thất bại. Vui lòng thử lại.");
  }

  return result.user;
}

form.addEventListener("submit", async (e) => {
  e.preventDefault();
  hideAlert();

  const idVal = identifier.value.trim();
  const pwVal = password.value;

  let ok = true;
  setInvalid(identifier, false);
  setInvalid(password, false);

  if (!idVal) {
    setInvalid(identifier, true);
    ok = false;
  }

  if (!pwVal || pwVal.length < 6) {
    setInvalid(password, true);
    ok = false;
  }

  if (!ok) {
    showAlert("Vui lòng nhập đầy đủ thông tin (mật khẩu tối thiểu 6 ký tự).");
    return;
  }

  setLoading(true);

  try {
    const user = await loginRequest({
      identifier: idVal,
      password: pwVal,
      remember: remember.checked
    });

    window.location.href = roleToHome(user.role);
  } catch (err) {
    showAlert(err.message || "Đăng nhập thất bại. Vui lòng thử lại.");
    setLoading(false);
  }
});
