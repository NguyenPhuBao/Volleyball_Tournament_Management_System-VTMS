(function () {
    const root = document.querySelector(".referee-register");
    if (!root) return;

    const ui = window.CoachUI;
    const form = document.getElementById("refereeRegisterForm");
    const alertBox = document.getElementById("formAlert");
    const message = document.getElementById("formMessage");
    const api = root.dataset.registerApi || "/api/referee/register";
    const optionsApi = root.dataset.optionsApi || "/api/referee/register/options";
    const level = document.getElementById("level");

    function levelLabel(item) {
        return item.tencapgiaidau || item.macapgiaidau || item.capkhuvucphamvi;
    }

    async function loadOptions() {
        try {
            const result = await ui.requestJson(optionsApi);
            const levels = result.data?.levels || [];
            level.innerHTML = '<option value="">-- Chọn cấp bậc trọng tài --</option>' +
                levels.map((item) => `<option value="${ui.escapeHtml(item.macapgiaidau)}">${ui.escapeHtml(levelLabel(item))}</option>`).join("");
        } catch (error) {
            level.innerHTML = '<option value="">Không tải được cấp bậc</option>';
            ui.showAlert(alertBox, ui.errorsText(error));
        }
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        ui.hideAlert(alertBox);
        ui.show(message, "");

        const fullName = document.getElementById("fullname").value.trim();
        const name = ui.splitName(fullName);
        const payload = {
            username: document.getElementById("username").value.trim(),
            password: document.getElementById("password").value,
            password_confirmation: document.getElementById("passwordConfirmation").value,
            email: document.getElementById("email").value.trim(),
            phone: document.getElementById("phone").value.trim(),
            hodem: name.hodem,
            ten: name.ten,
            ngaysinh: document.getElementById("dob").value,
            gioitinh: document.getElementById("gender").value,
            capbac: level.value,
            kinhnghiem: document.getElementById("experience").value,
            cccd: document.getElementById("identityNumber").value.trim() || null,
            noidung: document.getElementById("note").value.trim() || "Yêu cầu đăng ký tài khoản trọng tài",
        };

        for (const key of ["username", "password", "email", "phone", "ten", "ngaysinh", "gioitinh", "capbac", "kinhnghiem"]) {
            if (payload[key] === "") {
                ui.showAlert(alertBox, "Vui lòng nhập đầy đủ thông tin bắt buộc.");
                return;
            }
        }

        if (!payload.hodem) {
            ui.showAlert(alertBox, "Vui lòng nhập đầy đủ họ và tên.");
            return;
        }

        try {
            const result = await ui.requestJson(api, {
                method: "POST",
                body: JSON.stringify(payload),
            });
            ui.show(message, result.message || "Đăng ký thành công. Vui lòng chờ BTC Liên đoàn xác nhận.");
            form.reset();
        } catch (error) {
            ui.showAlert(alertBox, ui.errorsText(error));
        }
    });

    loadOptions();
})();
