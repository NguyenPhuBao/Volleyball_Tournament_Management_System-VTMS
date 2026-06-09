@extends('layouts.main')

@section('content')
    <section
        class="organizer-venues"
        data-venues-api="{{ url('/api/organizer/venues') }}"
        data-locations-api="{{ url('/api/organizer/competition-locations') }}"
    >
        <header class="venues-topbar">
            <div>
                <p class="eyebrow">BAN TO CHUC</p>
                <h1>Quan ly san dau</h1>
                <p class="sub">Bo sung, cap nhat va loai bo san dau bang cach chuyen sang trang thai ngung su dung.</p>
            </div>
            <button id="btnAdd" class="btn primary" type="button">Bo sung san dau</button>
        </header>

        <section class="venues-toolbar" aria-label="Bo loc san dau">
            <input id="q" type="text" placeholder="Tim theo ten san / dia chi">

            <select id="statusFilter">
                <option value="">Tat ca trang thai</option>
                <option value="HOAT_DONG">Hoat dong</option>
                <option value="DANG_BAO_TRI">Dang bao tri</option>
                <option value="NGUNG_SU_DUNG">Ngung su dung</option>
            </select>

            <button id="btnRefresh" class="btn" type="button">Lam moi</button>
        </section>

        <div class="venues-table-wrap">
            <table class="venues-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ten san</th>
                        <th>Vi tri thi dau</th>
                        <th>Dia chi</th>
                        <th>Suc chua</th>
                        <th>Trang thai</th>
                        <th>Ghi chu</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tbody">
                    <tr>
                        <td colspan="8" class="empty">Dang tai du lieu...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="venues-message" id="pageMessage" role="status"></p>
    </section>

    <div class="venue-modal hidden" id="venueModal" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="modal-head">
                <h2 id="modalTitle">Bo sung san dau</h2>
                <button id="m_close" class="icon" type="button" aria-label="Dong">x</button>
            </div>

            <div class="venue-grid">
                <div class="colspan">
                    <label for="m_name">Ten san</label>
                    <input id="m_name" placeholder="VD: San A - Nha thi dau IUH">
                </div>

                <div class="colspan">
                    <label for="m_location">Vi tri thi dau</label>
                    <select id="m_location"></select>
                </div>

                <div>
                    <label for="m_capacity">Suc chua</label>
                    <input id="m_capacity" type="number" min="0" value="0">
                </div>

                <div>
                    <label for="m_status">Trang thai</label>
                    <select id="m_status">
                        <option value="HOAT_DONG">Hoat dong</option>
                        <option value="DANG_BAO_TRI">Dang bao tri</option>
                        <option value="NGUNG_SU_DUNG">Ngung su dung</option>
                    </select>
                </div>

                <div class="colspan">
                    <label for="m_note">Mo ta / Ghi chu tuy chon</label>
                    <textarea id="m_note" rows="3" placeholder="VD: San thi dau chinh..."></textarea>
                </div>
            </div>

            <div id="m_alert" class="venue-alert hidden"></div>

            <div class="modal-actions">
                <button id="m_cancel" class="btn" type="button">Huy</button>
                <button id="m_remove" class="btn danger" type="button">Loai bo</button>
                <button id="m_save" class="btn primary" type="button">Luu</button>
            </div>
        </div>
    </div>
@endsection
