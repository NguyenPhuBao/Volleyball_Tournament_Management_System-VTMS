(function () {
    window.CoachUI = {
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

            if (params) {
                Object.entries(params).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && String(value).trim() !== "") {
                        url.searchParams.set(key, value);
                    }
                });
            }

            return url.toString();
        },

        show(el, message, isError = false) {
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

        errorsText(error) {
            const errors = error?.payload?.errors;
            if (errors && typeof errors === "object") {
                return Object.values(errors).flat().join(" ");
            }

            return error?.message || "Yêu cầu không thành công.";
        },

        splitName(fullname) {
            const parts = String(fullname || "").trim().split(/\s+/).filter(Boolean);
            if (parts.length <= 1) {
                return { hodem: "", ten: parts[0] || "" };
            }

            return {
                hodem: parts.slice(0, -1).join(" "),
                ten: parts[parts.length - 1],
            };
        },

        badge(status) {
            const map = {
                HOAT_DONG: ["ok", "Hoạt động"],
                CHO_DUYET: ["wait", "Chờ duyệt"],
                CHO_XAC_NHAN: ["wait", "Chờ xác nhận"],
                DANG_THAM_GIA: ["ok", "Đang tham gia"],
                DA_DUYET: ["ok", "Đã duyệt"],
                DA_XAC_NHAN: ["ok", "Đã xác nhận"],
                DU_DIEU_KIEN: ["ok", "Đủ điều kiện"],
                TU_CHOI: ["bad", "Từ chối"],
                DA_HUY: ["gray", "Đã hủy"],
                DA_CONG_BO: ["ok", "Đã công bố"],
                DA_DIEU_CHINH: ["proc", "Đã điều chỉnh"],
                TAM_KHOA: ["gray", "Tạm khóa"],
                GIAI_THE: ["bad", "Giải thể"],
                BAN_NHAP: ["gray", "Bản nháp"],
                DA_CHOT: ["ok", "Đã chốt"],
                DA_CAP_NHAT: ["proc", "Đã cập nhật"],
                DANG_MO: ["ok", "Đang mở"],
                DANG_DONG: ["bad", "Đã đóng"],
                CHUA_DIEN_RA: ["gray", "Chưa diễn ra"],
                SAP_DIEN_RA: ["wait", "Sắp diễn ra"],
                TRONG_TAI_TRE_GIAM_SAT: ["bad", "Trọng tài trễ giám sát"],
                DANG_DIEN_RA: ["proc", "Đang diễn ra"],
                TAM_DUNG: ["wait", "Tạm dừng"],
                DA_KET_THUC: ["ok", "Đã kết thúc"],
                DA_HUY_KHONG_CO_GIAM_SAT: ["bad", "Hủy do thiếu giám sát"],
                BI_HUY_TU_CACH: ["bad", "Bị hủy tư cách"],
            };
            return map[status] || ["gray", status || "-"];
        },

        fillSelect(select, items, valueKey, labelKey, placeholder) {
            if (!select) return;
            let html = placeholder === null ? "" : `<option value="">${this.escapeHtml(placeholder)}</option>`;
            html += (items || []).map((item) => (
                `<option value="${this.escapeHtml(item[valueKey])}">${this.escapeHtml(item[labelKey])}</option>`
            )).join("");
            select.innerHTML = html;
        },
    };
})();
