(function () {
    const page = document.querySelector(".athlete-profile");
    if (!page) return;

    const UI = window.AthleteUI;
    const api = page.dataset.requestsApi;
    const message = document.getElementById("pageMessage");
    const modal = document.getElementById("editIdModal");
    const modalAlert = document.getElementById("modalAlert");
    const btnEditId = document.getElementById("btnEditId");
    const btnClose = document.getElementById("m_close");
    const btnCancel = document.getElementById("m_cancel");
    const btnSubmit = document.getElementById("m_submit");

    function openModal() {
        UI.hideAlert(modalAlert);
        document.getElementById("newId").value = "";
        document.getElementById("reason").value = "";
        modal.classList.remove("hidden");
    }

    function closeModal() {
        modal.classList.add("hidden");
    }

    async function loadProfile() {
        try {
            const payload = await UI.requestJson(api);
            const athlete = payload.meta?.athlete || {};
            const fullName = [athlete.hodem, athlete.ten].filter(Boolean).join(" ") || athlete.hoten || athlete.username || "—";

            document.getElementById("fullName").textContent = fullName;
            document.getElementById("currentId").textContent = athlete.mavandongvien || "—";
            document.getElementById("currentCccd").textContent = athlete.cccd || "—";
            document.getElementById("email").textContent = athlete.email || "—";
            document.getElementById("phone").textContent = athlete.sodienthoai || "—";
        } catch (error) {
            UI.showMessage(message, UI.errorsText(error), true);
        }
    }

    async function submit() {
        UI.hideAlert(modalAlert);
        UI.showMessage(message, "");

        const value = document.getElementById("newId").value.trim();
        const reason = document.getElementById("reason").value.trim();

        if (!value || !reason) {
            UI.showAlert(modalAlert, "Vui lòng nhập đầy đủ ID mới và lý do.");
            return;
        }

        try {
            await UI.requestJson(api, {
                method: "POST",
                body: JSON.stringify({ field: "mavandongvien", value, reason }),
            });
            closeModal();
            UI.showMessage(message, "Đã gửi yêu cầu sửa ID cá nhân. Vui lòng chờ HLV duyệt.");
        } catch (error) {
            UI.showAlert(modalAlert, UI.errorsText(error));
        }
    }

    btnEditId.addEventListener("click", openModal);
    btnClose.addEventListener("click", closeModal);
    btnCancel.addEventListener("click", closeModal);
    btnSubmit.addEventListener("click", submit);
    loadProfile();
})();
