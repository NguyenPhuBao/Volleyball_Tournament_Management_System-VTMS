const root = document.querySelector(".admin-profile-users");
const usersApi = root?.dataset.usersApi || "/api/admin/users";

let users = [];
let currentUserId = null;
let searchTimer = null;

const tbody = document.getElementById("tbody");
const modal = document.getElementById("detailModal");
const pageMessage = document.getElementById("pageMessage");
const modalMessage = document.getElementById("modalMessage");
const btnSave = document.getElementById("btnSave");

const fields = {
    q: document.getElementById("q"),
    roleFilter: document.getElementById("roleFilter"),
    statusFilter: document.getElementById("statusFilter"),
    username: document.getElementById("m_username"),
    email: document.getElementById("m_email"),
    role: document.getElementById("m_role"),
    status: document.getElementById("m_status"),
    hodem: document.getElementById("m_hodem"),
    ten: document.getElementById("m_ten"),
    gioitinh: document.getElementById("m_gioitinh"),
    ngaysinh: document.getElementById("m_ngaysinh"),
    quequan: document.getElementById("m_quequan"),
};

const roleLabels = {
    ADMIN: "ADMIN",
    BAN_TO_CHUC: "BAN TỔ CHỨC",
    TRONG_TAI: "TRỌNG TÀI",
    HUAN_LUYEN_VIEN: "HLV",
    VAN_DONG_VIEN: "VĐV",
};

const statusLabels = {
    HOAT_DONG: "HOẠT ĐỘNG",
    CHUA_KICH_HOAT: "CHƯA KÍCH HOẠT",
    CHO_DUYET: "CHỜ DUYỆT",
    TAM_KHOA: "TẠM KHÓA",
    DA_HUY: "ĐÃ HỦY",
};

function userId(user) {
    return Number(user.idnguoidung || user.id);
}

function accountStatus(user) {
    return user.trangthai_taikhoan || user.status || "";
}

function fullName(user) {
    const name = user.hoten || `${user.hodem || ""} ${user.ten || ""}`;
    return name.trim();
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function badgeClass(status) {
    if (status === "HOAT_DONG") {
        return "active";
    }

    if (status === "CHO_DUYET" || status === "CHUA_KICH_HOAT") {
        return "pending";
    }

    return "lock";
}

function setPageMessage(message, success = false) {
    pageMessage.textContent = message || "";
    pageMessage.classList.toggle("success", success);
}

function setModalMessage(message) {
    modalMessage.textContent = message || "";
}

function render() {
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty">Không có người dùng phù hợp.</td></tr>';
        return;
    }

    tbody.innerHTML = users.map((user) => {
        const id = userId(user);
        const role = user.role || "";
        const status = accountStatus(user);

        return `
            <tr>
                <td>${escapeHtml(fullName(user))}</td>
                <td>${escapeHtml(user.username)}</td>
                <td>${escapeHtml(user.email)}</td>
                <td>${escapeHtml(roleLabels[role] || role)}</td>
                <td><span class="badge ${badgeClass(status)}">${escapeHtml(statusLabels[status] || status)}</span></td>
                <td><button class="btn" type="button" data-action="detail" data-id="${id}">Xem</button></td>
            </tr>
        `;
    }).join("");
}

async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        credentials: "same-origin",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            ...(options.headers || {}),
        },
        ...options,
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok || payload.success === false) {
        const details = payload.errors ? Object.values(payload.errors).join(" ") : "";
        throw new Error([payload.message, details].filter(Boolean).join(" ") || "Yêu cầu không thành công.");
    }

    return payload;
}

async function loadUsers() {
    const params = new URLSearchParams();
    const query = fields.q.value.trim();

    if (query !== "") {
        params.set("q", query);
    }

    if (fields.roleFilter.value !== "") {
        params.set("role", fields.roleFilter.value);
    }

    if (fields.statusFilter.value !== "") {
        params.set("status", fields.statusFilter.value);
    }

    tbody.innerHTML = '<tr><td colspan="6" class="empty">Đang tải dữ liệu...</td></tr>';
    setPageMessage("");

    try {
        const url = params.toString() === "" ? usersApi : `${usersApi}?${params.toString()}`;
        const payload = await apiRequest(url);
        users = payload.data || [];
        render();
    } catch (error) {
        users = [];
        render();
        setPageMessage(error.message);
    }
}

function fillModal(user) {
    fields.username.value = user.username || "";
    fields.email.value = user.email || "";
    fields.role.value = roleLabels[user.role] || user.role || "";
    fields.status.value = accountStatus(user);
    fields.hodem.value = user.hodem || "";
    fields.ten.value = user.ten || "";
    fields.gioitinh.value = user.gioitinh || "NAM";
    fields.ngaysinh.value = user.ngaysinh || "";
    fields.quequan.value = user.quequan || "";
}

function openModal() {
    modal.classList.remove("hidden");
    modal.setAttribute("aria-hidden", "false");
    fields.email.focus();
}

function closeModal() {
    modal.classList.add("hidden");
    modal.setAttribute("aria-hidden", "true");
    currentUserId = null;
    setModalMessage("");
}

async function openDetail(id) {
    setPageMessage("");
    setModalMessage("");

    try {
        const payload = await apiRequest(`${usersApi}/${id}`);
        currentUserId = id;
        fillModal(payload.data);
        openModal();
    } catch (error) {
        setPageMessage(error.message);
    }
}

function modalPayload() {
    return {
        email: fields.email.value.trim(),
        status: fields.status.value,
        hodem: fields.hodem.value.trim(),
        ten: fields.ten.value.trim(),
        gioitinh: fields.gioitinh.value,
        ngaysinh: fields.ngaysinh.value,
        quequan: fields.quequan.value.trim(),
    };
}

async function saveUser() {
    if (currentUserId === null) {
        return;
    }

    setModalMessage("");
    btnSave.disabled = true;

    try {
        await apiRequest(`${usersApi}/${currentUserId}`, {
            method: "PATCH",
            body: JSON.stringify(modalPayload()),
        });

        closeModal();
        setPageMessage("Cập nhật người dùng thành công.", true);
        await loadUsers();
    } catch (error) {
        setModalMessage(error.message);
    } finally {
        btnSave.disabled = false;
    }
}

tbody.addEventListener("click", (event) => {
    const button = event.target.closest("button[data-action='detail']");

    if (!button) {
        return;
    }

    openDetail(Number(button.dataset.id));
});

document.getElementById("btnClose").addEventListener("click", closeModal);
btnSave.addEventListener("click", saveUser);

modal.addEventListener("click", (event) => {
    if (event.target === modal) {
        closeModal();
    }
});

fields.roleFilter.addEventListener("change", loadUsers);
fields.statusFilter.addEventListener("change", loadUsers);
fields.q.addEventListener("input", () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadUsers, 250);
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modal.classList.contains("hidden")) {
        closeModal();
    }
});

loadUsers();
