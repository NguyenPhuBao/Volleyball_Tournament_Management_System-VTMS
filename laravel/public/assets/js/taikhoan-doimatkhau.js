(function () {
    const page = document.querySelector(".account-password-page");

    if (!page) {
        return;
    }

    const form = document.getElementById("passwordForm");
    const currentPassword = document.getElementById("currentPassword");
    const newPassword = document.getElementById("newPassword");
    const confirmPassword = document.getElementById("confirmPassword");
    const alertBox = document.getElementById("passwordAlert");
    const submitButton = document.getElementById("btnPasswordSubmit");
    const endpoint = page.dataset.passwordApi || "/api/account/password";

    function showAlert(message, type) {
        alertBox.textContent = message;
        alertBox.classList.remove("hidden", "success", "error");
        alertBox.classList.add(type);
    }

    function hideAlert() {
        alertBox.textContent = "";
        alertBox.classList.add("hidden");
        alertBox.classList.remove("success", "error");
    }

    function validate() {
        if (!currentPassword.value.trim()) {
            return "Vui lòng nhập mật khẩu hiện tại.";
        }

        if (newPassword.value.length < 6 || newPassword.value.length > 72) {
            return "Mật khẩu mới phải từ 6 đến 72 ký tự.";
        }

        if (newPassword.value !== confirmPassword.value) {
            return "Mật khẩu xác nhận không khớp.";
        }

        if (currentPassword.value === newPassword.value) {
            return "Mật khẩu mới phải khác mật khẩu hiện tại.";
        }

        return null;
    }

    function firstError(payload) {
        const errors = payload && payload.errors ? Object.values(payload.errors) : [];
        return errors.length > 0 ? String(errors[0]) : "";
    }

    form?.addEventListener("submit", async (event) => {
        event.preventDefault();
        hideAlert();

        const error = validate();
        if (error) {
            showAlert(error, "error");
            return;
        }

        submitButton.disabled = true;

        try {
            const response = await fetch(endpoint, {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    current_password: currentPassword.value,
                    new_password: newPassword.value,
                    new_password_confirmation: confirmPassword.value
                })
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || payload.success === false) {
                showAlert(firstError(payload) || payload.message || "Không thể đổi mật khẩu.", "error");
                return;
            }

            form.reset();
            showAlert(payload.message || "Đổi mật khẩu thành công.", "success");
        } catch (error) {
            showAlert("Không thể kết nối máy chủ. Vui lòng thử lại.", "error");
        } finally {
            submitButton.disabled = false;
        }
    });
})();
