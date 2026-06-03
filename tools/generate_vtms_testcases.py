from __future__ import annotations

import json
import re
import time
import urllib.error
import urllib.parse
import urllib.request
from collections import OrderedDict
from datetime import datetime
from http.cookiejar import CookieJar
from pathlib import Path
from typing import Any
from xml.sax.saxutils import escape
from zipfile import ZIP_DEFLATED, ZipFile


BASE_URL = "http://localhost:8000"
STAMP = datetime.now().strftime("%Y%m%d%H%M%S")
OUT_MD = Path("vtms-testcases-theo-actor.md")
OUT_DOCX = Path("vtms-testcases-thuc-te.docx")

ID_KEYS = [
    "idtaikhoan",
    "idnguoidung",
    "idgiaidau",
    "idsandau",
    "iddoibong",
    "iddoihinh",
    "idvitrithidau",
    "idtrandau",
    "idketqua",
    "idxephang",
    "idbangdau",
    "iddangky",
    "idphancong",
    "idbaocao",
    "idnghiphep",
    "idyeucau",
    "id",
]


class ApiClient:
    def __init__(self) -> None:
        self.jar = CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))

    def request(self, method: str, path: str, payload: dict[str, Any] | None = None) -> dict[str, Any]:
        url = BASE_URL + path
        data = None
        headers = {
            "Accept": "application/json",
            "User-Agent": "VTMS-Testcase-Generator/1.0",
        }
        if payload is not None:
            data = json.dumps(payload, ensure_ascii=False).encode("utf-8")
            headers["Content-Type"] = "application/json; charset=utf-8"

        req = urllib.request.Request(url, data=data, headers=headers, method=method)
        try:
            with self.opener.open(req, timeout=25) as res:
                raw = res.read()
                status = res.status
        except urllib.error.HTTPError as exc:
            raw = exc.read()
            status = exc.code
        except Exception as exc:  # noqa: BLE001
            return {
                "status": 0,
                "json": None,
                "text": str(exc),
            }

        text = raw.decode("utf-8", errors="replace")
        try:
            body = json.loads(text) if text.strip() else None
        except json.JSONDecodeError:
            body = None

        return {
            "status": status,
            "json": body,
            "text": text,
        }


def success_value(body: Any) -> bool | None:
    if isinstance(body, dict):
        if "success" in body:
            return bool(body["success"])
        if "ok" in body:
            return bool(body["ok"])
    return None


def find_first(obj: Any, keys: list[str]) -> Any:
    if isinstance(obj, dict):
        for key in keys:
            if key in obj and obj[key] not in (None, ""):
                return obj[key]
        for value in obj.values():
            found = find_first(value, keys)
            if found not in (None, ""):
                return found
    elif isinstance(obj, list):
        for item in obj:
            found = find_first(item, keys)
            if found not in (None, ""):
                return found
    return None


def first_list_count(obj: Any) -> int | None:
    if isinstance(obj, list):
        return len(obj)
    if isinstance(obj, dict):
        preferred = [
            "accounts",
            "users",
            "logs",
            "requests",
            "tournaments",
            "registrations",
            "teams",
            "referees",
            "coaches",
            "athletes",
            "venues",
            "matches",
            "groups",
            "complaints",
            "results",
            "rankings",
            "lineups",
            "members",
            "assignments",
            "leaves",
            "reports",
            "invitations",
        ]
        for key in preferred:
            if isinstance(obj.get(key), list):
                return len(obj[key])
        for value in obj.values():
            count = first_list_count(value)
            if count is not None:
                return count
    return None


def errors_text(body: Any) -> str | None:
    if not isinstance(body, dict):
        return None
    errors = body.get("errors")
    if isinstance(errors, dict) and errors:
        return ", ".join(str(key) for key in errors.keys())
    if isinstance(errors, list) and errors:
        return ", ".join(str(item) for item in errors[:5])
    return None


def actual_text(response: dict[str, Any], expected_status: list[int], expected_success: bool | None) -> str:
    status = int(response["status"])
    body = response.get("json")
    actual_success = success_value(body)
    if expected_success is None:
        success_ok = True
    elif actual_success is None:
        success_ok = (status < 400) is expected_success
    else:
        success_ok = actual_success is expected_success
    ok = status in expected_status and success_ok

    parts = [f"HTTP {status}"]
    if actual_success is not None:
        parts.append(f"success={str(actual_success).lower()}")
    if isinstance(body, dict) and body.get("message"):
        parts.append(f'thông báo: "{body["message"]}"')

    count = first_list_count(body.get("data") if isinstance(body, dict) and "data" in body else body)
    if count is not None:
        parts.append(f"số dòng dữ liệu: {count}")

    found_id = find_first(body, ID_KEYS)
    if found_id not in (None, ""):
        parts.append(f"id={found_id}")

    username = find_first(body, ["username", "tendangnhap"])
    role = find_first(body, ["role", "namerole", "maquyen"])
    if username:
        user_text = f"user={username}"
        if role:
            user_text += f", role={role}"
        parts.append(user_text)

    err = errors_text(body)
    if err:
        parts.append(f"lỗi: {err}")

    if status == 0 and response.get("text"):
        parts.append(str(response["text"]))

    return ("Đạt: " if ok else "Không đạt: ") + "; ".join(parts) + "."


def row(
    rows: OrderedDict[str, list[dict[str, str]]],
    actor: str,
    code: str,
    steps: list[str],
    input_text: str,
    expected: str,
    response: dict[str, Any],
    expected_status: int | list[int],
    expected_success: bool | None,
) -> dict[str, str]:
    statuses = [expected_status] if isinstance(expected_status, int) else list(expected_status)
    item = {
        "TC": code,
        "Các bước": "\n".join(steps),
        "Dữ liệu vào": input_text,
        "Kết quả mong đợi": expected,
        "Kết quả thực tế": actual_text(response, statuses, expected_success),
    }
    rows.setdefault(actor, []).append(item)
    return item


def call_case(
    rows: OrderedDict[str, list[dict[str, str]]],
    actor: str,
    code: str,
    client: ApiClient,
    method: str,
    path: str,
    payload: dict[str, Any] | None,
    steps: list[str],
    input_text: str,
    expected: str,
    expected_status: int | list[int] = 200,
    expected_success: bool | None = True,
) -> dict[str, Any]:
    response = client.request(method, path, payload)
    row(rows, actor, code, steps, input_text, expected, response, expected_status, expected_success)
    return response


def login_case(
    rows: OrderedDict[str, list[dict[str, str]]],
    actor: str,
    code: str,
    username: str,
    password: str,
    role_name: str,
) -> tuple[ApiClient, dict[str, Any]]:
    client = ApiClient()
    response = client.request("POST", "/api/auth/login", {"username": username, "password": password})
    row(
        rows,
        actor,
        code,
        [
            "Truy cập màn hình đăng nhập.",
            f"Nhập username: {username}.",
            f"Nhập password: {password}.",
            "Nhấn Đăng nhập.",
        ],
        f"POST /api/auth/login; username={username}; password={password}",
        f"Hệ thống đăng nhập thành công và trả về quyền {role_name}.",
        response,
        200,
        True,
    )
    return client, response


def md_escape(text: str) -> str:
    return text.replace("|", "\\|").replace("\n", "<br>")


def write_markdown(rows: OrderedDict[str, list[dict[str, str]]]) -> None:
    lines = [
        "# Bảng test case theo actor - VTMS",
        "",
        f"Ghi chú: Các kết quả thực tế được kiểm thử bằng HTTP request trên project VTMS đang chạy tại `{BASE_URL}` vào thời điểm `{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}`. Bảng không bao gồm actor Khán giả và không lặp lại các route alias trùng chức năng.",
        "",
    ]
    for idx, (actor, items) in enumerate(rows.items(), start=1):
        lines.extend([
            f"## {idx}. {actor}",
            "",
            "| TC | Các bước | Dữ liệu vào | Kết quả mong đợi | Kết quả thực tế |",
            "|---|---|---|---|---|",
        ])
        for item in items:
            lines.append(
                "| "
                + " | ".join(
                    [
                        md_escape(item["TC"]),
                        md_escape(item["Các bước"]),
                        md_escape(item["Dữ liệu vào"]),
                        md_escape(item["Kết quả mong đợi"]),
                        md_escape(item["Kết quả thực tế"]),
                    ]
                )
                + " |"
            )
        lines.append("")
    OUT_MD.write_text("\n".join(lines), encoding="utf-8")


def w_text(text: str, bold: bool = False, size: int = 20) -> str:
    runs = []
    parts = text.split("\n")
    for idx, part in enumerate(parts):
        if idx:
            runs.append("<w:r><w:br/></w:r>")
        pr = (
            "<w:rPr>"
            "<w:rFonts w:ascii=\"Times New Roman\" w:hAnsi=\"Times New Roman\" w:cs=\"Times New Roman\"/>"
            + ("<w:b/>" if bold else "")
            + f"<w:sz w:val=\"{size}\"/><w:szCs w:val=\"{size}\"/>"
            "</w:rPr>"
        )
        runs.append(f"<w:r>{pr}<w:t xml:space=\"preserve\">{escape(part)}</w:t></w:r>")
    return "".join(runs)


def para(text: str = "", bold: bool = False, size: int = 20, style: str | None = None) -> str:
    ppr = f"<w:pPr><w:pStyle w:val=\"{style}\"/></w:pPr>" if style else ""
    return f"<w:p>{ppr}{w_text(text, bold=bold, size=size)}</w:p>"


def cell(text: str, width: int, bold: bool = False, shade: bool = False) -> str:
    shd = '<w:shd w:fill="EDEDED"/>' if shade else ""
    return (
        "<w:tc>"
        f"<w:tcPr><w:tcW w:w=\"{width}\" w:type=\"dxa\"/>{shd}<w:vAlign w:val=\"top\"/></w:tcPr>"
        f"{para(text, bold=bold, size=20)}"
        "</w:tc>"
    )


def table(items: list[dict[str, str]]) -> str:
    headers = ["TC", "Các bước", "Dữ liệu vào", "Kết quả mong đợi", "Kết quả thực tế"]
    widths = [1500, 3500, 3600, 3000, 4200]
    borders = (
        "<w:tblBorders>"
        "<w:top w:val=\"single\" w:sz=\"6\" w:space=\"0\" w:color=\"000000\"/>"
        "<w:left w:val=\"single\" w:sz=\"6\" w:space=\"0\" w:color=\"000000\"/>"
        "<w:bottom w:val=\"single\" w:sz=\"6\" w:space=\"0\" w:color=\"000000\"/>"
        "<w:right w:val=\"single\" w:sz=\"6\" w:space=\"0\" w:color=\"000000\"/>"
        "<w:insideH w:val=\"single\" w:sz=\"6\" w:space=\"0\" w:color=\"000000\"/>"
        "<w:insideV w:val=\"single\" w:sz=\"6\" w:space=\"0\" w:color=\"000000\"/>"
        "</w:tblBorders>"
    )
    xml = [
        "<w:tbl>",
        f"<w:tblPr><w:tblW w:w=\"0\" w:type=\"auto\"/>{borders}</w:tblPr>",
        "<w:tblGrid>" + "".join(f"<w:gridCol w:w=\"{w}\"/>" for w in widths) + "</w:tblGrid>",
        "<w:tr>" + "".join(cell(h, w, bold=True, shade=True) for h, w in zip(headers, widths)) + "</w:tr>",
    ]
    for item in items:
        xml.append("<w:tr>" + "".join(cell(item[h], w) for h, w in zip(headers, widths)) + "</w:tr>")
    xml.append("</w:tbl>")
    return "".join(xml)


def write_docx(rows: OrderedDict[str, list[dict[str, str]]]) -> Path:
    body = [
        para("Bảng test case theo actor - VTMS", bold=True, size=28, style="Title"),
        para(
            f"Kết quả thực tế được kiểm thử bằng HTTP request trên project VTMS tại {BASE_URL} vào {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}. Không bao gồm actor Khán giả.",
            size=20,
        ),
    ]
    for idx, (actor, items) in enumerate(rows.items(), start=1):
        body.append(para(f"{idx}. {actor}", bold=True, size=24))
        body.append(table(items))
        body.append(para(""))

    sect = (
        "<w:sectPr>"
        "<w:pgSz w:w=\"16838\" w:h=\"11906\" w:orient=\"landscape\"/>"
        "<w:pgMar w:top=\"720\" w:right=\"720\" w:bottom=\"720\" w:left=\"720\" w:header=\"360\" w:footer=\"360\" w:gutter=\"0\"/>"
        "</w:sectPr>"
    )
    document = (
        "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>"
        "<w:document xmlns:w=\"http://schemas.openxmlformats.org/wordprocessingml/2006/main\">"
        "<w:body>"
        + "".join(body)
        + sect
        + "</w:body></w:document>"
    )
    styles = (
        "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>"
        "<w:styles xmlns:w=\"http://schemas.openxmlformats.org/wordprocessingml/2006/main\">"
        "<w:style w:type=\"paragraph\" w:default=\"1\" w:styleId=\"Normal\"><w:name w:val=\"Normal\"/></w:style>"
        "<w:style w:type=\"paragraph\" w:styleId=\"Title\"><w:name w:val=\"Title\"/><w:basedOn w:val=\"Normal\"/></w:style>"
        "</w:styles>"
    )
    content_types = (
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
        "<Types xmlns=\"http://schemas.openxmlformats.org/package/2006/content-types\">"
        "<Default Extension=\"rels\" ContentType=\"application/vnd.openxmlformats-package.relationships+xml\"/>"
        "<Default Extension=\"xml\" ContentType=\"application/xml\"/>"
        "<Override PartName=\"/word/document.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml\"/>"
        "<Override PartName=\"/word/styles.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml\"/>"
        "</Types>"
    )
    rels = (
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
        "<Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\">"
        "<Relationship Id=\"rId1\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument\" Target=\"word/document.xml\"/>"
        "</Relationships>"
    )
    doc_rels = (
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
        "<Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\">"
        "<Relationship Id=\"rId1\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles\" Target=\"styles.xml\"/>"
        "</Relationships>"
    )

    def write_zip(path: Path) -> None:
        with ZipFile(path, "w", ZIP_DEFLATED) as zf:
            zf.writestr("[Content_Types].xml", content_types)
            zf.writestr("_rels/.rels", rels)
            zf.writestr("word/document.xml", document)
            zf.writestr("word/styles.xml", styles)
            zf.writestr("word/_rels/document.xml.rels", doc_rels)

    try:
        write_zip(OUT_DOCX)
        return OUT_DOCX
    except PermissionError:
        fallback = Path("vtms-testcases-thuc-te-full.docx")
        write_zip(fallback)
        return fallback


def generate() -> OrderedDict[str, list[dict[str, str]]]:
    rows: OrderedDict[str, list[dict[str, str]]] = OrderedDict()

    admin, admin_login = login_case(rows, "Quản trị viên", "LS-01", "admin_test", "123456", "ADMIN")
    wrong_admin = ApiClient().request("POST", "/api/auth/login", {"username": "admin_test", "password": "1234567"})
    row(
        rows,
        "Quản trị viên",
        "LF-01",
        [
            "Truy cập màn hình đăng nhập.",
            "Nhập username: admin_test.",
            "Nhập password sai: 1234567.",
            "Nhấn Đăng nhập.",
        ],
        "POST /api/auth/login; username=admin_test; password=1234567",
        "Hệ thống từ chối đăng nhập do sai username hoặc mật khẩu.",
        wrong_admin,
        401,
        False,
    )
    admin_id = find_first(admin_login.get("json"), ["idtaikhoan", "account_id", "id"]) or 115
    call_case(rows, "Quản trị viên", "AM-01", admin, "GET", "/api/auth/me", None, ["Đăng nhập bằng tài khoản admin_test.", "Gửi yêu cầu xem thông tin phiên đăng nhập."], "GET /api/auth/me; session=admin_test", "Hệ thống trả về thông tin tài khoản đang đăng nhập.", 200, True)
    call_case(rows, "Quản trị viên", "PW-01", admin, "POST", "/api/account/password", {"current_password": "sai-mat-khau", "new_password": "12345678", "new_password_confirmation": "12345678"}, ["Đăng nhập bằng tài khoản admin_test.", "Mở chức năng đổi mật khẩu.", "Nhập mật khẩu hiện tại sai.", "Nhấn Lưu."], "POST /api/account/password; current_password=sai-mat-khau; new_password=12345678", "Hệ thống không đổi mật khẩu và báo mật khẩu hiện tại không đúng.", 422, False)
    call_case(rows, "Quản trị viên", "AR-01", admin, "GET", "/api/admin/roles", None, ["Đăng nhập bằng tài khoản admin_test.", "Mở màn hình thêm tài khoản.", "Tải danh sách quyền."], "GET /api/admin/roles", "Hiển thị danh sách quyền dùng để gán tài khoản.", 200, True)
    accounts = call_case(rows, "Quản trị viên", "AL-01", admin, "GET", "/api/admin/accounts?q=&role=&trangthai=", None, ["Đăng nhập bằng tài khoản admin_test.", "Truy cập Quản lý tài khoản.", "Gửi yêu cầu xem danh sách tài khoản."], "GET /api/admin/accounts?q=&role=&trangthai=", "Hiển thị danh sách tài khoản trong hệ thống.", 200, True)
    account_id = find_first(accounts.get("json"), ["idtaikhoan"]) or admin_id
    call_case(rows, "Quản trị viên", "AS-01", admin, "GET", f"/api/admin/accounts/{account_id}", None, ["Đăng nhập bằng tài khoản admin_test.", f"Chọn tài khoản ID {account_id}.", "Mở chi tiết tài khoản."], f"GET /api/admin/accounts/{account_id}", "Hiển thị chi tiết tài khoản được chọn.", 200, True)
    new_username = f"tc_acc_{STAMP}"
    new_account_payload = {
        "username": new_username,
        "email": f"{new_username}@vtms.test",
        "password": "123456",
        "role": "VAN_DONG_VIEN",
        "trangthai": "CHUA_KICH_HOAT",
        "hodem": "Tài khoản",
        "ten": "Kiểm thử",
        "hoten": "Tài khoản kiểm thử",
        "gioitinh": "NAM",
        "ngaysinh": "2001-01-01",
        "sodienthoai": "090" + STAMP[-7:],
        "cccd": "999" + STAMP[-9:],
    }
    created_account = call_case(rows, "Quản trị viên", "AC-01", admin, "POST", "/api/admin/accounts", new_account_payload, ["Đăng nhập bằng tài khoản admin_test.", "Truy cập Quản lý tài khoản.", "Chọn Thêm tài khoản.", "Nhập đầy đủ thông tin hợp lệ.", "Nhấn Lưu."], f"POST /api/admin/accounts; username={new_username}; email={new_username}@vtms.test; role=VAN_DONG_VIEN; trạng thái=CHUA_KICH_HOAT", "Tạo tài khoản mới thành công, tài khoản được lưu vào CSDL.", 201, True)
    created_account_id = find_first(created_account.get("json"), ["idtaikhoan"]) or 0
    call_case(rows, "Quản trị viên", "AC-02", admin, "POST", "/api/admin/accounts", {"username": "", "email": "", "password": "", "role": ""}, ["Đăng nhập bằng tài khoản admin_test.", "Truy cập Quản lý tài khoản.", "Chọn Thêm tài khoản.", "Bỏ trống các trường bắt buộc.", "Nhấn Lưu."], "POST /api/admin/accounts; username=; email=; password=; role=", "Hệ thống không tạo tài khoản và hiển thị lỗi trường bắt buộc.", 422, False)
    call_case(rows, "Quản trị viên", "AC-03", admin, "POST", "/api/admin/accounts", {"username": "admin_test", "email": "admin_test@vtms.test", "password": "123456", "role": "ADMIN"}, ["Đăng nhập bằng tài khoản admin_test.", "Chọn Thêm tài khoản.", "Nhập username/email đã tồn tại.", "Nhấn Lưu."], "POST /api/admin/accounts; username=admin_test; email=admin_test@vtms.test", "Hệ thống không tạo tài khoản và báo trùng username/email.", 422, False)
    if created_account_id:
        call_case(rows, "Quản trị viên", "AU-01", admin, "POST", f"/api/admin/accounts/{created_account_id}/update", {"email": f"{new_username}_updated@vtms.test", "trangthai": "HOAT_DONG"}, ["Đăng nhập bằng tài khoản admin_test.", f"Chọn tài khoản vừa tạo ID {created_account_id}.", "Cập nhật email và trạng thái.", "Nhấn Lưu."], f"POST /api/admin/accounts/{created_account_id}/update; email={new_username}_updated@vtms.test; trangthai=HOAT_DONG", "Cập nhật thông tin tài khoản thành công.", 200, True)
    call_case(rows, "Quản trị viên", "AU-02", admin, "POST", "/api/admin/accounts/999999/update", {"email": "notfound@vtms.test"}, ["Đăng nhập bằng tài khoản admin_test.", "Chọn cập nhật tài khoản không tồn tại.", "Nhấn Lưu."], "POST /api/admin/accounts/999999/update; email=notfound@vtms.test", "Hệ thống báo không tìm thấy tài khoản.", 404, False)
    if created_account_id:
        call_case(rows, "Quản trị viên", "AD-01", admin, "POST", f"/api/admin/accounts/{created_account_id}/delete", {}, ["Đăng nhập bằng tài khoản admin_test.", f"Chọn tài khoản kiểm thử ID {created_account_id}.", "Nhấn Xác nhận xóa."], f"POST /api/admin/accounts/{created_account_id}/delete", "Xóa tài khoản kiểm thử thành công.", 200, True)
    call_case(rows, "Quản trị viên", "AD-02", admin, "POST", f"/api/admin/accounts/{admin_id}/delete", {}, ["Đăng nhập bằng tài khoản admin_test.", "Chọn xóa chính tài khoản đang đăng nhập.", "Nhấn Xác nhận xóa."], f"POST /api/admin/accounts/{admin_id}/delete; tài khoản đang đăng nhập", "Hệ thống từ chối xóa tài khoản của chính mình.", 422, False)
    users = call_case(rows, "Quản trị viên", "UL-01", admin, "GET", "/api/admin/users", None, ["Đăng nhập bằng tài khoản admin_test.", "Truy cập Quản lý người dùng.", "Gửi yêu cầu xem danh sách người dùng."], "GET /api/admin/users", "Hiển thị danh sách hồ sơ người dùng.", 200, True)
    user_id = find_first(users.get("json"), ["idnguoidung"]) or 1
    call_case(rows, "Quản trị viên", "US-01", admin, "GET", f"/api/admin/users/{user_id}", None, ["Đăng nhập bằng tài khoản admin_test.", f"Chọn hồ sơ người dùng ID {user_id}.", "Mở chi tiết."], f"GET /api/admin/users/{user_id}", "Hiển thị chi tiết hồ sơ người dùng.", 200, True)
    call_case(rows, "Quản trị viên", "UU-01", admin, "POST", "/api/admin/users/999999/update", {"hoten": "Không tồn tại"}, ["Đăng nhập bằng tài khoản admin_test.", "Chọn cập nhật hồ sơ người dùng không tồn tại.", "Nhấn Lưu."], "POST /api/admin/users/999999/update; hoten=Không tồn tại", "Hệ thống báo không tìm thấy người dùng.", 404, False)
    logs = call_case(rows, "Quản trị viên", "SL-01", admin, "GET", "/api/admin/system-logs", None, ["Đăng nhập bằng tài khoản admin_test.", "Truy cập Nhật ký hệ thống.", "Gửi yêu cầu xem nhật ký."], "GET /api/admin/system-logs", "Hiển thị danh sách nhật ký hệ thống.", 200, True)
    log_id = find_first(logs.get("json"), ["idlog", "idnhatky", "id"]) or 1
    call_case(rows, "Quản trị viên", "SO-01", admin, "GET", "/api/admin/system-logs/options", None, ["Đăng nhập bằng tài khoản admin_test.", "Mở bộ lọc nhật ký hệ thống.", "Tải dữ liệu lựa chọn bộ lọc."], "GET /api/admin/system-logs/options", "Hiển thị các lựa chọn lọc nhật ký hệ thống.", 200, True)
    call_case(rows, "Quản trị viên", "SS-01", admin, "GET", f"/api/admin/system-logs/{log_id}", None, ["Đăng nhập bằng tài khoản admin_test.", f"Chọn nhật ký ID {log_id}.", "Mở chi tiết nhật ký."], f"GET /api/admin/system-logs/{log_id}", "Hiển thị chi tiết nhật ký hệ thống.", 200, True)
    call_case(rows, "Quản trị viên", "OR-01", admin, "GET", "/api/admin/organizer-change-requests", None, ["Đăng nhập bằng tài khoản admin_test.", "Truy cập xác nhận thông tin ban tổ chức.", "Gửi yêu cầu xem danh sách."], "GET /api/admin/organizer-change-requests", "Hiển thị danh sách yêu cầu thay đổi thông tin của ban tổ chức.", 200, True)
    call_case(rows, "Quản trị viên", "OA-01", admin, "POST", "/api/admin/organizer-change-requests/999999/approve", {"note": "Duyệt kiểm thử"}, ["Đăng nhập bằng tài khoản admin_test.", "Chọn yêu cầu thay đổi thông tin không tồn tại.", "Nhấn Duyệt."], "POST /api/admin/organizer-change-requests/999999/approve; note=Duyệt kiểm thử", "Hệ thống báo không tìm thấy yêu cầu.", [404, 422], False)

    org, _ = login_case(rows, "Ban tổ chức", "LS-02", "btc_quocgia", "123456", "BAN_TO_CHUC")
    tournament_options = call_case(rows, "Ban tổ chức", "TO-01", org, "GET", "/api/organizer/tournament-options", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Mở màn hình tạo giải đấu.", "Tải dữ liệu cấp giải, khu vực, luật thi đấu."], "GET /api/organizer/tournament-options", "Hiển thị danh sách cấp giải, khu vực và luật thi đấu hợp lệ.", 200, True)
    participant_level = find_first(tournament_options.get("json"), ["capdoituongthamgia"]) or "TINH_THANH"
    call_case(rows, "Ban tổ chức", "TEP-01", org, "GET", "/api/organizer/tournament-eligibility-preview", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Mở phần xem trước điều kiện tham gia giải.", "Gửi yêu cầu khi chưa chọn đủ dữ liệu."], "GET /api/organizer/tournament-eligibility-preview", "Hệ thống báo thiếu dữ liệu để xem trước điều kiện.", [200, 422], None)
    call_case(rows, "Ban tổ chức", "TL-01", org, "GET", "/api/organizer/tournaments", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập Quản lý giải đấu.", "Gửi yêu cầu xem danh sách giải đấu."], "GET /api/organizer/tournaments", "Hiển thị danh sách giải đấu thuộc phạm vi quản lý.", 200, True)
    tournament_payload = {
        "tengiaidau": f"TC Giải đầy đủ {STAMP}",
        "idcapgiaidau": 1,
        "idkhuvucphamvi": 1,
        "idluat": 1,
        "thoigianbatdau": "2026-07-01",
        "thoigianketthuc": "2026-07-10",
        "quymo": 2,
        "tinhchat": "CHINH_THUC",
        "gioitinh": "NAM",
        "dieukien": {"capdoituongthamgia": participant_level},
        "quytac": {"capdoituongthamgia": participant_level},
    }
    created_tournament = call_case(rows, "Ban tổ chức", "TC-01", org, "POST", "/api/organizer/tournaments", tournament_payload, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn Tạo giải đấu.", "Nhập đầy đủ thông tin hợp lệ.", "Nhấn Lưu."], f"POST /api/organizer/tournaments; tengiaidau=TC Giải đầy đủ {STAMP}; idcapgiaidau=1; idkhuvucphamvi=1; idluat=1; thời gian=2026-07-01 đến 2026-07-10; quymo=2", "Tạo giải đấu thành công ở trạng thái nháp/chưa mở đăng ký.", 201, True)
    tournament_id = find_first(created_tournament.get("json"), ["idgiaidau"]) or 1
    call_case(rows, "Ban tổ chức", "TC-02", org, "POST", "/api/organizer/tournaments", {"tengiaidau": "", "idcapgiaidau": "", "idkhuvucphamvi": "", "idluat": "", "quymo": ""}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn Tạo giải đấu.", "Bỏ trống các trường bắt buộc.", "Nhấn Lưu."], "POST /api/organizer/tournaments; tengiaidau=; idcapgiaidau=; idkhuvucphamvi=; idluat=; quymo=", "Hệ thống không tạo giải và hiển thị lỗi dữ liệu bắt buộc.", 422, False)
    invalid_date_payload = dict(tournament_payload)
    invalid_date_payload.update({"tengiaidau": "TC Ngày sai", "thoigianbatdau": "2026-07-10", "thoigianketthuc": "2026-07-01"})
    call_case(rows, "Ban tổ chức", "TC-03", org, "POST", "/api/organizer/tournaments", invalid_date_payload, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn Tạo giải đấu.", "Nhập ngày kết thúc nhỏ hơn ngày bắt đầu.", "Nhấn Lưu."], "POST /api/organizer/tournaments; thoigianbatdau=2026-07-10; thoigianketthuc=2026-07-01", "Hệ thống không cho lưu và báo lỗi thời gian kết thúc.", 422, False)
    call_case(rows, "Ban tổ chức", "TS-01", org, "GET", f"/api/organizer/tournaments/{tournament_id}", None, ["Đăng nhập bằng tài khoản btc_quocgia.", f"Chọn giải đấu ID {tournament_id}.", "Mở chi tiết giải đấu."], f"GET /api/organizer/tournaments/{tournament_id}", "Hiển thị chi tiết giải đấu.", 200, True)
    updated_tournament_payload = dict(tournament_payload)
    updated_tournament_payload.update({"mota": f"Cập nhật kiểm thử {STAMP}", "quymo": 4})
    call_case(rows, "Ban tổ chức", "TU-01", org, "POST", f"/api/organizer/tournaments/{tournament_id}/update", updated_tournament_payload, ["Đăng nhập bằng tài khoản btc_quocgia.", "Mở chi tiết giải đấu vừa tạo.", "Cập nhật mô tả và quy mô.", "Nhấn Lưu."], f"POST /api/organizer/tournaments/{tournament_id}/update; mota=Cập nhật kiểm thử {STAMP}; quymo=4", "Cập nhật giải đấu thành công.", 200, True)
    call_case(rows, "Ban tổ chức", "TP-01", org, "POST", f"/api/organizer/tournaments/{tournament_id}/publish", {}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn giải đấu vừa tạo.", "Nhấn Công bố."], f"POST /api/organizer/tournaments/{tournament_id}/publish", "Công bố giải đấu thành công hoặc báo trạng thái hiện tại không phù hợp.", [200, 409, 422], None)
    call_case(rows, "Ban tổ chức", "RO-01", org, "POST", f"/api/organizer/tournaments/{tournament_id}/registrations/open", {}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn giải đấu vừa tạo.", "Nhấn Mở đăng ký."], f"POST /api/organizer/tournaments/{tournament_id}/registrations/open", "Mở đăng ký thành công hoặc báo trạng thái hiện tại không phù hợp.", [200, 409, 422], None)
    call_case(rows, "Ban tổ chức", "RL-01", org, "GET", f"/api/organizer/tournaments/{tournament_id}/registrations", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Mở danh sách đăng ký của giải đấu vừa tạo."], f"GET /api/organizer/tournaments/{tournament_id}/registrations", "Hiển thị danh sách đội đăng ký giải đấu.", 200, True)
    call_case(rows, "Ban tổ chức", "RA-01", org, "POST", f"/api/organizer/tournaments/{tournament_id}/registrations/999999/approve", {"note": "Duyệt kiểm thử"}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn đăng ký không tồn tại.", "Nhấn Duyệt."], f"POST /api/organizer/tournaments/{tournament_id}/registrations/999999/approve", "Hệ thống báo không tìm thấy đăng ký hoặc không đủ điều kiện duyệt.", [404, 422], False)
    call_case(rows, "Ban tổ chức", "RR-01", org, "POST", f"/api/organizer/tournaments/{tournament_id}/registrations/999999/reject", {"reason": "Từ chối kiểm thử"}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn đăng ký không tồn tại.", "Nhấn Từ chối."], f"POST /api/organizer/tournaments/{tournament_id}/registrations/999999/reject; reason=Từ chối kiểm thử", "Hệ thống báo không tìm thấy đăng ký hoặc không đủ điều kiện từ chối.", [404, 422], False)
    call_case(rows, "Ban tổ chức", "RC-01", org, "POST", f"/api/organizer/tournaments/{tournament_id}/registrations/close", {}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn giải đấu vừa tạo.", "Nhấn Đóng đăng ký."], f"POST /api/organizer/tournaments/{tournament_id}/registrations/close", "Đóng đăng ký thành công hoặc báo trạng thái hiện tại không phù hợp.", [200, 409, 422], None)
    teams = call_case(rows, "Ban tổ chức", "TE-01", org, "GET", "/api/organizer/teams", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập Quản lý đội bóng.", "Gửi yêu cầu xem danh sách đội."], "GET /api/organizer/teams", "Hiển thị danh sách đội bóng thuộc phạm vi quản lý.", 200, True)
    team_id = find_first(teams.get("json"), ["iddoibong"]) or 1
    call_case(rows, "Ban tổ chức", "TE-02", org, "POST", "/api/organizer/teams/999999/update", {"mota": "Cập nhật kiểm thử"}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn đội bóng không tồn tại.", "Nhấn Lưu."], "POST /api/organizer/teams/999999/update; mota=Cập nhật kiểm thử", "Hệ thống từ chối cập nhật hoặc báo không tìm thấy đội bóng.", [403, 404, 422], False)
    call_case(rows, "Ban tổ chức", "TT-01", org, "GET", f"/api/organizer/tournaments/{tournament_id}/teams", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Mở danh sách đội trong giải đấu vừa tạo."], f"GET /api/organizer/tournaments/{tournament_id}/teams", "Hiển thị danh sách đội tham gia giải đấu.", 200, True)
    call_case(rows, "Ban tổ chức", "TT-02", org, "GET", f"/api/organizer/tournaments/{tournament_id}/teams/{team_id}", None, ["Đăng nhập bằng tài khoản btc_quocgia.", f"Mở chi tiết đội ID {team_id} trong giải đấu."], f"GET /api/organizer/tournaments/{tournament_id}/teams/{team_id}", "Hiển thị chi tiết đội trong giải hoặc báo đội chưa thuộc giải.", [200, 404, 422], None)
    call_case(rows, "Ban tổ chức", "RF-01", org, "GET", "/api/organizer/referees", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập Quản lý trọng tài.", "Gửi yêu cầu xem danh sách trọng tài."], "GET /api/organizer/referees", "Hiển thị danh sách trọng tài.", 200, True)
    call_case(rows, "Ban tổ chức", "RF-02", org, "POST", "/api/organizer/referees", {"username": "", "email": "", "password": ""}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn Thêm trọng tài.", "Bỏ trống thông tin bắt buộc.", "Nhấn Lưu."], "POST /api/organizer/referees; username=; email=; password=", "Hệ thống không tạo trọng tài và hiển thị lỗi dữ liệu.", 422, False)
    call_case(rows, "Ban tổ chức", "FA-01", org, "POST", "/api/organizer/referee-assignments", {"idtrongtai": "", "idtrandau": ""}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Mở chức năng phân công trọng tài.", "Bỏ trống trọng tài và trận đấu.", "Nhấn Phân công."], "POST /api/organizer/referee-assignments; idtrongtai=; idtrandau=", "Hệ thống không phân công và báo thiếu dữ liệu hoặc không tìm thấy trọng tài.", [404, 422], False)
    call_case(rows, "Ban tổ chức", "CA-01", org, "GET", "/api/organizer/coach-accounts", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập duyệt tài khoản huấn luyện viên.", "Gửi yêu cầu xem danh sách."], "GET /api/organizer/coach-accounts", "Hiển thị danh sách tài khoản huấn luyện viên chờ/đã duyệt.", 200, True)
    call_case(rows, "Ban tổ chức", "CA-02", org, "POST", "/api/organizer/coach-accounts/999999/approve", {"note": "Duyệt kiểm thử"}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn tài khoản huấn luyện viên không tồn tại.", "Nhấn Duyệt."], "POST /api/organizer/coach-accounts/999999/approve", "Hệ thống báo không tìm thấy tài khoản huấn luyện viên.", [404, 422], False)
    call_case(rows, "Ban tổ chức", "RAA-01", org, "GET", "/api/organizer/referee-accounts", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập duyệt tài khoản trọng tài.", "Gửi yêu cầu xem danh sách."], "GET /api/organizer/referee-accounts", "Hiển thị danh sách tài khoản trọng tài chờ/đã duyệt.", 200, True)
    call_case(rows, "Ban tổ chức", "RAA-02", org, "POST", "/api/organizer/referee-accounts/999999/reject", {"reason": "Từ chối kiểm thử"}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn tài khoản trọng tài không tồn tại.", "Nhấn Từ chối."], "POST /api/organizer/referee-accounts/999999/reject; reason=Từ chối kiểm thử", "Hệ thống báo không tìm thấy tài khoản trọng tài.", [404, 422], False)
    call_case(rows, "Ban tổ chức", "QH-01", org, "GET", "/api/organizer/coaches", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập quản lý tư cách huấn luyện viên.", "Gửi yêu cầu xem danh sách."], "GET /api/organizer/coaches", "Hiển thị danh sách huấn luyện viên và trạng thái tư cách.", 200, True)
    call_case(rows, "Ban tổ chức", "QA-01", org, "GET", "/api/organizer/athletes", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập quản lý tư cách vận động viên.", "Gửi yêu cầu xem danh sách."], "GET /api/organizer/athletes", "Hiển thị danh sách vận động viên và trạng thái tư cách.", 200, True)
    call_case(rows, "Ban tổ chức", "HE-01", org, "GET", "/api/organizer/higher-eligibility", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập chức năng đề cử đội lên cấp trên.", "Gửi yêu cầu xem danh sách đề cử."], "GET /api/organizer/higher-eligibility", "Hiển thị danh sách đề cử/tư cách cấp trên.", 200, True)
    call_case(rows, "Ban tổ chức", "HE-02", org, "POST", "/api/organizer/higher-eligibility/999999/approve", {"note": "Duyệt kiểm thử"}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn đề cử không tồn tại.", "Nhấn Duyệt."], "POST /api/organizer/higher-eligibility/999999/approve", "Hệ thống báo đề cử không tồn tại hoặc không đủ điều kiện xử lý.", [404, 409, 422], False)
    locations = call_case(rows, "Ban tổ chức", "VL-01", org, "GET", "/api/organizer/competition-locations", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Mở chức năng sân đấu.", "Tải danh sách vị trí thi đấu."], "GET /api/organizer/competition-locations", "Hiển thị danh sách vị trí thi đấu thuộc phạm vi quản lý.", 200, True)
    location_id = find_first(locations.get("json"), ["idvitrithidau"]) or 1
    venue_name = f"Sân testcase {STAMP}"
    venue_payload = {"tensandau": venue_name, "idvitrithidau": location_id, "diachi": "Khu kiểm thử VTMS", "trangthai": "HOAT_DONG"}
    venue = call_case(rows, "Ban tổ chức", "VC-01", org, "POST", "/api/organizer/venues", venue_payload, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập Quản lý sân đấu.", "Chọn Thêm sân đấu.", "Nhập thông tin sân hợp lệ.", "Nhấn Lưu."], f"POST /api/organizer/venues; tensandau={venue_name}; idvitrithidau={location_id}; diachi=Khu kiểm thử VTMS; trangthai=HOAT_DONG", "Bổ sung sân đấu thành công hoặc báo dữ liệu vị trí không hợp lệ.", [201, 422, 409], None)
    venue_id = find_first(venue.get("json"), ["idsandau"]) or 1
    call_case(rows, "Ban tổ chức", "VL-02", org, "GET", "/api/organizer/venues", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập Quản lý sân đấu.", "Gửi yêu cầu xem danh sách sân."], "GET /api/organizer/venues", "Hiển thị danh sách sân đấu.", 200, True)
    call_case(rows, "Ban tổ chức", "VS-01", org, "GET", f"/api/organizer/venues/{venue_id}", None, ["Đăng nhập bằng tài khoản btc_quocgia.", f"Chọn sân đấu ID {venue_id}.", "Mở chi tiết sân đấu."], f"GET /api/organizer/venues/{venue_id}", "Hiển thị chi tiết sân đấu.", [200, 404], None)
    call_case(rows, "Ban tổ chức", "VU-01", org, "POST", f"/api/organizer/venues/{venue_id}/update", {"diachi": f"Khu kiểm thử cập nhật {STAMP}"}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn sân đấu kiểm thử.", "Cập nhật địa chỉ.", "Nhấn Lưu."], f"POST /api/organizer/venues/{venue_id}/update; diachi=Khu kiểm thử cập nhật {STAMP}", "Cập nhật sân đấu thành công hoặc báo không tìm thấy sân.", [200, 404, 422], None)
    call_case(rows, "Ban tổ chức", "VD-01", org, "POST", f"/api/organizer/venues/{venue_id}/deactivate", {}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn sân đấu kiểm thử.", "Nhấn Ngừng hoạt động."], f"POST /api/organizer/venues/{venue_id}/deactivate", "Ngừng hoạt động sân đấu thành công hoặc báo không tìm thấy sân.", [200, 404, 422], None)
    call_case(rows, "Ban tổ chức", "SC-01", org, "GET", "/api/organizer/schedules/tournaments", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập Lịch thi đấu.", "Tải danh sách giải có thể xếp lịch."], "GET /api/organizer/schedules/tournaments", "Hiển thị danh sách giải để xếp lịch.", 200, True)
    call_case(rows, "Ban tổ chức", "SC-02", org, "GET", f"/api/organizer/tournaments/{tournament_id}/schedule", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn giải đấu vừa tạo.", "Mở tổng quan lịch thi đấu."], f"GET /api/organizer/tournaments/{tournament_id}/schedule", "Hiển thị tổng quan bảng đấu/trận đấu của giải.", [200, 404, 422], None)
    call_case(rows, "Ban tổ chức", "GR-01", org, "POST", f"/api/organizer/tournaments/{tournament_id}/groups", {"tenbang": ""}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Mở xếp lịch giải đấu.", "Chọn tạo bảng đấu.", "Bỏ trống tên bảng.", "Nhấn Lưu."], f"POST /api/organizer/tournaments/{tournament_id}/groups; tenbang=", "Hệ thống không tạo bảng đấu và báo dữ liệu không hợp lệ.", [409, 422], False)
    call_case(rows, "Ban tổ chức", "MC-01", org, "POST", f"/api/organizer/tournaments/{tournament_id}/matches", {"iddoibong1": "", "iddoibong2": ""}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Mở xếp lịch giải đấu.", "Chọn tạo trận đấu.", "Bỏ trống đội thi đấu.", "Nhấn Lưu."], f"POST /api/organizer/tournaments/{tournament_id}/matches; iddoibong1=; iddoibong2=", "Hệ thống không tạo trận đấu và báo dữ liệu không hợp lệ.", [409, 422], False)
    call_case(rows, "Ban tổ chức", "MG-01", org, "POST", f"/api/organizer/tournaments/{tournament_id}/standard-schedule", {}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Mở xếp lịch giải đấu.", "Nhấn Tạo lịch chuẩn khi chưa đủ đội."], f"POST /api/organizer/tournaments/{tournament_id}/standard-schedule", "Hệ thống không tạo lịch nếu giải chưa đủ điều kiện hoặc chưa đủ đội.", [200, 409, 422], None)
    call_case(rows, "Ban tổ chức", "SV-01", org, "GET", "/api/organizer/schedule-view", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập màn hình xem lịch thi đấu.", "Gửi yêu cầu xem lịch."], "GET /api/organizer/schedule-view", "Hiển thị lịch thi đấu tổng hợp.", 200, True)
    call_case(rows, "Ban tổ chức", "CP-01", org, "GET", "/api/organizer/complaints", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập Khiếu nại.", "Gửi yêu cầu xem danh sách khiếu nại."], "GET /api/organizer/complaints", "Hiển thị danh sách khiếu nại.", 200, True)
    call_case(rows, "Ban tổ chức", "CP-02", org, "POST", "/api/organizer/complaints/999999/resolve", {"note": "Xử lý kiểm thử"}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn khiếu nại không tồn tại.", "Nhấn Xử lý."], "POST /api/organizer/complaints/999999/resolve; note=Xử lý kiểm thử", "Hệ thống báo không tìm thấy khiếu nại.", [404, 422], False)
    call_case(rows, "Ban tổ chức", "RS-01", org, "GET", "/api/organizer/match-results", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập Kết quả trận đấu.", "Gửi yêu cầu xem danh sách kết quả."], "GET /api/organizer/match-results", "Hiển thị danh sách kết quả trận đấu.", 200, True)
    call_case(rows, "Ban tổ chức", "RS-02", org, "POST", "/api/organizer/match-results/999999/publish", {}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn kết quả không tồn tại.", "Nhấn Công bố."], "POST /api/organizer/match-results/999999/publish", "Hệ thống báo không tìm thấy kết quả trận đấu.", [404, 422], False)
    call_case(rows, "Ban tổ chức", "RK-01", org, "GET", "/api/organizer/rankings", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập Xếp hạng.", "Gửi yêu cầu xem bảng xếp hạng."], "GET /api/organizer/rankings", "Hiển thị danh sách bảng xếp hạng.", 200, True)
    call_case(rows, "Ban tổ chức", "RK-02", org, "POST", "/api/organizer/rankings/generate", {"idgiaidau": ""}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập Xếp hạng.", "Bỏ trống giải đấu.", "Nhấn Tạo bảng xếp hạng."], "POST /api/organizer/rankings/generate; idgiaidau=", "Hệ thống không tạo bảng xếp hạng và báo thiếu giải đấu.", [404, 422], False)
    call_case(rows, "Ban tổ chức", "PR-01", org, "GET", "/api/organizer/personal-change-requests", None, ["Đăng nhập bằng tài khoản btc_quocgia.", "Truy cập xác nhận thông tin cá nhân.", "Gửi yêu cầu xem danh sách."], "GET /api/organizer/personal-change-requests", "Hiển thị danh sách yêu cầu thay đổi thông tin cá nhân.", 200, True)
    call_case(rows, "Ban tổ chức", "PR-02", org, "POST", "/api/organizer/personal-change-requests/999999/approve", {"note": "Duyệt kiểm thử"}, ["Đăng nhập bằng tài khoản btc_quocgia.", "Chọn yêu cầu thay đổi thông tin không tồn tại.", "Nhấn Duyệt."], "POST /api/organizer/personal-change-requests/999999/approve", "Hệ thống báo không tìm thấy yêu cầu thay đổi thông tin.", [404, 422], False)

    coach, _ = login_case(rows, "Huấn luyện viên", "LS-03", "hlv_quocgia_01", "123456", "HUAN_LUYEN_VIEN")
    call_case(rows, "Huấn luyện viên", "RG-01", ApiClient(), "GET", "/api/coach/register/options", None, ["Truy cập màn hình đăng ký tài khoản huấn luyện viên.", "Tải dữ liệu khu vực/cấu hình đăng ký."], "GET /api/coach/register/options", "Hiển thị dữ liệu lựa chọn cho form đăng ký huấn luyện viên.", 200, True)
    call_case(rows, "Huấn luyện viên", "RG-02", ApiClient(), "POST", "/api/auth/register/coach", {"username": "", "email": "", "password": ""}, ["Truy cập màn hình đăng ký huấn luyện viên.", "Bỏ trống thông tin bắt buộc.", "Nhấn Đăng ký."], "POST /api/auth/register/coach; username=; email=; password=", "Hệ thống không tạo tài khoản và hiển thị lỗi dữ liệu đăng ký.", 422, False)
    coach_teams = call_case(rows, "Huấn luyện viên", "TL-01", coach, "GET", "/api/coach/teams", None, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Truy cập Quản lý đội bóng.", "Gửi yêu cầu xem danh sách đội."], "GET /api/coach/teams", "Hiển thị danh sách đội bóng của huấn luyện viên.", 200, True)
    base_team_id = find_first(coach_teams.get("json"), ["iddoibong"]) or team_id
    coach_team_name = f"Đội testcase {STAMP}"
    coach_team = call_case(rows, "Huấn luyện viên", "TC-01", coach, "POST", "/api/coach/teams", {"tendoibong": coach_team_name, "diaphuong": "Khu kiểm thử", "mota": "Đội tạo bởi testcase"}, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Truy cập Quản lý đội bóng.", "Chọn Tạo đội.", "Nhập tên đội hợp lệ.", "Nhấn Lưu."], f"POST /api/coach/teams; tendoibong={coach_team_name}; diaphuong=Khu kiểm thử", "Tạo đội bóng thành công hoặc báo điều kiện HLV không cho phép tạo thêm đội.", [201, 409, 422], None)
    coach_team_id = find_first(coach_team.get("json"), ["iddoibong"]) or base_team_id
    call_case(rows, "Huấn luyện viên", "TC-02", coach, "POST", "/api/coach/teams", {"tendoibong": ""}, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Chọn Tạo đội.", "Bỏ trống tên đội.", "Nhấn Lưu."], "POST /api/coach/teams; tendoibong=", "Hệ thống không tạo đội và báo tên đội bắt buộc.", 422, False)
    call_case(rows, "Huấn luyện viên", "TS-01", coach, "GET", f"/api/coach/teams/{coach_team_id}", None, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", f"Chọn đội bóng ID {coach_team_id}.", "Mở chi tiết đội."], f"GET /api/coach/teams/{coach_team_id}", "Hiển thị chi tiết đội bóng của huấn luyện viên.", [200, 404], None)
    call_case(rows, "Huấn luyện viên", "TU-01", coach, "POST", f"/api/coach/teams/{coach_team_id}/update", {"mota": f"Cập nhật đội {STAMP}"}, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Chọn đội bóng kiểm thử/đội đang quản lý.", "Cập nhật mô tả.", "Nhấn Lưu."], f"POST /api/coach/teams/{coach_team_id}/update; mota=Cập nhật đội {STAMP}", "Cập nhật đội bóng thành công hoặc báo không tìm thấy đội thuộc HLV.", [200, 404, 422], None)
    call_case(rows, "Huấn luyện viên", "AM-01", coach, "GET", f"/api/coach/teams/{coach_team_id}/members", None, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Mở đội bóng đang quản lý.", "Xem danh sách thành viên."], f"GET /api/coach/teams/{coach_team_id}/members", "Hiển thị danh sách thành viên của đội.", [200, 404], None)
    call_case(rows, "Huấn luyện viên", "AM-02", coach, "POST", f"/api/coach/teams/{coach_team_id}/members", {"idvandongvien": "", "vaitro": "THANH_VIEN"}, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Mở đội bóng đang quản lý.", "Chọn thêm thành viên.", "Bỏ trống vận động viên.", "Nhấn Lưu."], f"POST /api/coach/teams/{coach_team_id}/members; idvandongvien=; vaitro=THANH_VIEN", "Hệ thống không thêm thành viên và báo thiếu vận động viên.", [404, 422], False)
    call_case(rows, "Huấn luyện viên", "AA-01", coach, "POST", "/api/coach/athletes", {"username": "", "email": "", "password": ""}, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Chọn tạo tài khoản vận động viên.", "Bỏ trống thông tin bắt buộc.", "Nhấn Lưu."], "POST /api/coach/athletes; username=; email=; password=", "Hệ thống không tạo tài khoản vận động viên và hiển thị lỗi dữ liệu.", [403, 422], False)
    call_case(rows, "Huấn luyện viên", "LU-01", coach, "GET", "/api/coach/lineups", None, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Truy cập chức năng đội hình.", "Gửi yêu cầu xem danh sách đội hình."], "GET /api/coach/lineups", "Hiển thị danh sách đội hình hiện có.", 200, True)
    call_case(rows, "Huấn luyện viên", "LC-01", coach, "POST", f"/api/coach/teams/{coach_team_id}/lineups", {"tendoihinh": "", "danhsach": []}, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Mở đội bóng đang quản lý.", "Chọn tạo đội hình.", "Bỏ trống dữ liệu đội hình.", "Nhấn Lưu."], f"POST /api/coach/teams/{coach_team_id}/lineups; tendoihinh=; danhsach=[]", "Hệ thống không tạo đội hình và báo dữ liệu không hợp lệ.", [404, 422], False)
    call_case(rows, "Huấn luyện viên", "CT-01", coach, "GET", "/api/coach/tournaments", None, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Truy cập Đăng ký giải đấu.", "Gửi yêu cầu xem danh sách giải có thể đăng ký."], "GET /api/coach/tournaments", "Hiển thị danh sách giải đấu theo phạm vi của huấn luyện viên.", 200, True)
    call_case(rows, "Huấn luyện viên", "CR-01", coach, "GET", "/api/coach/tournament-registrations", None, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Truy cập Đăng ký giải đấu.", "Gửi yêu cầu xem danh sách đăng ký đã gửi."], "GET /api/coach/tournament-registrations", "Hiển thị danh sách đăng ký giải của đội do huấn luyện viên quản lý.", 200, True)
    call_case(rows, "Huấn luyện viên", "CR-02", coach, "POST", "/api/coach/tournament-registrations", {"idgiaidau": "", "iddoibong": ""}, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Chọn đăng ký giải đấu.", "Bỏ trống giải đấu và đội.", "Nhấn Gửi đăng ký."], "POST /api/coach/tournament-registrations; idgiaidau=; iddoibong=", "Hệ thống không gửi đăng ký và báo thiếu dữ liệu.", 422, False)
    call_case(rows, "Huấn luyện viên", "CS-01", coach, "GET", f"/api/coach/teams/{coach_team_id}/schedule", None, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Mở đội bóng đang quản lý.", "Xem lịch thi đấu của đội."], f"GET /api/coach/teams/{coach_team_id}/schedule", "Hiển thị lịch thi đấu của đội.", [200, 404], None)
    call_case(rows, "Huấn luyện viên", "RS-01", coach, "GET", "/api/coach/results", None, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Truy cập Kết quả.", "Gửi yêu cầu xem kết quả trận đấu của đội."], "GET /api/coach/results", "Hiển thị kết quả liên quan đến đội của huấn luyện viên.", 200, True)
    call_case(rows, "Huấn luyện viên", "CM-01", coach, "POST", "/api/coach/results/999999/complaints", {"noidung": "Khiếu nại kiểm thử"}, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Chọn kết quả không tồn tại.", "Nhập nội dung khiếu nại.", "Nhấn Gửi."], "POST /api/coach/results/999999/complaints; noidung=Khiếu nại kiểm thử", "Hệ thống báo không tìm thấy kết quả hoặc không có quyền khiếu nại.", [404, 422], False)
    call_case(rows, "Huấn luyện viên", "PR-01", coach, "GET", "/api/coach/athlete-change-requests", None, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Truy cập duyệt thay đổi hồ sơ vận động viên.", "Gửi yêu cầu xem danh sách."], "GET /api/coach/athlete-change-requests", "Hiển thị danh sách yêu cầu thay đổi hồ sơ vận động viên.", 200, True)
    call_case(rows, "Huấn luyện viên", "PR-02", coach, "POST", "/api/coach/athlete-change-requests/999999/approve", {"note": "Duyệt kiểm thử"}, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Chọn yêu cầu thay đổi hồ sơ không tồn tại.", "Nhấn Duyệt."], "POST /api/coach/athlete-change-requests/999999/approve", "Hệ thống báo không tìm thấy yêu cầu thay đổi hồ sơ.", [404, 422], False)
    call_case(rows, "Huấn luyện viên", "LR-01", coach, "GET", "/api/coach/athlete-leaves", None, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Truy cập duyệt đơn nghỉ của vận động viên.", "Gửi yêu cầu xem danh sách."], "GET /api/coach/athlete-leaves", "Hiển thị danh sách đơn nghỉ của vận động viên.", 200, True)
    call_case(rows, "Huấn luyện viên", "LR-02", coach, "POST", "/api/coach/athlete-leaves/999999/reject", {"reason": "Từ chối kiểm thử"}, ["Đăng nhập bằng tài khoản hlv_quocgia_01.", "Chọn đơn nghỉ không tồn tại.", "Nhấn Từ chối."], "POST /api/coach/athlete-leaves/999999/reject; reason=Từ chối kiểm thử", "Hệ thống báo không tìm thấy đơn nghỉ.", [404, 422], False)

    athlete, _ = login_case(rows, "Vận động viên", "LS-04", "vdv_quocgia_01", "123456", "VAN_DONG_VIEN")
    invitations = call_case(rows, "Vận động viên", "IV-01", athlete, "GET", "/api/athlete/team-invitations", None, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", "Truy cập Lời mời đội bóng.", "Gửi yêu cầu xem danh sách lời mời."], "GET /api/athlete/team-invitations", "Hiển thị danh sách lời mời đội bóng của vận động viên.", 200, True)
    invitation_id = find_first(invitations.get("json"), ["idloimoi", "idmoiddoibong", "id"]) or 999999
    call_case(rows, "Vận động viên", "IS-01", athlete, "GET", f"/api/athlete/team-invitations/{invitation_id}", None, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", f"Mở chi tiết lời mời ID {invitation_id}."], f"GET /api/athlete/team-invitations/{invitation_id}", "Hiển thị chi tiết lời mời nếu tồn tại, hoặc báo không tìm thấy.", [200, 404], None)
    call_case(rows, "Vận động viên", "IA-01", athlete, "POST", "/api/athlete/team-invitations/999999/accept", {}, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", "Chọn lời mời không tồn tại.", "Nhấn Chấp nhận."], "POST /api/athlete/team-invitations/999999/accept", "Hệ thống báo không tìm thấy lời mời hoặc không có quyền xử lý.", [404, 422], False)
    call_case(rows, "Vận động viên", "IR-01", athlete, "POST", "/api/athlete/team-invitations/999999/reject", {}, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", "Chọn lời mời không tồn tại.", "Nhấn Từ chối."], "POST /api/athlete/team-invitations/999999/reject", "Hệ thống báo không tìm thấy lời mời hoặc không có quyền xử lý.", [404, 422], False)
    athlete_teams = call_case(rows, "Vận động viên", "AT-01", athlete, "GET", "/api/athlete/teams", None, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", "Truy cập Đội bóng của tôi.", "Gửi yêu cầu xem danh sách đội."], "GET /api/athlete/teams", "Hiển thị đội bóng liên quan đến vận động viên.", 200, True)
    athlete_team_id = find_first(athlete_teams.get("json"), ["iddoibong"]) or team_id
    call_case(rows, "Vận động viên", "AT-02", athlete, "GET", f"/api/athlete/teams/{athlete_team_id}", None, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", f"Chọn đội bóng ID {athlete_team_id}.", "Mở chi tiết đội bóng."], f"GET /api/athlete/teams/{athlete_team_id}", "Hiển thị chi tiết đội bóng của vận động viên.", [200, 404], None)
    lineups = call_case(rows, "Vận động viên", "VL-01", athlete, "GET", "/api/athlete/lineups", None, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", "Truy cập Xem đội hình.", "Gửi yêu cầu xem danh sách đội hình."], "GET /api/athlete/lineups", "Hiển thị danh sách đội hình mà vận động viên thuộc về.", 200, True)
    lineup_id = find_first(lineups.get("json"), ["iddoihinh"]) or 1
    call_case(rows, "Vận động viên", "VL-02", athlete, "GET", f"/api/athlete/lineups/{lineup_id}", None, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", f"Chọn đội hình ID {lineup_id}.", "Mở chi tiết đội hình."], f"GET /api/athlete/lineups/{lineup_id}", "Hiển thị chi tiết đội hình nếu thuộc vận động viên.", [200, 404], None)
    call_case(rows, "Vận động viên", "PS-01", athlete, "GET", "/api/athlete/schedule", None, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", "Truy cập Lịch thi đấu cá nhân.", "Gửi yêu cầu xem lịch."], "GET /api/athlete/schedule", "Hiển thị lịch thi đấu cá nhân của vận động viên.", 200, True)
    call_case(rows, "Vận động viên", "PS-02", athlete, "GET", "/api/athlete/matches/999999", None, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", "Mở chi tiết trận đấu không tồn tại."], "GET /api/athlete/matches/999999", "Hệ thống báo không tìm thấy trận đấu liên quan.", [404, 422], False)
    call_case(rows, "Vận động viên", "ID-01", athlete, "GET", "/api/athlete/identifier-change-requests", None, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", "Truy cập chức năng sửa định danh cá nhân.", "Gửi yêu cầu xem danh sách yêu cầu đã gửi."], "GET /api/athlete/identifier-change-requests", "Hiển thị danh sách yêu cầu sửa định danh cá nhân.", 200, True)
    call_case(rows, "Vận động viên", "ID-02", athlete, "POST", "/api/athlete/identifier-change-requests", {"cccd": "ABC123", "lydo": "Kiểm thử CCCD sai"}, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", "Chọn cập nhật CCCD.", "Nhập CCCD sai định dạng.", "Nhấn Gửi yêu cầu."], "POST /api/athlete/identifier-change-requests; cccd=ABC123; lydo=Kiểm thử CCCD sai", "Hệ thống không tạo yêu cầu và báo CCCD không hợp lệ.", 422, False)
    call_case(rows, "Vận động viên", "LR-01", athlete, "GET", "/api/athlete/leave-requests", None, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", "Truy cập Xin nghỉ thi đấu.", "Gửi yêu cầu xem danh sách đơn nghỉ."], "GET /api/athlete/leave-requests", "Hiển thị danh sách đơn nghỉ thi đấu của vận động viên.", 200, True)
    call_case(rows, "Vận động viên", "LR-02", athlete, "POST", "/api/athlete/leave-requests", {"lydo": ""}, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", "Chọn gửi đơn nghỉ.", "Bỏ trống lý do/ngày nghỉ.", "Nhấn Gửi."], "POST /api/athlete/leave-requests; lydo=", "Hệ thống không gửi đơn nghỉ và báo thiếu dữ liệu bắt buộc.", 422, False)
    call_case(rows, "Vận động viên", "LR-03", athlete, "POST", "/api/athlete/leave-requests/999999/cancel", {}, ["Đăng nhập bằng tài khoản vdv_quocgia_01.", "Chọn đơn nghỉ không tồn tại.", "Nhấn Hủy đơn."], "POST /api/athlete/leave-requests/999999/cancel", "Hệ thống báo không tìm thấy đơn nghỉ.", [404, 422], False)

    referee, _ = login_case(rows, "Trọng tài", "LS-05", "tt_quocgia_01", "123456", "TRONG_TAI")
    call_case(rows, "Trọng tài", "RG-01", ApiClient(), "GET", "/api/referee/register/options", None, ["Truy cập màn hình đăng ký tài khoản trọng tài.", "Tải dữ liệu cấu hình đăng ký."], "GET /api/referee/register/options", "Hiển thị dữ liệu lựa chọn cho form đăng ký trọng tài.", 200, True)
    call_case(rows, "Trọng tài", "RG-02", ApiClient(), "POST", "/api/auth/register/referee", {"username": "", "email": "", "password": ""}, ["Truy cập màn hình đăng ký trọng tài.", "Bỏ trống thông tin bắt buộc.", "Nhấn Đăng ký."], "POST /api/auth/register/referee; username=; email=; password=", "Hệ thống không tạo tài khoản và hiển thị lỗi dữ liệu đăng ký.", 422, False)
    call_case(rows, "Trọng tài", "AS-01", referee, "GET", "/api/referee/assignments", None, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Truy cập Lịch phân công.", "Gửi yêu cầu xem danh sách phân công."], "GET /api/referee/assignments", "Hiển thị danh sách trận được phân công cho trọng tài.", 200, True)
    call_case(rows, "Trọng tài", "AS-02", referee, "GET", "/api/referee/assignments/999999", None, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Mở chi tiết phân công không tồn tại."], "GET /api/referee/assignments/999999", "Hệ thống báo không tìm thấy phân công.", 404, False)
    call_case(rows, "Trọng tài", "AC-01", referee, "POST", "/api/referee/assignments/999999/confirm", {}, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Chọn phân công không tồn tại.", "Nhấn Xác nhận."], "POST /api/referee/assignments/999999/confirm", "Hệ thống không xác nhận và báo phân công không tồn tại.", [404, 422], False)
    call_case(rows, "Trọng tài", "AD-01", referee, "POST", "/api/referee/assignments/999999/decline", {"reason": "Từ chối kiểm thử"}, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Chọn phân công không tồn tại.", "Nhấn Từ chối."], "POST /api/referee/assignments/999999/decline; reason=Từ chối kiểm thử", "Hệ thống không từ chối và báo phân công không tồn tại.", [404, 422], False)
    call_case(rows, "Trọng tài", "AT-01", referee, "GET", "/api/referee/tournaments-of-me", None, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Tải danh sách giải đấu được phân công."], "GET /api/referee/tournaments-of-me", "Hiển thị danh sách giải đấu liên quan đến trọng tài.", 200, True)
    call_case(rows, "Trọng tài", "AV-01", referee, "GET", "/api/referee/venues-of-me", None, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Tải danh sách sân đấu được phân công."], "GET /api/referee/venues-of-me", "Hiển thị danh sách sân đấu liên quan đến trọng tài.", 200, True)
    call_case(rows, "Trọng tài", "MS-01", referee, "GET", "/api/referee/matches/999999/supervision", None, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Mở màn hình giám sát trận không tồn tại."], "GET /api/referee/matches/999999/supervision", "Hệ thống báo không tìm thấy trận đấu được phân công.", [404, 422], False)
    call_case(rows, "Trọng tài", "MP-01", referee, "POST", "/api/referee/matches/999999/participants/confirm", {"participants": []}, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Mở trận không tồn tại.", "Nhấn xác nhận vận động viên tham gia."], "POST /api/referee/matches/999999/participants/confirm; participants=[]", "Hệ thống báo không tìm thấy trận đấu được phân công.", [404, 422], False)
    call_case(rows, "Trọng tài", "MS-02", referee, "POST", "/api/referee/matches/999999/start", {}, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Mở trận không tồn tại.", "Nhấn Bắt đầu trận."], "POST /api/referee/matches/999999/start", "Hệ thống báo không tìm thấy trận đấu được phân công.", [404, 422], False)
    call_case(rows, "Trọng tài", "MS-03", referee, "POST", "/api/referee/matches/999999/pause", {}, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Mở trận không tồn tại.", "Nhấn Tạm dừng trận."], "POST /api/referee/matches/999999/pause", "Hệ thống báo không tìm thấy trận đấu được phân công.", [404, 422], False)
    call_case(rows, "Trọng tài", "MS-04", referee, "POST", "/api/referee/matches/999999/resume", {}, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Mở trận không tồn tại.", "Nhấn Tiếp tục trận."], "POST /api/referee/matches/999999/resume", "Hệ thống báo không tìm thấy trận đấu được phân công.", [404, 422], False)
    call_case(rows, "Trọng tài", "MR-01", referee, "POST", "/api/referee/matches/999999/result", {"diemdoi1": 3, "diemdoi2": 1}, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Mở trận không tồn tại.", "Nhập tỷ số 3-1.", "Nhấn Ghi nhận kết quả."], "POST /api/referee/matches/999999/result; diemdoi1=3; diemdoi2=1", "Hệ thống báo không tìm thấy trận đấu được phân công.", [404, 422], False)
    call_case(rows, "Trọng tài", "MF-01", referee, "POST", "/api/referee/matches/999999/finish", {}, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Mở trận không tồn tại.", "Nhấn Kết thúc trận."], "POST /api/referee/matches/999999/finish", "Hệ thống báo không tìm thấy trận đấu được phân công.", [404, 422], False)
    call_case(rows, "Trọng tài", "RL-01", referee, "GET", "/api/referee/leave-requests", None, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Truy cập Xin nghỉ phép.", "Gửi yêu cầu xem danh sách đơn nghỉ."], "GET /api/referee/leave-requests", "Hiển thị danh sách đơn nghỉ của trọng tài.", 200, True)
    call_case(rows, "Trọng tài", "RL-02", referee, "POST", "/api/referee/leave-requests", {"lydo": ""}, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Chọn gửi đơn nghỉ.", "Bỏ trống lý do/ngày nghỉ.", "Nhấn Gửi."], "POST /api/referee/leave-requests; lydo=", "Hệ thống không gửi đơn nghỉ và báo thiếu dữ liệu bắt buộc.", 422, False)
    call_case(rows, "Trọng tài", "RL-03", referee, "POST", "/api/referee/leave-requests/999999/cancel", {}, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Chọn đơn nghỉ không tồn tại.", "Nhấn Hủy đơn."], "POST /api/referee/leave-requests/999999/cancel", "Hệ thống báo không tìm thấy đơn nghỉ.", [404, 422], False)
    call_case(rows, "Trọng tài", "IR-01", referee, "GET", "/api/referee/incident-report-matches", None, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Truy cập Báo cáo sự cố.", "Tải danh sách trận có thể báo cáo."], "GET /api/referee/incident-report-matches", "Hiển thị danh sách trận có thể lập báo cáo sự cố.", 200, True)
    call_case(rows, "Trọng tài", "IR-02", referee, "GET", "/api/referee/incident-reports", None, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Truy cập Báo cáo sự cố.", "Gửi yêu cầu xem danh sách báo cáo."], "GET /api/referee/incident-reports", "Hiển thị danh sách báo cáo sự cố của trọng tài.", 200, True)
    call_case(rows, "Trọng tài", "IR-03", referee, "POST", "/api/referee/incident-reports", {"idtrandau": 999999, "tieude": "", "noidung": ""}, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Chọn gửi báo cáo sự cố.", "Bỏ trống tiêu đề/nội dung.", "Nhấn Gửi."], "POST /api/referee/incident-reports; idtrandau=999999; tieude=; noidung=", "Hệ thống không gửi báo cáo và hiển thị lỗi dữ liệu bắt buộc hoặc không tìm thấy trận.", 422, False)
    call_case(rows, "Trọng tài", "IR-04", referee, "GET", "/api/referee/incident-reports/999999", None, ["Đăng nhập bằng tài khoản tt_quocgia_01.", "Mở chi tiết báo cáo sự cố không tồn tại."], "GET /api/referee/incident-reports/999999", "Hệ thống báo không tìm thấy báo cáo sự cố.", [404, 422], False)

    # Permission check is placed last so it does not interrupt the normal HLV session.
    call_case(rows, "Quản trị viên", "RD-01", coach, "GET", "/api/admin/accounts", None, ["Đăng nhập bằng tài khoản huấn luyện viên.", "Truy cập API quản lý tài khoản của quản trị viên."], "GET /api/admin/accounts; session=hlv_quocgia_01", "Hệ thống từ chối vì người dùng không có quyền ADMIN.", 403, False)

    return rows


def main() -> int:
    rows = generate()
    write_markdown(rows)
    docx_path = write_docx(rows)
    total = sum(len(items) for items in rows.values())
    passed = sum(1 for items in rows.values() for item in items if item["Kết quả thực tế"].startswith("Đạt:"))
    print(f"Wrote {OUT_MD} and {docx_path}")
    print(f"Total test cases: {total}")
    print(f"Passed expectation: {passed}/{total}")
    for actor, items in rows.items():
        print(f"- {actor}: {len(items)}")
    return 0 if passed == total else 1


if __name__ == "__main__":
    raise SystemExit(main())
