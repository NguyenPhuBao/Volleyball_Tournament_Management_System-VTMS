(function () {
    window.AthleteUI = {
        escapeHtml(value) {
            return String(value ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        },

        async requestJson(url, options = {}) {
            const response = await fetch(url, {
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    ...(options.headers || {}),
                },
                credentials: "same-origin",
                ...options,
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || payload.success === false) {
                const error = new Error(payload.message || "Yêu cầu không thành công.");
                error.status = response.status;
                error.payload = payload;
                throw error;
            }

            return payload;
        },

        apiUrl(base, params = null) {
            const url = new URL(base, window.location.origin);
            Object.entries(params || {}).forEach(([key, value]) => {
                if (value !== null && value !== undefined && String(value).trim() !== "") {
                    url.searchParams.set(key, value);
                }
            });
            return url.toString();
        },

        errorsText(error) {
            const errors = error?.payload?.errors;
            if (errors && typeof errors === "object") {
                return Object.values(errors).flat().join(" ");
            }
            return error?.message || "Yêu cầu không thành công.";
        },

        showMessage(el, message, isError = false) {
            if (!el) return;
            el.textContent = message || "";
            el.classList.toggle("error", Boolean(isError));
        },

        showAlert(el, message) {
            if (!el) return;
            el.textContent = message || "";
            el.classList.remove("hidden");
        },

        hideAlert(el) {
            if (!el) return;
            el.textContent = "";
            el.classList.add("hidden");
        },

        badge(status) {
            const map = {
                CHO_PHAN_HOI: ["wait", "Chờ phản hồi"],
                DONG_Y: ["ok", "Đồng ý"],
                TU_CHOI: ["bad", "Từ chối"],
                HET_HAN: ["gray", "Hết hạn"],
                DANG_THAM_GIA: ["ok", "Đang tham gia"],
                DA_ROI_DOI: ["gray", "Đã rời đội"],
                CHO_DUYET: ["wait", "Chờ duyệt"],
                DA_DUYET: ["ok", "Đã duyệt"],
                DA_HUY: ["gray", "Đã hủy"],
                CHUA_DIEN_RA: ["gray", "Chưa diễn ra"],
                SAP_DIEN_RA: ["wait", "Sắp diễn ra"],
                TRONG_TAI_TRE_GIAM_SAT: ["bad", "Trọng tài trễ giám sát"],
                DANG_DIEN_RA: ["proc", "Đang diễn ra"],
                TAM_DUNG: ["wait", "Tạm dừng"],
                DA_KET_THUC: ["ok", "Đã kết thúc"],
                DA_HUY_KHONG_CO_GIAM_SAT: ["bad", "Hủy do thiếu giám sát"],
                DA_CONG_BO: ["ok", "Đã công bố"],
                CHO_CONG_BO: ["wait", "Chờ công bố"],
            };
            return map[status] || ["gray", status || "-"];
        },

        badgeHtml(status) {
            const [className, label] = this.badge(status);
            return `<span class="badge ${className}">${this.escapeHtml(label)}</span>`;
        },

        formatDateTime(value) {
            return String(value || "").replace("T", " ");
        },
    };
})();
