(function () {
    const root = document.querySelector(".organizer-venues");

    if (!root) {
        return;
    }

    const venuesApi = root.dataset.venuesApi || "/api/organizer/venues";
    const locationsApi = root.dataset.locationsApi || "/api/organizer/competition-locations";
    const tbody = document.getElementById("tbody");
    const q = document.getElementById("q");
    const statusFilter = document.getElementById("statusFilter");
    const btnRefresh = document.getElementById("btnRefresh");
    const btnAdd = document.getElementById("btnAdd");
    const pageMessage = document.getElementById("pageMessage");

    const venueModal = document.getElementById("venueModal");
    const modalTitle = document.getElementById("modalTitle");
    const mClose = document.getElementById("m_close");
    const mCancel = document.getElementById("m_cancel");
    const mSave = document.getElementById("m_save");
    const mRemove = document.getElementById("m_remove");
    const mAlert = document.getElementById("m_alert");

    const mName = document.getElementById("m_name");
    const mLocation = document.getElementById("m_location");
    const mCapacity = document.getElementById("m_capacity");
    const mStatus = document.getElementById("m_status");
    const mNote = document.getElementById("m_note");

    let venues = [];
    let locations = [];
    let editingId = null;

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function showPageMessage(message, isError = false) {
        pageMessage.textContent = message || "";
        pageMessage.style.color = isError ? "#991b1b" : "#64748b";
    }

    function showModalError(message) {
        mAlert.textContent = message;
        mAlert.classList.remove("hidden");
    }

    function hideModalError() {
        mAlert.textContent = "";
        mAlert.classList.add("hidden");
    }

    function badge(status) {
        const map = {
            HOAT_DONG: ["ok", "Hoạt động"],
            DANG_BAO_TRI: ["maint", "Đang bảo trì"],
            NGUNG_SU_DUNG: ["off", "Ngưng sử dụng"],
        };

        return map[status] || ["off", status || "-"];
    }

    async function requestJson(url, options = {}) {
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
    }

    function apiUrl(path = "", params = null) {
        const url = new URL(venuesApi + path, window.location.origin);

        if (params) {
            Object.entries(params).forEach(([key, value]) => {
                if (value !== null && value !== undefined && String(value).trim() !== "") {
                    url.searchParams.set(key, value);
                }
            });
        }

        return url.toString();
    }

    function renderRows() {
        if (venues.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="empty">Không có sân đấu phù hợp.</td></tr>';
            return;
        }

        tbody.innerHTML = venues.map((venue) => {
            const [className, label] = badge(venue.trangthai);
            return `
                <tr>
                    <td>${escapeHtml(venue.idsandau)}</td>
                    <td>${escapeHtml(venue.tensandau)}</td>
                    <td>${escapeHtml(venue.tenvitrithidau || "")}</td>
                    <td>${escapeHtml(venue.diachi)}</td>
                    <td>${escapeHtml(venue.succhua)}</td>
                    <td><span class="badge ${className}">${escapeHtml(label)}</span></td>
                    <td>${escapeHtml(venue.mota || "")}</td>
                    <td>
                        <button class="btn" type="button" data-action="edit" data-id="${escapeHtml(venue.idsandau)}">Cập nhật</button>
                    </td>
                </tr>
            `;
        }).join("");
    }

    async function loadVenues() {
        showPageMessage("Đang tải dữ liệu...");

        try {
            const payload = await requestJson(apiUrl("", {
                q: q.value.trim(),
                status: statusFilter.value,
            }));
            venues = Array.isArray(payload.data) ? payload.data : [];
            renderRows();
            showPageMessage("");
        } catch (error) {
            venues = [];
            tbody.innerHTML = '<tr><td colspan="8" class="empty">Không thể tải dữ liệu sân đấu.</td></tr>';
            showPageMessage(error.message || "Không thể tải dữ liệu sân đấu.", true);
        }
    }

    async function loadLocations() {
        try {
            const payload = await requestJson(new URL(locationsApi, window.location.origin).toString());
            locations = Array.isArray(payload.data) ? payload.data : [];
        } catch (error) {
            locations = [];
        }

        const currentValue = mLocation.value;
        mLocation.innerHTML = '<option value="">Chọn vị trí thi đấu</option>' + locations.map((location) => `
            <option value="${escapeHtml(location.idvitrithidau)}">
                ${escapeHtml(location.tenvitrithidau)}${location.diachi ? ` - ${escapeHtml(location.diachi)}` : ""}
            </option>
        `).join("");
        mLocation.value = currentValue;
    }

    function currentVenue() {
        return venues.find((venue) => Number(venue.idsandau) === Number(editingId)) || null;
    }

    function openCreate() {
        editingId = null;
        hideModalError();
        modalTitle.textContent = "Bổ sung sân đấu";
        mName.value = "";
        mLocation.value = "";
        mCapacity.value = "0";
        mStatus.value = "HOAT_DONG";
        mNote.value = "";
        mRemove.disabled = true;
        venueModal.classList.remove("hidden");
    }

    function openEdit(id) {
        const venue = venues.find((item) => Number(item.idsandau) === Number(id));

        if (!venue) {
            return;
        }

        editingId = Number(id);
        hideModalError();
        modalTitle.textContent = "Cập nhật sân đấu";
        mName.value = venue.tensandau || "";
        mLocation.value = String(venue.idvitrithidau || "");
        mCapacity.value = venue.succhua ?? 0;
        mStatus.value = venue.trangthai || "HOAT_DONG";
        mNote.value = venue.mota || "";
        mRemove.disabled = venue.trangthai === "NGUNG_SU_DUNG";
        venueModal.classList.remove("hidden");
    }

    function closeModal() {
        venueModal.classList.add("hidden");
        editingId = null;
    }

    function formPayload() {
        return {
            tensandau: mName.value.trim(),
            idvitrithidau: Number(mLocation.value || 0),
            succhua: Number(mCapacity.value || 0),
            trangthai: mStatus.value,
            mota: mNote.value.trim() || null,
        };
    }

    function validate(payload) {
        if (!payload.tensandau || !payload.idvitrithidau) {
            return "Vui lòng nhập đầy đủ: Tên sân, Vị trí thi đấu.";
        }

        if (!Number.isInteger(payload.succhua) || payload.succhua < 0) {
            return "Sức chứa phải là số nguyên không âm.";
        }

        return null;
    }

    function changedPayload(payload) {
        const venue = currentVenue();

        if (!venue) {
            return payload;
        }

        const changes = {};

        if (payload.tensandau !== String(venue.tensandau || "")) {
            changes.tensandau = payload.tensandau;
        }

        if (payload.idvitrithidau !== Number(venue.idvitrithidau || 0)) {
            changes.idvitrithidau = payload.idvitrithidau;
        }

        if (payload.succhua !== Number(venue.succhua || 0)) {
            changes.succhua = payload.succhua;
        }

        if (payload.trangthai !== String(venue.trangthai || "")) {
            changes.trangthai = payload.trangthai;
        }

        if ((payload.mota || null) !== (venue.mota || null)) {
            changes.mota = payload.mota;
        }

        return changes;
    }

    async function saveVenue() {
        hideModalError();
        const payload = formPayload();
        const validationError = validate(payload);

        if (validationError) {
            showModalError(validationError);
            return;
        }

        mSave.disabled = true;

        try {
            if (editingId) {
                const changes = changedPayload(payload);

                if (Object.keys(changes).length === 0) {
                    showModalError("Không có dữ liệu thay đổi.");
                    return;
                }

                await requestJson(apiUrl(`/${editingId}`), {
                    method: "PATCH",
                    body: JSON.stringify(changes),
                });
                closeModal();
                await loadVenues();
                showPageMessage("Cập nhật sân đấu thành công.");
                return;
            } else {
                await requestJson(apiUrl(), {
                    method: "POST",
                    body: JSON.stringify(payload),
                });
                closeModal();
                await loadVenues();
                showPageMessage("Bổ sung sân đấu thành công.");
                return;
            }
        } catch (error) {
            showModalError(error.message || "Không thể lưu sân đấu.");
        } finally {
            mSave.disabled = false;
        }
    }

    async function removeVenue() {
        hideModalError();

        if (!editingId) {
            return;
        }

        if (!window.confirm("Loại bỏ sân đấu? Hệ thống sẽ chuyển trạng thái sang NGƯNG SỬ DỤNG.")) {
            return;
        }

        mRemove.disabled = true;

        try {
            await requestJson(apiUrl(`/${editingId}/deactivate`), {
                method: "POST",
                body: JSON.stringify({ lydo: "Loai bo san dau tu giao dien quan ly san dau" }),
            });
            closeModal();
            await loadVenues();
            showPageMessage("Ngưng sử dụng sân đấu thành công.");
        } catch (error) {
            showModalError(error.message || "Không thể loại bỏ sân đấu.");
            mRemove.disabled = false;
        }
    }

    btnAdd.addEventListener("click", openCreate);
    btnRefresh.addEventListener("click", loadVenues);
    q.addEventListener("input", loadVenues);
    statusFilter.addEventListener("change", loadVenues);
    mClose.addEventListener("click", closeModal);
    mCancel.addEventListener("click", closeModal);
    mSave.addEventListener("click", saveVenue);
    mRemove.addEventListener("click", removeVenue);

    tbody.addEventListener("click", (event) => {
        const button = event.target.closest("[data-action='edit']");

        if (!button) {
            return;
        }

        openEdit(button.dataset.id);
    });

    Promise.all([loadLocations(), loadVenues()]);
})();
