(function () {
    const root = document.querySelector(".coach-tournaments");
    if (!root) return;

    const ui = window.CoachUI;
    const tournamentsApi = root.dataset.tournamentsApi || "/api/coach/tournaments";
    const registrationsApi = root.dataset.registrationsApi || "/api/coach/tournament-registrations";
    const teamsApi = root.dataset.teamsApi || "/api/coach/teams";

    const tbody = document.getElementById("tbody");
    const q = document.getElementById("q");
    const modal = document.getElementById("detailModal");
    const alertBox = document.getElementById("d_alert");
    const pageMessage = document.getElementById("pageMessage");
    const teamSelect = document.getElementById("d_team");
    const lineupSelect = document.getElementById("d_lineup");
    const teamRegistration = document.getElementById("d_teamRegistration");
    const registerButton = document.getElementById("btnRegister");

    let tournaments = [];
    let teams = [];
    let current = null;
    let currentRegistrations = [];
    let currentLineups = [];

    const registrationStatusText = {
        CHO_DUYET: "Chờ duyệt",
        DA_DUYET: "Đã duyệt",
        TU_CHOI: "Từ chối",
        DA_HUY: "Đã hủy",
    };

    const tournamentRegistrationStatusText = {
        CHO_DUYET: "đang chờ duyệt",
        DA_DUYET: "đã đăng ký",
        DA_KET_THUC: "đã kết thúc",
        DA_HUY: "rời giải đấu",
        TU_CHOI: "từ chối",
    };

    const tournamentRegistrationBadgeClass = {
        CHO_DUYET: "wait",
        DA_DUYET: "ok",
        DA_KET_THUC: "gray",
        DA_HUY: "gray",
        TU_CHOI: "bad",
    };

    const tournamentGenderText = {
        NAM: "Nam",
        NU: "Nữ",
    };

    function dateRange(from, to) {
        return `${from || "-"} - ${to || "-"}`;
    }

    function statusLabel(tournament) {
        if (tournament.trangthaidangky === "DANG_MO") return "Đang mở đăng ký";
        if (tournament.trangthaidangky === "DA_DONG") return "Đã đóng đăng ký";
        return tournament.trangthaidangky || tournament.trangthai || "-";
    }

    function coachRegistrationBadge(status) {
        const label = tournamentRegistrationStatusText[status];
        if (!label) return "";

        const badgeClass = tournamentRegistrationBadgeClass[status] || "gray";
        return `<span class="badge ${badgeClass}">${ui.escapeHtml(label)}</span>`;
    }

    function render() {
        if (tournaments.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty">Không có giải đang mở đăng ký hoặc hồ sơ giải đấu đã gửi.</td></tr>';
            return;
        }

        tbody.innerHTML = tournaments.map((tournament) => {
            const [badgeClass, label] = ui.badge(tournament.trangthaidangky);
            const registrationBadge = coachRegistrationBadge(tournament.coach_registration_status);
            return `
                <tr>
                    <td>${ui.escapeHtml(tournament.tengiaidau)}</td>
                    <td>${ui.escapeHtml(dateRange(tournament.ngaytao, tournament.thoigianbatdau))}</td>
                    <td>${ui.escapeHtml(dateRange(tournament.thoigianbatdau, tournament.thoigianketthuc))}</td>
                    <td>${ui.escapeHtml(tournament.approved_registrations || 0)} / ${ui.escapeHtml(tournament.quymo || 0)}</td>
                    <td><span class="badge ${badgeClass}">${ui.escapeHtml(label)}</span> ${registrationBadge}</td>
                    <td><button class="btn" type="button" data-action="view" data-id="${ui.escapeHtml(tournament.idgiaidau)}">Xem</button></td>
                </tr>
            `;
        }).join("");
    }

    async function load() {
        ui.show(pageMessage, "");
        const params = { q: q.value.trim() };
        const [tournamentPayload, teamPayload] = await Promise.all([
            ui.requestJson(ui.apiUrl(tournamentsApi, params)),
            ui.requestJson(teamsApi),
        ]);
        tournaments = tournamentPayload.data || [];
        teams = teamPayload.data || [];
        fillTeamSelect();
        render();
    }

    function registrationStatusLabel(status) {
        return registrationStatusText[status] || status || "-";
    }

    function genderLabel(gender) {
        return tournamentGenderText[gender] || gender || "-";
    }

    function tournamentGender() {
        return String(current?.gioitinh || "NAM").toUpperCase();
    }

    function registrationForTeam(teamId) {
        return currentRegistrations.find((registration) => Number(registration.iddoibong) === Number(teamId)) || null;
    }

    function registrationForSelectedTeam() {
        const teamId = Number(teamSelect.value || 0);
        if (!teamId) return null;

        return registrationForTeam(teamId);
    }

    function eligibleTeamIdsForCurrentTournament() {
        if (!current || !Array.isArray(current.eligible_team_ids)) return null;

        return current.eligible_team_ids.map((id) => Number(id));
    }

    function isTeamEligibleForCurrentTournament(teamId) {
        const eligibleIds = eligibleTeamIdsForCurrentTournament();
        if (eligibleIds === null) return true;

        return eligibleIds.includes(Number(teamId));
    }

    function eligibleTeamsForCurrentTournament() {
        return teams.filter((team) => isTeamEligibleForCurrentTournament(team.iddoibong));
    }

    function fillTeamSelect(selectedTeamId = "") {
        if (!teamSelect) return;

        const selectedValue = selectedTeamId || teamSelect.value || "";
        const options = ['<option value="">Chọn đội bóng</option>'];
        const visibleTeams = eligibleTeamsForCurrentTournament();

        visibleTeams.forEach((team) => {
            const registration = registrationForTeam(team.iddoibong);
            const status = registration
                ? `Đã đăng ký - ${registrationStatusLabel(registration.trangthai)}`
                : "Chưa đăng ký";
            options.push(
                `<option value="${ui.escapeHtml(team.iddoibong)}">${ui.escapeHtml(team.tendoibong)} (${ui.escapeHtml(status)})</option>`
            );
        });

        if (visibleTeams.length === 0) {
            options.push('<option value="" disabled>Không có đội đủ điều kiện đăng ký</option>');
        }

        teamSelect.innerHTML = options.join("");
        teamSelect.value = isTeamEligibleForCurrentTournament(selectedValue) ? selectedValue : "";
    }

    function fillLineupSelect(selectedLineupId = "") {
        if (!lineupSelect) return;

        const options = ['<option value="">Chọn đội hình</option>'];

        currentLineups.forEach((lineup) => {
            const main = Number(lineup.la_doihinh_chinh || 0) === 1 ? " - Đội hình chính" : "";
            options.push(
                `<option value="${ui.escapeHtml(lineup.iddoihinh)}">${ui.escapeHtml(lineup.tendoihinh)} (${ui.escapeHtml(genderLabel(lineup.gioitinh))}${main})</option>`
            );
        });

        if (currentLineups.length === 0) {
            options.push('<option value="" disabled>Không có đội hình phù hợp</option>');
        }

        lineupSelect.innerHTML = options.join("");
        const preferred = selectedLineupId
            || currentLineups.find((lineup) => Number(lineup.la_doihinh_chinh || 0) === 1)?.iddoihinh
            || "";
        lineupSelect.value = currentLineups.some((lineup) => Number(lineup.iddoihinh) === Number(preferred))
            ? String(preferred)
            : "";
        lineupSelect.disabled = currentLineups.length === 0;
    }

    async function loadLineupsForTeam(teamId, selectedLineupId = "") {
        currentLineups = [];

        if (!lineupSelect) return;

        if (!teamId) {
            lineupSelect.innerHTML = '<option value="">Chọn đội trước</option>';
            lineupSelect.disabled = true;
            return;
        }

        lineupSelect.innerHTML = '<option value="">Đang tải đội hình...</option>';
        lineupSelect.disabled = true;

        const payload = await ui.requestJson(`${teamsApi}/${teamId}/lineups`);
        const lineups = payload.data || payload.lineups || [];
        const gender = tournamentGender();

        currentLineups = lineups.filter((lineup) => {
            const lineupGender = String(lineup.gioitinh || "NAM").toUpperCase();
            const status = String(lineup.trangthai || "").toUpperCase();

            return lineupGender === gender && ["DA_CHOT", "DA_CAP_NHAT"].includes(status);
        });

        fillLineupSelect(selectedLineupId);
    }

    function renderTeamRegistrationState() {
        if (!teamSelect.value) {
            teamRegistration.classList.add("hidden");
            teamRegistration.innerHTML = "";
            if (lineupSelect) {
                lineupSelect.innerHTML = '<option value="">Chọn đội trước</option>';
                lineupSelect.disabled = true;
            }
            registerButton.disabled = true;
            registerButton.textContent = "Đăng ký giải";
            registerButton.dataset.mode = "register";
            delete registerButton.dataset.registrationId;

            if (current && eligibleTeamsForCurrentTournament().length === 0) {
                teamRegistration.classList.remove("hidden");
                teamRegistration.innerHTML = "Không có đội bóng nào của bạn đáp ứng điều kiện tham gia giải đấu này.";
            }
            return;
        }

        const selectedTeam = teams.find((team) => Number(team.iddoibong) === Number(teamSelect.value));
        const registration = registrationForSelectedTeam();
        teamRegistration.classList.remove("hidden");

        if (!registration) {
            const hasLineup = currentLineups.length > 0 && !lineupSelect?.disabled;
            teamRegistration.innerHTML = `
                <strong>${ui.escapeHtml(selectedTeam?.tendoibong || "Đội bóng")}</strong>
                chưa đăng ký tham gia giải đấu này.
                ${hasLineup ? "" : `<span class="state-detail">Chưa có đội hình ${ui.escapeHtml(genderLabel(tournamentGender()))} đã chốt/cập nhật phù hợp.</span>`}
            `;
            registerButton.disabled = !hasLineup;
            registerButton.textContent = "Đăng ký giải";
            registerButton.dataset.mode = "register";
            delete registerButton.dataset.registrationId;
            if (lineupSelect) lineupSelect.disabled = !hasLineup;
            return;
        }

        const [badgeClass, badgeLabel] = ui.badge(registration.trangthai);
        const status = registration.trangthai || "";
        const canCancel = ["CHO_DUYET", "DA_DUYET"].includes(status);
        const extra = [
            registration.ngaydangky ? `Ngày gửi: ${ui.escapeHtml(registration.ngaydangky)}` : "",
            registration.tendoihinh ? `Đội hình: ${ui.escapeHtml(registration.tendoihinh)} (${ui.escapeHtml(genderLabel(registration.gioitinh_doihinh))})` : "",
            registration.lydotuchoi ? `Lý do từ chối/hủy: ${ui.escapeHtml(registration.lydotuchoi)}` : "",
        ].filter(Boolean).join(" • ");
        teamRegistration.innerHTML = `
            <strong>${ui.escapeHtml(selectedTeam?.tendoibong || registration.tendoibong || "Đội bóng")}</strong>
            đã có hồ sơ đăng ký tham gia giải đấu.
            <span class="badge ${badgeClass}">${ui.escapeHtml(badgeLabel)}</span>
            ${extra ? `<span class="state-detail">${extra}</span>` : ""}
        `;
        if (lineupSelect) {
            lineupSelect.disabled = true;
            if (registration.iddoihinh && currentLineups.some((lineup) => Number(lineup.iddoihinh) === Number(registration.iddoihinh))) {
                lineupSelect.value = String(registration.iddoihinh);
            }
        }
        registerButton.disabled = !canCancel;
        registerButton.textContent = status === "DA_DUYET"
            ? "Hủy thi đấu"
            : (status === "CHO_DUYET" ? "Hủy đăng ký" : registrationStatusLabel(status));
        registerButton.dataset.mode = canCancel ? "cancel" : "registered";
        if (registration.iddangky) {
            registerButton.dataset.registrationId = registration.iddangky;
        } else {
            delete registerButton.dataset.registrationId;
        }
    }

    async function loadTournamentRegistrations(tournamentId) {
        const payload = await ui.requestJson(ui.apiUrl(registrationsApi, { tournament_id: tournamentId }));
        currentRegistrations = payload.data || [];
        fillTeamSelect();
        if (teamSelect.value) {
            const registration = registrationForSelectedTeam();
            await loadLineupsForTeam(teamSelect.value, registration?.iddoihinh || "");
        }
        renderTeamRegistrationState();
    }

    async function openDetail(id) {
        current = tournaments.find((item) => Number(item.idgiaidau) === Number(id));
        if (!current) return;
        currentRegistrations = [];
        currentLineups = [];
        teamSelect.value = "";
        if (lineupSelect) {
            lineupSelect.innerHTML = '<option value="">Chọn đội trước</option>';
            lineupSelect.disabled = true;
        }
        ui.hideAlert(alertBox);
        document.getElementById("d_name").textContent = current.tengiaidau || "Chi tiết giải đấu";
        document.getElementById("d_registerTime").value = dateRange(current.ngaytao, current.thoigianbatdau);
        document.getElementById("d_playTime").value = dateRange(current.thoigianbatdau, current.thoigianketthuc);
        document.getElementById("d_status").value = statusLabel(current);
        document.getElementById("d_gender").value = tournamentGenderText[current.gioitinh] || current.gioitinh || "Nam";
        document.getElementById("d_desc").textContent = current.mota || "Không có mô tả.";
        document.getElementById("d_ruleTitle").textContent = current.dieule_tieude || "Điều lệ giải đấu";
        document.getElementById("d_ruleContent").textContent = current.dieule_noidung || "Chưa có điều lệ được công bố.";
        fillTeamSelect();
        renderTeamRegistrationState();
        modal.classList.remove("hidden");
        await loadTournamentRegistrations(current.idgiaidau);
    }

    tbody.addEventListener("click", (event) => {
        const button = event.target.closest("button[data-action='view']");
        if (button) {
            openDetail(button.dataset.id).catch((error) => ui.show(pageMessage, ui.errorsText(error), true));
        }
    });

    document.getElementById("d_close").addEventListener("click", () => modal.classList.add("hidden"));
    document.getElementById("d_cancel").addEventListener("click", () => modal.classList.add("hidden"));
    document.getElementById("btnRefresh").addEventListener("click", () => load().catch((error) => ui.show(pageMessage, ui.errorsText(error), true)));
    q.addEventListener("input", () => load().catch(() => {}));

    teamSelect.addEventListener("change", () => {
        const registration = registrationForSelectedTeam();
        loadLineupsForTeam(teamSelect.value, registration?.iddoihinh || "")
            .then(renderTeamRegistrationState)
            .catch((error) => {
                currentLineups = [];
                fillLineupSelect();
                renderTeamRegistrationState();
                ui.showAlert(alertBox, ui.errorsText(error));
            });
    });

    registerButton.addEventListener("click", async () => {
        ui.hideAlert(alertBox);
        if (!current) return;
        if (!teamSelect.value) {
            ui.showAlert(alertBox, "Vui lòng chọn đội bóng đăng ký.");
            return;
        }

        if (registerButton.dataset.mode === "cancel") {
            const registration = registrationForSelectedTeam();
            const registrationId = Number(registerButton.dataset.registrationId || registration?.iddangky || 0);
            if (!registrationId) {
                ui.showAlert(alertBox, "Không xác định được hồ sơ đăng ký cần hủy.");
                return;
            }

            const selectedTeamId = teamSelect.value;
            const approved = registration?.trangthai === "DA_DUYET";
            const confirmed = window.confirm(approved ? "Hủy thi đấu cho đội này?" : "Hủy đăng ký giải đấu cho đội này?");
            if (!confirmed) return;

            try {
                const result = await ui.requestJson(`${registrationsApi}/${registrationId}/cancel`, {
                    method: "POST",
                    body: JSON.stringify({
                        reason: approved ? "HLV hủy thi đấu" : "HLV hủy đăng ký giải đấu",
                    }),
                });
                ui.show(pageMessage, result.message || (approved ? "Đã hủy thi đấu." : "Đã hủy đăng ký."));
                await loadTournamentRegistrations(current.idgiaidau);
                await load();
                fillTeamSelect(selectedTeamId);
                teamSelect.value = selectedTeamId;
                renderTeamRegistrationState();
            } catch (error) {
                ui.showAlert(alertBox, ui.errorsText(error));
            }
            return;
        }

        if (registrationForSelectedTeam()) {
            ui.showAlert(alertBox, "Đội bóng này đã có hồ sơ đăng ký trong giải đấu.");
            return;
        }

        if (!lineupSelect?.value) {
            ui.showAlert(alertBox, "Vui lòng chọn đội hình đăng ký.");
            return;
        }

        try {
            const registeredTeamId = teamSelect.value;
            const registeredLineupId = lineupSelect.value;
            const result = await ui.requestJson(`${registrationsApi}`, {
                method: "POST",
                body: JSON.stringify({
                    idgiaidau: current.idgiaidau,
                    iddoibong: teamSelect.value,
                    iddoihinh: registeredLineupId,
                }),
            });
            ui.show(pageMessage, result.message || "Đã gửi đăng ký, chờ duyệt.");
            await loadTournamentRegistrations(current.idgiaidau);
            await load();
            fillTeamSelect(registeredTeamId);
            teamSelect.value = registeredTeamId;
            await loadLineupsForTeam(registeredTeamId, registeredLineupId);
            renderTeamRegistrationState();
        } catch (error) {
            ui.showAlert(alertBox, ui.errorsText(error));
        }
    });

    load().catch((error) => ui.show(pageMessage, ui.errorsText(error), true));
})();
