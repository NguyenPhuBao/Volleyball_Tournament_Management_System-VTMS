from __future__ import annotations

import html
import os
import shutil
import struct
from datetime import datetime
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile


OUT_MAIN = Path("Hướng dẫn sử dụng CNM.docx")
OUT_COPY = Path("Hướng dẫn sử dụng CNM - VTMS cập nhật.docx")
BACKUP = Path("Hướng dẫn sử dụng CNM.backup.docx")
SCREEN_DIR = Path("runtime/vtms-guide-screens")

NS_W = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
NS_R = "http://schemas.openxmlformats.org/officeDocument/2006/relationships"
NS_WP = "http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
NS_A = "http://schemas.openxmlformats.org/drawingml/2006/main"
NS_PIC = "http://schemas.openxmlformats.org/drawingml/2006/picture"


def esc(value: object) -> str:
    return html.escape(str(value), quote=True)


def png_size(path: Path) -> tuple[int, int]:
    data = path.read_bytes()
    if data[:8] != b"\x89PNG\r\n\x1a\n":
        raise ValueError(f"{path} is not a PNG")
    return struct.unpack(">II", data[16:24])


class Docx:
    def __init__(self) -> None:
        self.body: list[str] = []
        self.rels: list[tuple[str, str, str]] = []
        self.media: list[tuple[str, bytes]] = []
        self.next_rid = 1
        self.next_docpr = 1

    def run(self, text: str, *, bold: bool = False, italic: bool = False, size: int = 22, color: str | None = None) -> str:
        props = [
            '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>',
            f'<w:sz w:val="{size}"/><w:szCs w:val="{size}"/>',
        ]
        if bold:
            props.append("<w:b/><w:bCs/>")
        if italic:
            props.append("<w:i/><w:iCs/>")
        if color:
            props.append(f'<w:color w:val="{color}"/>')
        parts = []
        for idx, line in enumerate(str(text).split("\n")):
            if idx:
                parts.append("<w:r><w:br/></w:r>")
            parts.append(
                "<w:r><w:rPr>"
                + "".join(props)
                + f'</w:rPr><w:t xml:space="preserve">{esc(line)}</w:t></w:r>'
            )
        return "".join(parts)

    def p(self, text: str = "", *, bold: bool = False, italic: bool = False, size: int = 22, style: str | None = None, color: str | None = None, align: str | None = None) -> None:
        ppr = []
        if style:
            ppr.append(f'<w:pStyle w:val="{style}"/>')
        if align:
            ppr.append(f'<w:jc w:val="{align}"/>')
        ppr_xml = f"<w:pPr>{''.join(ppr)}</w:pPr>" if ppr else ""
        self.body.append(f"<w:p>{ppr_xml}{self.run(text, bold=bold, italic=italic, size=size, color=color)}</w:p>")

    def heading(self, text: str, level: int = 1) -> None:
        size = {1: 30, 2: 26, 3: 24}.get(level, 22)
        style = f"Heading{min(level, 3)}"
        self.p(text, bold=True, size=size, style=style, color="1F4E79")

    def bullet(self, items: list[str]) -> None:
        for item in items:
            self.p("• " + item, size=22)

    def code(self, text: str) -> None:
        self.body.append(
            '<w:p><w:pPr><w:shd w:fill="F3F6FA"/><w:spacing w:before="80" w:after="80"/></w:pPr>'
            + self.run(text, size=20)
            + "</w:p>"
        )

    def table(self, headers: list[str], rows: list[list[str]], widths: list[int] | None = None) -> None:
        if widths is None:
            widths = [1800] * len(headers)
        borders = (
            "<w:tblBorders>"
            '<w:top w:val="single" w:sz="6" w:color="9AA7B2"/>'
            '<w:left w:val="single" w:sz="6" w:color="9AA7B2"/>'
            '<w:bottom w:val="single" w:sz="6" w:color="9AA7B2"/>'
            '<w:right w:val="single" w:sz="6" w:color="9AA7B2"/>'
            '<w:insideH w:val="single" w:sz="6" w:color="D0D7DE"/>'
            '<w:insideV w:val="single" w:sz="6" w:color="D0D7DE"/>'
            "</w:tblBorders>"
        )
        xml = [
            "<w:tbl>",
            f'<w:tblPr><w:tblW w:w="0" w:type="auto"/>{borders}</w:tblPr>',
            "<w:tblGrid>" + "".join(f'<w:gridCol w:w="{w}"/>' for w in widths) + "</w:tblGrid>",
        ]
        xml.append("<w:tr>" + "".join(self.cell(h, w, bold=True, shade="D9EAF7") for h, w in zip(headers, widths)) + "</w:tr>")
        for row in rows:
            xml.append("<w:tr>" + "".join(self.cell(c, w) for c, w in zip(row, widths)) + "</w:tr>")
        xml.append("</w:tbl>")
        self.body.append("".join(xml))

    def cell(self, text: str, width: int, *, bold: bool = False, shade: str | None = None) -> str:
        shd = f'<w:shd w:fill="{shade}"/>' if shade else ""
        return (
            "<w:tc>"
            f'<w:tcPr><w:tcW w:w="{width}" w:type="dxa"/>{shd}<w:vAlign w:val="top"/></w:tcPr>'
            f"<w:p>{self.run(text, bold=bold, size=20)}</w:p>"
            "</w:tc>"
        )

    def image(self, path: Path, caption: str, width_in: float = 6.3) -> None:
        rid = f"rId{self.next_rid}"
        self.next_rid += 1
        media_name = f"image{len(self.media) + 1}.png"
        self.rels.append((rid, "http://schemas.openxmlformats.org/officeDocument/2006/relationships/image", f"media/{media_name}"))
        self.media.append((media_name, path.read_bytes()))

        width_px, height_px = png_size(path)
        width_emu = int(width_in * 914400)
        height_emu = int(width_emu * height_px / width_px)
        docpr = self.next_docpr
        self.next_docpr += 1

        drawing = f"""
        <w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:drawing>
        <wp:inline distT="0" distB="0" distL="0" distR="0" xmlns:wp="{NS_WP}">
        <wp:extent cx="{width_emu}" cy="{height_emu}"/>
        <wp:effectExtent l="0" t="0" r="0" b="0"/>
        <wp:docPr id="{docpr}" name="{esc(caption)}"/>
        <wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="{NS_A}" noChangeAspect="1"/></wp:cNvGraphicFramePr>
        <a:graphic xmlns:a="{NS_A}"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
        <pic:pic xmlns:pic="{NS_PIC}">
        <pic:nvPicPr><pic:cNvPr id="{docpr}" name="{esc(path.name)}"/><pic:cNvPicPr/></pic:nvPicPr>
        <pic:blipFill><a:blip xmlns:r="{NS_R}" r:embed="{rid}"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>
        <pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="{width_emu}" cy="{height_emu}"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>
        </pic:pic></a:graphicData></a:graphic>
        </wp:inline></w:drawing></w:r></w:p>
        """
        self.body.append(drawing)
        self.p(caption, italic=True, size=20, align="center")

    def page_break(self) -> None:
        self.body.append('<w:p><w:r><w:br w:type="page"/></w:r></w:p>')

    def document_xml(self) -> str:
        sect = (
            "<w:sectPr>"
            '<w:pgSz w:w="11906" w:h="16838"/>'
            '<w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="360" w:footer="360" w:gutter="0"/>'
            "</w:sectPr>"
        )
        return (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            f'<w:document xmlns:w="{NS_W}" xmlns:r="{NS_R}"><w:body>'
            + "".join(self.body)
            + sect
            + "</w:body></w:document>"
        )

    def styles_xml(self) -> str:
        return (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            f'<w:styles xmlns:w="{NS_W}">'
            '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="22"/></w:rPr></w:style>'
            '<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:rPr><w:b/><w:sz w:val="36"/></w:rPr></w:style>'
            '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:rPr><w:b/><w:sz w:val="30"/></w:rPr></w:style>'
            '<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:rPr><w:b/><w:sz w:val="26"/></w:rPr></w:style>'
            '<w:style w:type="paragraph" w:styleId="Heading3"><w:name w:val="heading 3"/><w:basedOn w:val="Normal"/><w:rPr><w:b/><w:sz w:val="24"/></w:rPr></w:style>'
            "</w:styles>"
        )

    def write(self, path: Path) -> None:
        rels_xml = (
            '<?xml version="1.0" encoding="UTF-8"?>'
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            '<Relationship Id="rId0" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            + "".join(f'<Relationship Id="{rid}" Type="{typ}" Target="{target}"/>' for rid, typ, target in self.rels)
            + "</Relationships>"
        )
        root_rels = (
            '<?xml version="1.0" encoding="UTF-8"?>'
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            "</Relationships>"
        )
        content_types = (
            '<?xml version="1.0" encoding="UTF-8"?>'
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            '<Default Extension="xml" ContentType="application/xml"/>'
            '<Default Extension="png" ContentType="image/png"/>'
            '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            "</Types>"
        )
        with ZipFile(path, "w", ZIP_DEFLATED) as zf:
            zf.writestr("[Content_Types].xml", content_types)
            zf.writestr("_rels/.rels", root_rels)
            zf.writestr("word/document.xml", self.document_xml())
            zf.writestr("word/styles.xml", self.styles_xml())
            zf.writestr("word/_rels/document.xml.rels", rels_xml)
            for name, data in self.media:
                zf.writestr(f"word/media/{name}", data)


def build() -> Docx:
    doc = Docx()
    today = datetime.now().strftime("%d/%m/%Y %H:%M")

    doc.p("VTMS - Tài liệu hướng dẫn sử dụng công nghệ mới", bold=True, size=34, style="Title", align="center")
    doc.p("Volleyball Tournament Management System", bold=True, size=24, align="center")
    doc.p(f"Phiên bản cập nhật theo dự án hiện tại: {today}", italic=True, size=20, align="center")
    doc.p("Tài liệu này thay thế các nội dung mô tả chung trước đó bằng hướng dẫn bám sát route, giao diện, tài khoản mẫu và luồng nghiệp vụ đang có trong source VTMS.", size=22)

    doc.heading("1. Mục đích và phạm vi", 1)
    doc.bullet([
        "Hướng dẫn cài đặt, chạy thử và demo hệ thống VTMS trên môi trường local.",
        "Mô tả công nghệ mới được áp dụng trong dự án: PHP MVC tự xây dựng, Router, Middleware, Service Layer, Fetch API, MySQL/PDO, Cloudflared.",
        "Hướng dẫn thao tác theo vai trò: Quản trị viên, Ban tổ chức, Huấn luyện viên, Vận động viên, Trọng tài và Khán giả.",
        "Bổ sung ảnh chụp từ dự án VTMS đang chạy tại http://localhost:8000.",
    ])

    doc.heading("2. Tổng quan hệ thống VTMS", 1)
    doc.p("VTMS là hệ thống quản lý giải đấu bóng chuyền, hỗ trợ tạo và công bố giải đấu, mở/đóng đăng ký, duyệt đội tham gia, quản lý sân đấu, lập lịch, phân công trọng tài, giám sát trận đấu, ghi nhận kết quả, xử lý khiếu nại và công bố bảng xếp hạng.")
    doc.table(
        ["Vai trò", "Phạm vi thao tác chính"],
        [
            ["Quản trị viên", "Quản lý tài khoản, hồ sơ người dùng, nhật ký hệ thống, xác nhận thay đổi thông tin ban tổ chức."],
            ["Ban tổ chức", "Quản lý giải đấu, đội bóng, suất đại diện, sân đấu, trọng tài, tài khoản HLV/trọng tài, lịch thi đấu, kết quả, khiếu nại, bảng xếp hạng."],
            ["Huấn luyện viên", "Quản lý đội bóng, thành viên, vận động viên, đội hình, đăng ký giải đấu, xem lịch đội, gửi khiếu nại kết quả, duyệt yêu cầu VĐV."],
            ["Vận động viên", "Xem thông báo, lời mời đội bóng, đội bóng của tôi, đội hình, lịch thi đấu cá nhân, hồ sơ, xin nghỉ phép thi đấu."],
            ["Trọng tài", "Xem lịch phân công, xác nhận/từ chối phân công, giám sát trận đấu, báo cáo sự cố, xin nghỉ phép."],
            ["Khán giả", "Xem đội bóng, lịch thi đấu, kết quả và bảng xếp hạng công khai."],
        ],
        [2200, 7000],
    )

    doc.heading("3. Công nghệ mới và cách áp dụng trong dự án", 1)
    doc.table(
        ["Công nghệ", "Cách dùng trong VTMS hiện tại"],
        [
            ["PHP 8 + Front Controller", "Tất cả request đi qua public/index.php; khi chạy thử dùng public/server.php để định tuyến đúng asset và route động."],
            ["MVC tự xây dựng", "Controller nằm trong app/backend/controllers, View nằm trong app/frontend/views, Model nằm trong app/backend/models."],
            ["Router tự xây dựng", "Route tập trung tại app/backend/config/cauhinh-duongdan.php, có hỗ trợ route động như /api/organizer/tournaments/{id}."],
            ["Middleware", "Kiểm tra đăng nhập và role trước khi vào controller, ví dụ role:ADMIN, role:BAN_TO_CHUC, role:HUAN_LUYEN_VIEN."],
            ["Service Layer", "Nghiệp vụ phức tạp nằm trong app/backend/services, ví dụ tạo giải, duyệt đăng ký, lập lịch, ghi kết quả."],
            ["MySQL + PDO", "Kết nối CSDL qua app/backend/config/cauhinh-csdl.php, thông tin môi trường lấy từ .env."],
            ["Fetch API", "Frontend trong public/assets/js gọi API JSON, ví dụ quantri-taikhoan.js, bantochuc-giaidau.js, trongtai-giamsat.js."],
            ["Cloudflared", "Dùng để demo local ra URL public khi cần trình bày, không phải môi trường production chính thức."],
        ],
        [2200, 7000],
    )

    doc.heading("4. Cấu trúc thư mục đúng với source hiện tại", 1)
    doc.table(
        ["Thư mục/file", "Vai trò"],
        [
            ["public/index.php", "Điểm vào chính của ứng dụng khi chạy web."],
            ["public/server.php", "Router hỗ trợ PHP built-in server, giúp CSS/JS và route động hoạt động đúng."],
            ["app/backend/config/cauhinh-duongdan.php", "Khai báo route trang và route API."],
            ["app/backend/controllers", "Controller theo nhóm Admin, Organizer, Coach, Athlete, Referee, Spectator, Shared."],
            ["app/backend/services", "Xử lý nghiệp vụ theo từng actor và module."],
            ["app/backend/models", "Truy vấn CSDL bằng PDO."],
            ["app/frontend/views", "Giao diện PHP theo từng vai trò."],
            ["public/assets/js", "JavaScript module theo màn hình."],
            ["public/assets/css", "CSS giao diện."],
            ["vtms5.sql", "Script CSDL và dữ liệu mẫu."],
            [".env", "Cấu hình APP_URL và thông tin MySQL."],
        ],
        [3100, 6100],
    )

    doc.heading("5. Cài đặt và chạy thử hệ thống", 1)
    doc.heading("5.1. Chuẩn bị môi trường", 2)
    doc.bullet([
        "Cài XAMPP hoặc PHP CLI. Dự án hiện đang chạy thử bằng C:\\xampp\\php\\php.exe.",
        "Cài MySQL và dùng MySQL Workbench để tạo database/import file vtms5.sql.",
        "Đảm bảo database trong .env là DB_DATABASE=vtms, DB_USERNAME=root, DB_PASSWORD=123456 hoặc chỉnh lại theo máy đang dùng.",
    ])
    doc.heading("5.2. Import database bằng MySQL Workbench", 2)
    doc.bullet([
        "Mở MySQL Workbench và kết nối tới MySQL local.",
        "Tạo schema tên vtms nếu chưa có.",
        "Vào Server > Data Import hoặc mở file vtms5.sql bằng SQL Editor.",
        "Chạy toàn bộ script để tạo bảng và dữ liệu mẫu.",
        "Kiểm tra một số bảng chính như Taikhoan, Nguoidung, Giaidau, Doibong, Vandongvien, Trongtai.",
    ])
    doc.heading("5.3. Chạy server local", 2)
    doc.code(r"C:\xampp\php\php.exe -S localhost:8000 -t public public/server.php")
    doc.p("Lưu ý: cần chạy đúng lệnh có public/server.php. Nếu chỉ chạy -t public mà thiếu router server.php, một số route động hoặc asset CSS/JS có thể không được xử lý đúng.")
    doc.heading("5.4. Demo qua Cloudflared", 2)
    doc.code(r"cloudflared tunnel --url http://localhost:8000")
    doc.p("Sau khi Cloudflared sinh URL public, cập nhật APP_URL trong .env nếu cần demo link tuyệt đối. Với chạy thử local thông thường, giữ APP_URL=http://localhost:8000.")

    doc.heading("6. Tài khoản mẫu dùng khi demo", 1)
    doc.table(
        ["Vai trò", "Username", "Mật khẩu", "Ghi chú"],
        [
            ["Quản trị viên", "admin_test", "123456", "Quản lý tài khoản, người dùng, nhật ký."],
            ["Ban tổ chức", "btc_quocgia", "123456", "Quản lý giải cấp quốc gia."],
            ["Huấn luyện viên", "hlv_quocgia_01", "123456", "Quản lý đội và đăng ký giải."],
            ["Vận động viên", "vdv_quocgia_01", "123456", "Xem lời mời, đội hình, lịch cá nhân."],
            ["Trọng tài", "tt_quocgia_01", "123456", "Xem phân công, giám sát, báo cáo sự cố."],
        ],
        [2100, 2500, 1600, 3000],
    )

    doc.heading("7. Hướng dẫn sử dụng theo vai trò", 1)
    doc.heading("7.1. Truy cập và đăng nhập", 2)
    doc.bullet([
        "Mở trình duyệt và truy cập http://localhost:8000.",
        "Chọn Đăng nhập.",
        "Nhập username hoặc email và mật khẩu.",
        "Sau khi đăng nhập, hệ thống chuyển đến dashboard tương ứng với role.",
    ])
    doc.image(SCREEN_DIR / "01-trang-chu.png", "Hình 1. Trang chủ công khai của VTMS")
    doc.image(SCREEN_DIR / "02-dang-nhap.png", "Hình 2. Màn hình đăng nhập VTMS")

    doc.heading("7.2. Quản trị viên", 2)
    doc.p("Quản trị viên quản lý phần nền tảng của hệ thống: tài khoản, hồ sơ người dùng, nhật ký và yêu cầu thay đổi thông tin của ban tổ chức.")
    doc.bullet([
        "Vào Quản lý tài khoản để thêm, sửa, khóa/mở khóa hoặc xóa tài khoản.",
        "Vào Hồ sơ người dùng để xem chi tiết thông tin cá nhân gắn với tài khoản.",
        "Vào Nhật ký hệ thống để kiểm tra thao tác đã phát sinh.",
        "Vào Xác nhận thông tin BTC để duyệt/từ chối yêu cầu cập nhật thông tin ban tổ chức.",
    ])
    doc.image(SCREEN_DIR / "03-admin-quan-ly-tai-khoan.png", "Hình 3. Quản trị viên - Quản lý tài khoản")
    doc.image(SCREEN_DIR / "04-admin-nhat-ky.png", "Hình 4. Quản trị viên - Nhật ký hệ thống")

    doc.heading("7.3. Ban tổ chức", 2)
    doc.p("Ban tổ chức là actor trung tâm của nghiệp vụ giải đấu. Màn hình hiện tại có các module: Giải đấu, Lịch thi đấu, Đội bóng, Suất đại diện, Sân đấu, Trọng tài, Tài khoản HLV, Tài khoản trọng tài, Huấn luyện viên, Vận động viên, Khiếu nại, Kết quả và Xếp hạng.")
    doc.bullet([
        "Tạo giải đấu: nhập cấp giải, khu vực, luật, thời gian, quy mô và cấu hình điều kiện tham gia.",
        "Công bố giải: sau khi tạo hợp lệ, chọn Công bố để chuyển giải sang trạng thái công khai.",
        "Mở/đóng đăng ký: dùng nút Mở ĐK hoặc Đóng ĐK trên danh sách giải.",
        "Duyệt đội: vào danh sách đăng ký của giải để duyệt hoặc từ chối đội tham gia.",
        "Lập lịch: vào Lịch thi đấu để tạo bảng đấu/trận đấu hoặc sinh lịch chuẩn khi đủ điều kiện.",
        "Kết quả/xếp hạng: theo dõi kết quả trọng tài ghi nhận, điều chỉnh khi cần và công bố bảng xếp hạng.",
    ])
    doc.image(SCREEN_DIR / "05-btc-giai-dau.png", "Hình 5. Ban tổ chức - Quản lý giải đấu")
    doc.image(SCREEN_DIR / "06-btc-lich-thi-dau.png", "Hình 6. Ban tổ chức - Lịch thi đấu")

    doc.heading("7.4. Huấn luyện viên", 2)
    doc.bullet([
        "Vào Đội bóng của tôi để tạo mới hoặc chỉnh sửa thông tin đội.",
        "Vào Tài khoản VĐV để tạo tài khoản vận động viên thuộc đội.",
        "Vào Thành viên đội để thêm, chuyển hoặc loại thành viên.",
        "Vào Đội hình để lập danh sách thi đấu.",
        "Vào Đăng ký giải đấu để gửi đăng ký tham gia giải đang mở.",
        "Vào Kết quả thi đấu để xem kết quả và gửi khiếu nại khi cần.",
    ])
    doc.image(SCREEN_DIR / "07-hlv-ho-so-doi.png", "Hình 7. Huấn luyện viên - Đội bóng của tôi")

    doc.heading("7.5. Vận động viên", 2)
    doc.bullet([
        "Vào Lời mời đội bóng để chấp nhận hoặc từ chối lời mời tham gia đội.",
        "Vào Đội bóng của tôi để xem thông tin đội hiện tại.",
        "Vào Đội hình để xem danh sách thi đấu.",
        "Vào Lịch thi đấu để xem lịch cá nhân.",
        "Vào Hồ sơ để xem/cập nhật yêu cầu thay đổi thông tin.",
        "Vào Nghỉ phép thi đấu để gửi đơn nghỉ khi không thể tham gia trận.",
    ])
    doc.image(SCREEN_DIR / "08-vdv-loi-moi.png", "Hình 8. Vận động viên - Lời mời đội bóng")

    doc.heading("7.6. Trọng tài", 2)
    doc.bullet([
        "Vào Lịch phân công để xem trận được giao và xác nhận/từ chối phân công.",
        "Vào Giám sát để xác nhận thành phần tham gia, bắt đầu/tạm dừng/tiếp tục/kết thúc trận và ghi kết quả.",
        "Vào Báo cáo sự cố để lập báo cáo liên quan đến trận được phân công.",
        "Vào Xin nghỉ phép để gửi đơn nghỉ khi không thể nhận phân công.",
    ])
    doc.image(SCREEN_DIR / "09-trong-tai-phan-cong.png", "Hình 9. Trọng tài - Lịch phân công")

    doc.heading("7.7. Khán giả", 2)
    doc.p("Khán giả không cần đăng nhập. Các route công khai đang có: /khan-gia/doi-bong, /khan-gia/lich-thi-dau, /khan-gia/ket-qua, /khan-gia/bang-xep-hang và các route /api/public tương ứng.")

    doc.heading("8. Quy trình nghiệp vụ chính", 1)
    doc.p("Phần này là trọng tâm của tài liệu hướng dẫn sử dụng. Các bước được viết theo đúng thao tác trên giao diện hiện tại của VTMS, dùng để demo hoặc hướng dẫn người dùng chạy thử trên localhost.")
    doc.table(
        ["Quy trình", "Các bước chính"],
        [
            ["Tạo giải - công bố - duyệt đăng ký", "BTC tạo giải đấu -> công bố giải -> mở đăng ký -> HLV gửi đăng ký -> BTC duyệt/từ chối -> đóng đăng ký."],
            ["Tạo trận đấu", "BTC chọn giải -> tạo bảng/vòng đấu nếu cần -> tạo trận hoặc sinh lịch chuẩn -> gán sân, thời gian và đội."],
            ["Trọng tài giám sát trận đấu", "Trọng tài xem phân công -> xác nhận -> vào giám sát -> xác nhận VĐV -> ghi kết quả -> kết thúc trận -> BTC kiểm tra/công bố."],
            ["Đề cử đội - duyệt đội", "BTC/HLV theo dõi tư cách đội -> đề cử lên cấp cao hơn -> BTC cấp trên duyệt/từ chối -> đội đủ điều kiện đăng ký giải cấp trên."],
        ],
        [2500, 6700],
    )

    doc.heading("8.1. Quy trình tạo giải đấu, công bố, mở đăng ký và duyệt đội", 2)
    doc.table(
        ["Thành phần", "Nội dung"],
        [
            ["Actor chính", "Ban tổ chức."],
            ["Actor liên quan", "Huấn luyện viên, đội bóng, vận động viên trong đội."],
            ["Điều kiện trước", "Ban tổ chức đã đăng nhập; database có dữ liệu luật thi đấu, khu vực và tài khoản mẫu."],
            ["Kết quả sau", "Giải đấu được tạo, công bố, mở đăng ký; đội gửi hồ sơ được BTC duyệt hoặc từ chối."],
            ["Màn hình sử dụng", "/ban-to-chuc/giai-dau, /huan-luyen-vien/giai-dau, /ban-to-chuc/doi-bong."],
        ],
        [2400, 6800],
    )
    doc.table(
        ["Bước", "Thao tác trên hệ thống", "Kết quả cần thấy"],
        [
            ["1", "Đăng nhập bằng tài khoản Ban tổ chức, ví dụ btc_quocgia / 123456.", "Hệ thống chuyển vào dashboard của Ban tổ chức."],
            ["2", "Chọn menu Giải đấu ở thanh bên trái.", "Danh sách giải đấu hiển thị, có các nút Tạo giải đấu, Công bố, Mở ĐK, Đăng ký, Sửa, Hủy tùy trạng thái giải."],
            ["3", "Bấm Tạo giải đấu.", "Form tạo giải đấu mở ra dạng popup."],
            ["4", "Nhập các thông tin bắt buộc: tên giải, cấp giải, khu vực phạm vi, thời gian bắt đầu/kết thúc, luật thi đấu, giới tính, tính chất giải, quy mô, địa điểm, điều lệ, phí và điều kiện thành tích nếu có.", "Dữ liệu được nhập hợp lệ, các combobox hiển thị đúng danh mục."],
            ["5", "Bấm Lưu để tạo giải.", "Giải mới xuất hiện trong danh sách ở trạng thái phù hợp để tiếp tục công bố."],
            ["6", "Bấm Công bố & mở ĐK hoặc công bố trước rồi bấm Mở ĐK.", "Giải chuyển sang trạng thái công khai và cho phép HLV gửi hồ sơ đăng ký."],
            ["7", "Huấn luyện viên đăng nhập, vào menu Đăng ký giải đấu.", "Danh sách các giải đang mở đăng ký được hiển thị để HLV chọn đội/đội hình gửi hồ sơ."],
            ["8", "Ban tổ chức quay lại Giải đấu, bấm Đăng ký tại giải cần xử lý.", "Popup quản lý đăng ký hiển thị danh sách hồ sơ, bộ lọc trạng thái và ô tìm kiếm."],
            ["9", "BTC kiểm tra thông tin đội, sau đó duyệt hoặc nhập lý do từ chối.", "Hồ sơ chuyển sang trạng thái đã duyệt hoặc từ chối; đội đã duyệt được dùng để lập lịch/trận."],
        ],
        [900, 5200, 3100],
    )
    doc.image(SCREEN_DIR / "10-btc-tao-giai-modal.png", "Hình 10. Ban tổ chức mở form tạo giải đấu")
    doc.image(SCREEN_DIR / "11-btc-duyet-dang-ky-modal.png", "Hình 11. Ban tổ chức mở danh sách đăng ký của giải đấu")
    doc.image(SCREEN_DIR / "17-hlv-dang-ky-giai.png", "Hình 12. Huấn luyện viên vào màn hình đăng ký giải đấu")
    doc.image(SCREEN_DIR / "12-btc-duyet-doi-bong.png", "Hình 13. Ban tổ chức theo dõi đội bóng và hồ sơ đội")

    doc.heading("8.2. Quy trình tạo trận đấu và lập lịch thi đấu", 2)
    doc.table(
        ["Thành phần", "Nội dung"],
        [
            ["Actor chính", "Ban tổ chức."],
            ["Điều kiện trước", "Giải đấu đã có đội được duyệt; có sân đấu và cấu hình vòng/bảng phù hợp."],
            ["Kết quả sau", "Trận đấu được tạo với đội 1, đội 2, vòng đấu, sân, thời gian, trạng thái và trọng tài được phân công."],
            ["Màn hình sử dụng", "/ban-to-chuc/lich-thi-dau."],
        ],
        [2400, 6800],
    )
    doc.table(
        ["Bước", "Thao tác trên hệ thống", "Kết quả cần thấy"],
        [
            ["1", "Đăng nhập Ban tổ chức và chọn menu Lịch thi đấu.", "Màn hình quản lý lịch thi đấu hiển thị bộ lọc giải, trạng thái và danh sách trận."],
            ["2", "Chọn giải đấu cần lập lịch.", "Dữ liệu bảng đấu, vòng đấu, đội tham gia và trận của giải được tải."],
            ["3", "Nếu giải cần chia bảng, bấm Thêm bảng đấu.", "Popup thêm bảng đấu hiển thị các trường tên bảng, vòng đấu, trạng thái, thời gian và mô tả."],
            ["4", "Nhập thông tin bảng đấu rồi bấm Lưu.", "Bảng đấu mới xuất hiện trong danh sách để gán trận."],
            ["5", "Bấm Thêm trận đấu.", "Popup thêm trận đấu hiển thị lựa chọn bảng, vòng, đội 1, đội 2, sân, trạng thái và thời gian."],
            ["6", "Chọn đội cụ thể hoặc chọn nguồn đội từ trận/vòng trước nếu là vòng loại trực tiếp.", "Nguồn đội của trận được xác định đúng theo thể thức."],
            ["7", "Chọn sân đấu, thời gian bắt đầu/kết thúc và trạng thái Đã xếp lịch.", "Trận đủ thông tin để lưu."],
            ["8", "Bấm + Thêm trọng tài để phân công trọng tài chính, trọng tài phụ hoặc giám sát.", "Danh sách trọng tài được gắn vào trận; hệ thống cảnh báo nếu trận chưa đủ trọng tài cần thiết."],
            ["9", "Bấm Lưu.", "Trận đấu xuất hiện trong lịch thi đấu và có thể được trọng tài nhìn thấy trong lịch phân công."],
        ],
        [900, 5200, 3100],
    )
    doc.image(SCREEN_DIR / "06-btc-lich-thi-dau.png", "Hình 14. Ban tổ chức - màn hình lịch thi đấu")
    doc.image(SCREEN_DIR / "14-btc-tao-bang-dau-modal.png", "Hình 15. Ban tổ chức thêm bảng đấu")
    doc.image(SCREEN_DIR / "15-btc-tao-tran-dau-modal.png", "Hình 16. Ban tổ chức thêm trận đấu")
    doc.image(SCREEN_DIR / "16-btc-phan-cong-trong-tai-modal.png", "Hình 17. Khu vực phân công trọng tài trong form trận đấu")

    doc.heading("8.3. Quy trình trọng tài giám sát trận đấu", 2)
    doc.table(
        ["Thành phần", "Nội dung"],
        [
            ["Actor chính", "Trọng tài."],
            ["Actor liên quan", "Ban tổ chức, đội bóng, huấn luyện viên."],
            ["Điều kiện trước", "Trọng tài đã được phân công vào trận đấu; trận có lịch thi đấu hợp lệ."],
            ["Kết quả sau", "Trận được xác nhận, bắt đầu, ghi kết quả, kết thúc; sự cố nếu có được lập báo cáo."],
            ["Màn hình sử dụng", "/trong-tai/lich-phan-cong, /trong-tai/giam-sat, /trong-tai/bao-cao-su-co."],
        ],
        [2400, 6800],
    )
    doc.table(
        ["Bước", "Thao tác trên hệ thống", "Kết quả cần thấy"],
        [
            ["1", "Đăng nhập bằng tài khoản trọng tài, ví dụ tt_quocgia_01 / 123456.", "Hệ thống chuyển vào dashboard của trọng tài."],
            ["2", "Vào menu Lịch phân công.", "Danh sách trận được phân công hiển thị cùng vai trò và trạng thái xác nhận."],
            ["3", "Mở chi tiết phân công, kiểm tra giải, trận, sân, thời gian và vai trò.", "Thông tin trận đấu và tổ trọng tài hiển thị trong popup chi tiết."],
            ["4", "Bấm Xác nhận tham gia trận đấu hoặc Hủy xác nhận nếu không thể tham gia.", "Trạng thái phân công được cập nhật."],
            ["5", "Vào menu Giám sát trận đấu.", "Màn hình giám sát hiển thị thông tin trận, đội 1, đội 2, sân, thời gian và các nút điều khiển trận."],
            ["6", "Bấm Xác nhận tham gia trận đấu và Chọn trọng tài tham gia nếu hệ thống yêu cầu.", "Tổ trọng tài của trận được xác nhận."],
            ["7", "Bấm Bắt đầu khi trận bắt đầu; dùng Tạm dừng/Tiếp tục nếu trận gián đoạn.", "Trạng thái trận thay đổi theo thao tác."],
            ["8", "Nhập tỷ số từng set, người thắng và ghi chú kết quả.", "Dữ liệu kết quả được chuẩn bị để lưu."],
            ["9", "Bấm Kết thúc để hoàn tất trận.", "Trận chuyển sang trạng thái hoàn tất, BTC có thể kiểm tra kết quả."],
            ["10", "Nếu có sự cố, vào Báo cáo sự cố và lập báo cáo theo trận.", "Báo cáo sự cố được lưu để BTC theo dõi/xử lý."],
        ],
        [900, 5200, 3100],
    )
    doc.image(SCREEN_DIR / "09-trong-tai-phan-cong.png", "Hình 18. Trọng tài - lịch phân công")
    doc.image(SCREEN_DIR / "22-trong-tai-giam-sat.png", "Hình 19. Trọng tài - màn hình giám sát trận đấu")
    doc.image(SCREEN_DIR / "23-trong-tai-bao-cao-su-co.png", "Hình 20. Trọng tài - báo cáo sự cố")
    doc.image(SCREEN_DIR / "24-trong-tai-xin-nghi-phep.png", "Hình 21. Trọng tài - xin nghỉ phép")

    doc.heading("8.4. Quy trình đề cử đội và duyệt đội", 2)
    doc.table(
        ["Thành phần", "Nội dung"],
        [
            ["Actor chính", "Ban tổ chức."],
            ["Actor liên quan", "Đội bóng, huấn luyện viên, BTC cấp trên."],
            ["Điều kiện trước", "Đội có thành tích nguồn hoặc đủ điều kiện theo cấu hình của giải/cấp tổ chức."],
            ["Kết quả sau", "Đội được đề cử lên BTC cấp cao hơn; đề cử được xác nhận hoặc từ chối."],
            ["Màn hình sử dụng", "/ban-to-chuc/tu-cach-cap-tren, /ban-to-chuc/doi-bong."],
        ],
        [2400, 6800],
    )
    doc.table(
        ["Bước", "Thao tác trên hệ thống", "Kết quả cần thấy"],
        [
            ["1", "Ban tổ chức đăng nhập và vào menu Suất đại diện.", "Màn hình Tư cách tham gia cấp trên hiển thị."],
            ["2", "Dùng bộ lọc theo đội bóng, giải nguồn hoặc trạng thái thành tích.", "Danh sách đội có thể đề cử hoặc đề cử đã gửi được lọc đúng."],
            ["3", "Kiểm tra thông tin đội ở mục Đội có thể đề cử.", "Hệ thống cho biết đội, thành tích nguồn, giải cấp trên, BTC nhận và trạng thái."],
            ["4", "Thực hiện đề cử đội đủ điều kiện lên cấp trên.", "Đề cử chuyển sang nhóm Đề cử gửi đến BTC hiện tại hoặc trạng thái chờ xác nhận."],
            ["5", "BTC cấp nhận xem đề cử và xác nhận/từ chối.", "Đề cử được cập nhật trạng thái; đội hợp lệ có thể tham gia giải cấp trên."],
            ["6", "Vào menu Đội bóng để theo dõi lại hồ sơ đội và trạng thái liên quan.", "Thông tin đội, HLV, đội hình và đăng ký được đối chiếu trước khi duyệt giải."],
        ],
        [900, 5200, 3100],
    )
    doc.image(SCREEN_DIR / "13-btc-de-cu-duyet-doi.png", "Hình 22. Ban tổ chức - tư cách tham gia cấp trên")
    doc.image(SCREEN_DIR / "12-btc-duyet-doi-bong.png", "Hình 23. Ban tổ chức - danh sách đội bóng phục vụ duyệt đội")

    doc.heading("8.5. Các quy trình hỗ trợ thường dùng", 2)
    doc.p("Ngoài bốn quy trình chính, khi demo hệ thống nên trình bày thêm các thao tác hỗ trợ vì chúng liên quan trực tiếp tới dữ liệu đầu vào của giải và trận.")
    doc.table(
        ["Nhóm thao tác", "Actor", "Màn hình", "Mục đích"],
        [
            ["Quản lý thành viên đội", "Huấn luyện viên", "/huan-luyen-vien/thanh-vien", "Thêm, lọc, kiểm tra trạng thái thành viên đội trước khi lập đội hình."],
            ["Lập đội hình", "Huấn luyện viên", "/huan-luyen-vien/doi-hinh", "Tạo hoặc cập nhật danh sách vận động viên tham gia giải."],
            ["Xem đội hình", "Vận động viên", "/van-dong-vien/doi-hinh", "Vận động viên kiểm tra đội hình mình được đưa vào."],
            ["Xin nghỉ thi đấu", "Vận động viên", "/van-dong-vien/nghi-phep-thi-dau", "Gửi đơn nghỉ khi không thể tham gia trận."],
        ],
        [2200, 1700, 2500, 2800],
    )
    doc.image(SCREEN_DIR / "18-hlv-thanh-vien-doi.png", "Hình 24. Huấn luyện viên - quản lý thành viên đội")
    doc.image(SCREEN_DIR / "19-hlv-doi-hinh.png", "Hình 25. Huấn luyện viên - quản lý đội hình")
    doc.image(SCREEN_DIR / "20-vdv-doi-hinh.png", "Hình 26. Vận động viên - xem đội hình")
    doc.image(SCREEN_DIR / "21-vdv-nghi-phep-thi-dau.png", "Hình 27. Vận động viên - nghỉ phép thi đấu")

    doc.heading("8.6. Bộ ảnh hướng dẫn chi tiết các chức năng chính", 2)
    doc.p("Các hình dưới đây được chụp lại theo từng bước thao tác trên giao diện VTMS. Phần này dùng trực tiếp cho chương hướng dẫn sử dụng khi cần minh họa chi tiết thay vì chỉ mô tả bằng chữ.")

    doc.heading("8.6.1. Chức năng tạo giải đấu", 3)
    doc.table(
        ["Bước", "Ảnh minh họa", "Nội dung cần hướng dẫn"],
        [
            ["1", "Hình 28", "BTC vào menu Giải đấu và xem danh sách giải hiện có."],
            ["2", "Hình 29", "Bấm Tạo giải đấu, nhập thông tin cơ bản: tên, cấp giải, khu vực, luật, giới tính, thời gian."],
            ["3", "Hình 30", "Cuộn xuống phần địa điểm, mô tả và ảnh giải đấu."],
            ["4", "Hình 31", "Nhập điều kiện tham gia: số đội, số VĐV tối thiểu/tối đa, lệ phí, điều kiện thành tích."],
            ["5", "Hình 32", "Chọn thể thức thi đấu, cách ghép cặp và kiểm tra nút Lưu ở cuối form."],
            ["6", "Hình 33", "Sau khi giải có hồ sơ đăng ký, BTC mở popup Đăng ký để duyệt/từ chối đội."],
            ["7", "Hình 34", "HLV vào màn hình Đăng ký giải đấu để xem giải đang mở và gửi hồ sơ."],
        ],
        [900, 1600, 6700],
    )
    doc.image(SCREEN_DIR / "30-main-tao-giai-01-danh-sach-giai-dau.png", "Hình 28. Bước 1 tạo giải - danh sách giải đấu của BTC")
    doc.image(SCREEN_DIR / "31-main-tao-giai-02-form-thong-tin-co-ban.png", "Hình 29. Bước 2 tạo giải - form thông tin cơ bản")
    doc.image(SCREEN_DIR / "32-main-tao-giai-03-dia-diem-mo-ta.png", "Hình 30. Bước 3 tạo giải - địa điểm và mô tả")
    doc.image(SCREEN_DIR / "33-main-tao-giai-04-dieu-kien-tham-gia.png", "Hình 31. Bước 4 tạo giải - điều kiện tham gia")
    doc.image(SCREEN_DIR / "34-main-tao-giai-05-the-thuc-va-nut-luu.png", "Hình 32. Bước 5 tạo giải - thể thức thi đấu và lưu")
    doc.image(SCREEN_DIR / "35-main-tao-giai-06-quan-ly-dang-ky-doi.png", "Hình 33. Bước 6 tạo giải - quản lý đăng ký đội")
    doc.image(SCREEN_DIR / "36-main-tao-giai-07-hlv-dang-ky-giai.png", "Hình 34. Bước 7 tạo giải - HLV đăng ký giải")

    doc.heading("8.6.2. Chức năng tạo trận đấu", 3)
    doc.table(
        ["Bước", "Ảnh minh họa", "Nội dung cần hướng dẫn"],
        [
            ["1", "Hình 35", "BTC vào Lịch thi đấu, chọn giải cần lập lịch."],
            ["2", "Hình 36", "Nếu giải có chia bảng, bấm Thêm bảng đấu và nhập thông tin bảng."],
            ["3", "Hình 37", "Bấm Thêm trận đấu, chọn đội 1, đội 2, vòng đấu và sân."],
            ["4", "Hình 38", "Nhập thời gian bắt đầu/kết thúc, trạng thái trận và kiểm tra cảnh báo hợp lệ."],
            ["5", "Hình 39", "Thêm trọng tài vào trận, chọn vai trò và trạng thái phân công."],
            ["6", "Hình 40", "Đóng form để kiểm tra trận đã xuất hiện trong danh sách lịch thi đấu."],
        ],
        [900, 1600, 6700],
    )
    doc.image(SCREEN_DIR / "40-main-tao-tran-01-man-hinh-lich-thi-dau.png", "Hình 35. Bước 1 tạo trận - màn hình lịch thi đấu")
    doc.image(SCREEN_DIR / "41-main-tao-tran-02-form-tao-bang-dau.png", "Hình 36. Bước 2 tạo trận - form tạo bảng đấu")
    doc.image(SCREEN_DIR / "42-main-tao-tran-03-form-chon-doi-san.png", "Hình 37. Bước 3 tạo trận - chọn đội và sân")
    doc.image(SCREEN_DIR / "43-main-tao-tran-04-thoi-gian-trang-thai.png", "Hình 38. Bước 4 tạo trận - thời gian và trạng thái")
    doc.image(SCREEN_DIR / "44-main-tao-tran-05-phan-cong-trong-tai.png", "Hình 39. Bước 5 tạo trận - phân công trọng tài")
    doc.image(SCREEN_DIR / "45-main-tao-tran-06-danh-sach-tran-da-tao.png", "Hình 40. Bước 6 tạo trận - danh sách trận đã tạo")

    doc.heading("8.6.3. Chức năng trọng tài giám sát trận đấu", 3)
    doc.table(
        ["Bước", "Ảnh minh họa", "Nội dung cần hướng dẫn"],
        [
            ["1", "Hình 41", "Trọng tài vào Lịch phân công để xem trận được giao."],
            ["2", "Hình 42", "Mở Chi tiết trận để kiểm tra giải, sân, đội và tổ trọng tài."],
            ["3", "Hình 43", "Vào Giám sát trận đấu bằng trận được phân công."],
            ["4", "Hình 44", "Xác nhận tham gia hoặc chọn tổ trọng tài tham gia trước khi bắt đầu trận."],
            ["5", "Hình 45", "Vào Báo cáo sự cố để theo dõi hoặc tạo báo cáo liên quan đến trận."],
            ["6", "Hình 46", "Mở form Tạo báo cáo sự cố, chọn trận, nhập tiêu đề, nội dung và minh chứng nếu có."],
        ],
        [900, 1600, 6700],
    )
    doc.image(SCREEN_DIR / "50-main-giam-sat-01-lich-phan-cong.png", "Hình 41. Bước 1 giám sát - lịch phân công trọng tài")
    doc.image(SCREEN_DIR / "51-main-giam-sat-02-chi-tiet-tran-phan-cong.png", "Hình 42. Bước 2 giám sát - chi tiết trận được phân công")
    doc.image(SCREEN_DIR / "52-main-giam-sat-03-man-hinh-giam-sat.png", "Hình 43. Bước 3 giám sát - màn hình giám sát trận")
    doc.image(SCREEN_DIR / "53-main-giam-sat-04-xac-nhan-to-trong-tai.png", "Hình 44. Bước 4 giám sát - xác nhận tham gia trận")
    doc.image(SCREEN_DIR / "54-main-giam-sat-05-bao-cao-su-co.png", "Hình 45. Bước 5 giám sát - danh sách báo cáo sự cố")
    doc.image(SCREEN_DIR / "55-main-giam-sat-06-form-tao-bao-cao-su-co.png", "Hình 46. Bước 6 giám sát - form tạo báo cáo sự cố")

    doc.heading("8.6.4. Chức năng đề cử đội và duyệt đội", 3)
    doc.table(
        ["Bước", "Ảnh minh họa", "Nội dung cần hướng dẫn"],
        [
            ["1", "Hình 47", "BTC vào menu Suất đại diện/Tư cách tham gia cấp trên."],
            ["2", "Hình 48", "Kiểm tra nhóm Đội có thể đề cử theo giải nguồn và thành tích."],
            ["3", "Hình 49", "BTC cấp nhận xem đề cử gửi đến, sau đó bấm Xác nhận hoặc Từ chối."],
            ["4", "Hình 50", "Đối chiếu lại hồ sơ đội ở màn hình Đội bóng trước hoặc sau khi duyệt."],
        ],
        [900, 1600, 6700],
    )
    doc.image(SCREEN_DIR / "60-main-de-cu-01-tu-cach-cap-tren.png", "Hình 47. Bước 1 đề cử - màn hình tư cách tham gia cấp trên")
    doc.image(SCREEN_DIR / "61-main-de-cu-02-danh-sach-doi-co-the-de-cu.png", "Hình 48. Bước 2 đề cử - danh sách đội có thể đề cử")
    doc.image(SCREEN_DIR / "62-main-de-cu-03-de-cu-gui-den-va-duyet.png", "Hình 49. Bước 3 đề cử - đề cử gửi đến và nút duyệt")
    doc.image(SCREEN_DIR / "63-main-de-cu-04-doi-bong-doi-chieu-ho-so.png", "Hình 50. Bước 4 đề cử - đối chiếu hồ sơ đội bóng")

    doc.heading("9. API và route tiêu biểu đang khớp source", 1)
    doc.table(
        ["Nhóm", "Route/API tiêu biểu"],
        [
            ["Xác thực", "POST /api/auth/login, POST /api/auth/logout, GET /api/auth/me, POST /api/account/password"],
            ["Admin", "GET/POST /api/admin/accounts, GET /api/admin/users, GET /api/admin/system-logs"],
            ["Ban tổ chức - giải đấu", "GET/POST /api/organizer/tournaments, POST /api/organizer/tournaments/{id}/publish, /registrations/open, /registrations/close"],
            ["Ban tổ chức - lịch", "GET /api/organizer/schedules/tournaments, GET /api/organizer/tournaments/{id}/schedule, POST /groups, POST /matches"],
            ["Huấn luyện viên", "GET/POST /api/coach/teams, GET /api/coach/lineups, GET /api/coach/tournaments, POST /api/coach/tournament-registrations"],
            ["Vận động viên", "GET /api/athlete/team-invitations, GET /api/athlete/teams, GET /api/athlete/lineups, GET /api/athlete/schedule"],
            ["Trọng tài", "GET /api/referee/assignments, GET /api/referee/matches/{matchId}/supervision, POST /api/referee/matches/{matchId}/result"],
            ["Khán giả", "GET /api/public/teams, /api/public/schedule, /api/public/results, /api/public/standings"],
        ],
        [2300, 6900],
    )

    doc.heading("10. Kiểm thử nhanh sau khi chạy", 1)
    doc.bullet([
        "Truy cập http://localhost:8000 và kiểm tra trang chủ hiển thị CSS/JS đầy đủ.",
        "Đăng nhập từng tài khoản mẫu để kiểm tra điều hướng theo role.",
        "Admin kiểm tra danh sách tài khoản và nhật ký hệ thống.",
        "Ban tổ chức kiểm tra danh sách giải đấu, tạo giải mới và mở/đóng đăng ký.",
        "Huấn luyện viên kiểm tra đội bóng, thành viên, đội hình và đăng ký giải.",
        "Vận động viên kiểm tra lời mời, đội bóng, đội hình và lịch cá nhân.",
        "Trọng tài kiểm tra lịch phân công, giám sát trận và báo cáo sự cố.",
        "Kiểm tra phân quyền bằng cách dùng tài khoản HLV truy cập /admin/users, hệ thống phải từ chối.",
    ])

    doc.heading("11. Ghi chú cập nhật so với bản cũ", 1)
    doc.bullet([
        "Bổ sung đúng username mẫu đang dùng trong CSDL hiện tại.",
        "Sửa lại route theo cauhinh-duongdan.php, đặc biệt các route HLV và VĐV.",
        "Bổ sung module mới/đang có: suất đại diện, tài khoản trọng tài, yêu cầu VĐV, báo cáo sự cố, nghỉ phép thi đấu.",
        "Thêm ảnh chụp thật từ hệ thống local.",
        "Giữ phạm vi chạy thử local và Cloudflared, không mô tả triển khai production quá mức.",
    ])

    doc.p("Kết luận", bold=True, size=26, color="1F4E79")
    doc.p("Tài liệu sau khi cập nhật phản ánh đúng hơn cấu trúc kỹ thuật và màn hình sử dụng hiện tại của VTMS. Khi source thay đổi route hoặc nghiệp vụ, cần cập nhật lại phần hướng dẫn theo vai trò và phụ lục API.")
    return doc


def main() -> int:
    missing = [p for p in SCREEN_DIR.glob("*.png") if not p.exists()]
    required = [
        "01-trang-chu.png",
        "02-dang-nhap.png",
        "03-admin-quan-ly-tai-khoan.png",
        "04-admin-nhat-ky.png",
        "05-btc-giai-dau.png",
        "06-btc-lich-thi-dau.png",
        "07-hlv-ho-so-doi.png",
        "08-vdv-loi-moi.png",
        "09-trong-tai-phan-cong.png",
        "10-btc-tao-giai-modal.png",
        "11-btc-duyet-dang-ky-modal.png",
        "12-btc-duyet-doi-bong.png",
        "13-btc-de-cu-duyet-doi.png",
        "14-btc-tao-bang-dau-modal.png",
        "15-btc-tao-tran-dau-modal.png",
        "16-btc-phan-cong-trong-tai-modal.png",
        "17-hlv-dang-ky-giai.png",
        "18-hlv-thanh-vien-doi.png",
        "19-hlv-doi-hinh.png",
        "20-vdv-doi-hinh.png",
        "21-vdv-nghi-phep-thi-dau.png",
        "22-trong-tai-giam-sat.png",
        "23-trong-tai-bao-cao-su-co.png",
        "24-trong-tai-xin-nghi-phep.png",
        "30-main-tao-giai-01-danh-sach-giai-dau.png",
        "31-main-tao-giai-02-form-thong-tin-co-ban.png",
        "32-main-tao-giai-03-dia-diem-mo-ta.png",
        "33-main-tao-giai-04-dieu-kien-tham-gia.png",
        "34-main-tao-giai-05-the-thuc-va-nut-luu.png",
        "35-main-tao-giai-06-quan-ly-dang-ky-doi.png",
        "36-main-tao-giai-07-hlv-dang-ky-giai.png",
        "40-main-tao-tran-01-man-hinh-lich-thi-dau.png",
        "41-main-tao-tran-02-form-tao-bang-dau.png",
        "42-main-tao-tran-03-form-chon-doi-san.png",
        "43-main-tao-tran-04-thoi-gian-trang-thai.png",
        "44-main-tao-tran-05-phan-cong-trong-tai.png",
        "45-main-tao-tran-06-danh-sach-tran-da-tao.png",
        "50-main-giam-sat-01-lich-phan-cong.png",
        "51-main-giam-sat-02-chi-tiet-tran-phan-cong.png",
        "52-main-giam-sat-03-man-hinh-giam-sat.png",
        "53-main-giam-sat-04-xac-nhan-to-trong-tai.png",
        "54-main-giam-sat-05-bao-cao-su-co.png",
        "55-main-giam-sat-06-form-tao-bao-cao-su-co.png",
        "60-main-de-cu-01-tu-cach-cap-tren.png",
        "61-main-de-cu-02-danh-sach-doi-co-the-de-cu.png",
        "62-main-de-cu-03-de-cu-gui-den-va-duyet.png",
        "63-main-de-cu-04-doi-bong-doi-chieu-ho-so.png",
    ]
    missing = [name for name in required if not (SCREEN_DIR / name).exists()]
    if missing:
        raise SystemExit("Missing screenshots: " + ", ".join(missing))

    if OUT_MAIN.exists() and not BACKUP.exists():
        shutil.copy2(OUT_MAIN, BACKUP)

    doc = build()
    tmp = Path("runtime") / "vtms-guide-updated.docx"
    doc.write(tmp)
    shutil.copy2(tmp, OUT_COPY)
    try:
        shutil.copy2(tmp, OUT_MAIN)
        print(f"Updated {OUT_MAIN}")
    except PermissionError:
        print(f"Could not overwrite {OUT_MAIN}; it may be open in Word.")
    print(f"Wrote {OUT_COPY}")
    print(f"Backup: {BACKUP if BACKUP.exists() else 'not created'}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
