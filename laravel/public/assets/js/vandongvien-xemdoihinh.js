(function () {
    const page = document.querySelector(".athlete-lineup-view");
    if (!page) return;

    const UI = window.AthleteUI;
    const api = page.dataset.lineupsApi;
    const message = document.getElementById("pageMessage");
    const empty = document.getElementById("empty");
    const header = document.getElementById("lineupHeader");
    const lineup = document.getElementById("lineup");

    async function load() {
        try {
            const payload = await UI.requestJson(api);
            const lineups = Array.isArray(payload.data) ? payload.data : [];
            const active = lineups.find((item) => item.current_athlete_in_lineup > 0) || lineups[0];

            empty.classList.toggle("hidden", Boolean(active));

            if (!active) {
                lineup.innerHTML = "";
                header.classList.add("hidden");
                return;
            }

            const detailPayload = await UI.requestJson(`${api}/${encodeURIComponent(active.iddoihinh)}`);
            const item = detailPayload.data || active;
            const details = Array.isArray(detailPayload.details) ? detailPayload.details : [];

            header.classList.remove("hidden");
            const tournamentName = item.tengiaidau || "Đội hình chính";
            header.innerHTML = `
                <h2>${UI.escapeHtml(item.tendoihinh || "Đội hình")}</h2>
                <p class="sub">${UI.escapeHtml(item.tendoibong || "-")} • ${UI.escapeHtml(tournamentName)} • ${UI.escapeHtml(item.trangthai || "-")}</p>
            `;
            lineup.innerHTML = details.length === 0
                ? `<section class="athlete-card empty">Đội hình chưa có thành viên.</section>`
                : details.map((detail) => `
                    <section class="athlete-card">
                        <div class="position">${UI.escapeHtml(detail.vitri || "-")}</div>
                        <div>${UI.escapeHtml(detail.hoten || detail.username || "-")}</div>
                        <p class="sub">${UI.escapeHtml(detail.mavandongvien || "")}</p>
                    </section>
                `).join("");
        } catch (error) {
            UI.showMessage(message, UI.errorsText(error), true);
        }
    }

    load();
})();
