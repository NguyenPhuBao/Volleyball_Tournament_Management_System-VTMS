(function () {
    const root = document.querySelector(".coach-register");
    if (!root) return;

    const ui = window.CoachUI;
    const form = document.getElementById("registerForm");
    const alertBox = document.getElementById("formAlert");
    const message = document.getElementById("formMessage");
    const api = root.dataset.registerApi || "/api/coach/register";
    const optionsApi = root.dataset.optionsApi || "/api/coach/register/options";
    const workRegion = document.getElementById("workRegion");

    function regionLabel(region) {
        return `${region.tenkhuvuc} (${region.capkhuvuc})`;
    }

    async function loadOptions() {
        try {
            const result = await ui.requestJson(optionsApi);
            const regions = result.data?.work_regions || [];
            workRegion.innerHTML = '<option value="">-- Chọn khu vực công tác --</option>' +
                regions.map((region) => `<option value="${region.idkhuvuc}">${ui.escapeHtml(regionLabel(region))}</option>`).join("");
        } catch (error) {
            workRegion.innerHTML = '<option value="">Không tải được khu vực</option>';
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
            idkhuvuccongtac: workRegion.value,
            donvicongtac: document.getElementById("workUnit").value.trim(),
            bangcap: document.getElementById("degree").value.trim(),
            kinhnghiem: document.getElementById("experience").value,
            cccd: document.getElementById("identityNumber").value.trim() || null,
            noidung: document.getElementById("note").value.trim() || "Yêu cầu đăng ký tài khoản huấn luyện viên",
        };

        for (const key of ["username", "password", "email", "phone", "ten", "ngaysinh", "gioitinh", "idkhuvuccongtac", "donvicongtac", "bangcap", "kinhnghiem"]) {
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
            ui.show(message, result.message || "Đăng ký thành công. Vui lòng chờ Ban tổ chức xác nhận.");
            form.reset();
        } catch (error) {
            ui.showAlert(alertBox, ui.errorsText(error));
        }
    });

    loadOptions();
})();
