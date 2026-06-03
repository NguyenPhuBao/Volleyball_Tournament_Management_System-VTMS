# VTMS

VTMS la he thong quan ly giai dau bong chuyen. Du an duoc viet bang PHP thuan theo huong Monolithic MVC ket hop Service Layer, co giao dien theo vai tro va API noi bo cho tung module nghiep vu.

## Cong nghe

- PHP 8.1+.
- MySQL/MariaDB, ket noi qua PDO MySQL.
- HTML, CSS, JavaScript thuan.
- PHP built-in server hoac Apache/XAMPP.

Trong source hien tai khong co `composer.json` hoac `package.json`, nen khong can chay `composer install` hay `npm install`.

## Kien truc du an

```text
VTMS/
|-- app/
|   |-- backend/
|   |   |-- config/             # Cau hinh app, database, route
|   |   |-- controllers/        # Controller theo vai tro/module
|   |   |-- core/               # Config, Database, Router, Request, Response, View, Middleware, Auth
|   |   |-- models/             # Lop truy van du lieu
|   |   |-- services/           # Nghiep vu ung dung
|   |   |-- hethong-bando-file.php
|   |   `-- hethong-khoidong.php
|   `-- frontend/
|       |-- layout/             # Layout chinh va layout xac thuc
|       `-- views/              # View theo role: admin, bantochuc, trongtai, huanluyenvien, vandongvien
|-- public/
|   |-- assets/                 # CSS/JS public
|   |-- uploads/                # File upload khi chay local
|   |-- index.php               # Front Controller
|   |-- server.php              # Entry cho PHP built-in server
|   `-- .htaccess               # Rewrite ve index.php khi chay Apache
|-- Diagrams/                   # BPMN, use case/class, function, technology architecture
|-- Documents/                  # Tai lieu SRS
|-- tools/                      # Script ho tro test, chup man hinh, tao tai lieu
|-- vtms5.sql                   # Dump CSDL hien tai
|-- .env.example
`-- README.md
```

Luongs khoi dong:

1. `public/index.php` dinh nghia `BASE_PATH`, nap `app/backend/hethong-khoidong.php`.
2. `hethong-khoidong.php` nap `.env`, dang ky autoload, nap config, cau hinh session/database, tao router.
3. `hethong-bando-file.php` map ten class/config/view/asset logic sang ten file tieng Viet trong source.
4. `app/backend/config/cauhinh-duongdan.php` dang ky route web va API.

## Module chinh

### Dung chung

- Trang chu cong khai `/`.
- Dang nhap/dang xuat `/login`, `/logout`.
- Dashboard theo vai tro `/dashboard`.
- Doi mat khau `/tai-khoan/doi-mat-khau`.
- API xac thuc: `/api/auth/login`, `/api/auth/logout`, `/api/auth/me`.

### Quan tri he thong

- `/admin`: dashboard quan tri.
- `/admin/users`: quan ly tai khoan.
- `/admin/nguoi-dung`: quan ly ho so nguoi dung.
- `/admin/logs`: nhat ky he thong.
- `/admin/xac-nhan-thong-tin-btc`: duyet yeu cau doi thong tin ban to chuc.

### Ban to chuc

- `/ban-to-chuc/giai-dau`: tao, cap nhat, cong bo, huy giai dau.
- `/ban-to-chuc/doi-bong`: quan ly ho so doi bong va tu cach tham gia.
- `/ban-to-chuc/tu-cach-cap-tren`: de cu/duyet suat tham du cap tren.
- `/ban-to-chuc/trong-tai`: quan ly trong tai, lich nghi va phan cong.
- `/ban-to-chuc/tai-khoan-hlv`: duyet tai khoan huan luyen vien.
- `/ban-to-chuc/tai-khoan-trong-tai`: duyet tai khoan trong tai.
- `/ban-to-chuc/huan-luyen-vien`: quan ly tu cach huan luyen vien.
- `/ban-to-chuc/van-dong-vien`: quan ly tu cach van dong vien.
- `/ban-to-chuc/san-dau`: quan ly san dau/vi tri thi dau.
- `/ban-to-chuc/lich-thi-dau`: tao bang dau, vong dau, tran dau, lich thi dau.
- `/ban-to-chuc/khieu-nai`: xu ly khieu nai.
- `/ban-to-chuc/ket-qua`: cap nhat ket qua tran dau.
- `/ban-to-chuc/xep-hang`: bang xep hang.
- `/ban-to-chuc/xac-nhan-thong-tin-ca-nhan`: yeu cau cap nhat thong tin ca nhan.

### Trong tai

- `/trong-tai/lich-phan-cong`: lich phan cong cua toi.
- `/trong-tai/giam-sat`: giam sat tran dau.
- `/trong-tai/bao-cao-su-co`: bao cao su co tran dau.
- `/trong-tai/xin-nghi-phep`: tao don nghi phep.
- `/trong-tai/dang-ky`: dang ky tai khoan trong tai cong khai.

### Huan luyen vien

- `/huan-luyen-vien/dang-ky`: dang ky tai khoan HLV cong khai.
- `/huan-luyen-vien/van-dong-vien`: quan ly tai khoan/ho so van dong vien.
- `/huan-luyen-vien/giai-dau`: dang ky giai dau.
- `/huan-luyen-vien/doi-bong`: ho so doi bong.
- `/huan-luyen-vien/thanh-vien`: thanh vien doi.
- `/huan-luyen-vien/doi-hinh`: xem doi hinh.
- `/huan-luyen-vien/doi-hinh/chinh-sua`: chinh sua doi hinh.
- `/huan-luyen-vien/lich-thi-dau-doi`: lich thi dau cua doi.
- `/huan-luyen-vien/ket-qua`: ket qua thi dau.
- `/huan-luyen-vien/yeu-cau-vdv`: duyet yeu cau lien quan van dong vien.

### Van dong vien

- `/van-dong-vien/thong-bao`: thong bao.
- `/van-dong-vien/loi-moi-doi-bong`: loi moi tham gia doi bong.
- `/van-dong-vien/doi-bong-cua-toi`: thong tin doi bong cua toi.
- `/van-dong-vien/doi-hinh`: xem doi hinh.
- `/van-dong-vien/lich-thi-dau-ca-nhan`: lich thi dau ca nhan.
- `/van-dong-vien/ho-so`: ho so ca nhan.
- `/van-dong-vien/nghi-phep-thi-dau`: don nghi phep thi dau.

## Co so du lieu

CSDL hien tai nam trong `vtms5.sql`.

Thong tin quan trong:

- File dump tao database `vtms` voi `utf8mb4_unicode_ci`.
- File co lenh `DROP DATABASE IF EXISTS vtms;`, nen import se xoa database `vtms` hien co truoc khi tao lai.
- Dump duoc tao tu MySQL dump 8.0.43, server MariaDB 10.4.32.
- Co 64 bang, bao gom nhom bang tai khoan/phan quyen, khu vuc/don vi, giai dau, doi bong, thanh vien, doi hinh, bang dau/vong dau/tran dau, trong tai, ket qua, bang xep hang, khieu nai, thong bao, nhat ky va phien dang nhap.
- Bang `role` seed 6 role: `ADMIN`, `BAN_TO_CHUC`, `TRONG_TAI`, `HUAN_LUYEN_VIEN`, `VAN_DONG_VIEN`, `BIEN_TAP`.
- Cac route hien tai tap trung vao `ADMIN`, `BAN_TO_CHUC`, `TRONG_TAI`, `HUAN_LUYEN_VIEN`, `VAN_DONG_VIEN`. Actor khan gia da duoc loai khoi source nen khong con route `/khan-gia`, `/spectator`, `/api/khan-gia`, `/api/public` hoac `/api/spectator`.

So luong tai khoan seed trong bang `taikhoan`:

| Role ID | Role | So tai khoan seed | Vi du username |
| --- | --- | ---: | --- |
| 1 | ADMIN | 1 | `admin_test` |
| 2 | BAN_TO_CHUC | 6 | `btc_quocgia`, `btc_hn`, `btc_dn`, `btc_hcm` |
| 3 | TRONG_TAI | 24 | `tt_quocgia_01`, `tt_hn_01`, `tt_dn_01`, `tt_hcm_01` |
| 4 | HUAN_LUYEN_VIEN | 12 | `hlv_quocgia_01`, `hlv_hn_01`, `hlv_dn_01`, `hlv_hcm_01` |
| 5 | VAN_DONG_VIEN | 72 | `vdv_quocgia_01`, `vdv_tinh_01`, `vdv_phuong_01` |

Mat khau seed mau da xac thuc bang `password_verify` la:

```text
123456
```

## Cai dat local

### 1. Chuan bi moi truong

- PHP 8.1+ hoac XAMPP co PHP.
- MySQL/MariaDB.
- Extension PHP PDO MySQL.

### 2. Tao file `.env`

```powershell
Copy-Item .env.example .env
```

Cap nhat `.env` theo moi truong local. Neu chay bang PHP built-in server thi nen dung `http`, vi built-in server khong tu tao HTTPS:

```env
APP_NAME=VTMS
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Ho_Chi_Minh
APP_SESSION_NAME=VTMS_SESSION

DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=vtms
DB_USERNAME=root
DB_PASSWORD=123456
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

Neu MySQL cua XAMPP khong co mat khau root, dat:

```env
DB_PASSWORD=
```

### 3. Import CSDL

Canh bao: lenh nay se xoa database `vtms` hien co vi `vtms5.sql` co `DROP DATABASE IF EXISTS vtms;`.

Neu `mysql` co trong PATH:

```powershell
mysql -u root -p < vtms5.sql
```

Neu dung MySQL cua XAMPP:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -p < vtms5.sql
```

Neu root khong co mat khau:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root < vtms5.sql
```

Co the import bang phpMyAdmin neu khong dung CLI.

### 4. Chay ung dung

Dung PHP trong PATH:

```powershell
php -S localhost:8000 -t public public/server.php
```

Bang PHP cua XAMPP:

```powershell
C:\xampp\php\php.exe -S localhost:8000 -t public public/server.php
```

Mo trinh duyet:

```text
http://localhost:8000
```

## Chay bang Apache/XAMPP

Neu chay bang Apache, tro DocumentRoot ve thu muc `public/`. File `public/.htaccess` se rewrite cac request ve `index.php`.

Luu y: `.htaccess` hien co rule redirect sang HTTPS. Neu local Apache chua cau hinh HTTPS, cach don gian hon la chay bang PHP built-in server nhu muc tren.

## Cloudflare Tunnel de demo

Chay PHP server:

```powershell
Start-Process -FilePath "C:\xampp\php\php.exe" `
  -ArgumentList @("-S","127.0.0.1:8000","-t","public","public/server.php") `
  -WorkingDirectory (Get-Location) `
  -WindowStyle Hidden
```

Chay tunnel:

```powershell
.\tools\cloudflared.exe tunnel --url http://127.0.0.1:8000 --no-autoupdate
```

Sau khi co URL tunnel, cap nhat `APP_URL` trong `.env` neu can tao link public theo URL tunnel.

Dung server/tunnel:

```powershell
Stop-Process -Name php, cloudflared -Force
```

## Script ho tro

Thu muc `tools/` co cac script phu tro:

- `generate_vtms_testcases.py`: tao test case/tai lieu test.
- `capture_vtms_screenshots.js`, `capture_vtms_workflow_screenshots.js`, `capture_vtms_main_flow_steps.js`: chup man hinh quy trinh.
- `build_vtms_user_guide_docx.py`: tao tai lieu huong dan su dung tu anh chup man hinh.
- `cloudflared.exe`: binary local de chay tunnel, khong nen commit len GitHub.

Cac file sinh ra thuong nam trong `runtime/` va duoc ignore.

## Ghi chu khi day len GitHub

- Khong commit `.env`.
- Khong commit `runtime/`.
- Khong commit file upload trong `public/uploads/`.
- Khong commit binary local nhu `tools/cloudflared.exe`.
- Nen commit `.env.example`, `vtms5.sql`, source trong `app/`, `public/assets/`, `Documents/`, `Diagrams/` neu day la tai lieu chinh thuc cua mon hoc.

Neu cac file local da bi Git track tu truoc, `.gitignore` khong tu bo track. Co the dung:

```powershell
git rm --cached tools/cloudflared.exe runtime/php-server.err .vtms_schema_actual.json
```

Chi chay lenh tren neu chac chan cac file do khong can nam trong repository.

## Loi thuong gap

- Khong vao duoc trang: kiem tra PHP server dang chay va truy cap dung `http://localhost:8000`.
- Loi ket noi CSDL: kiem tra `.env`, MySQL dang chay, va da import `vtms5.sql`.
- Dang nhap that bai: kiem tra database `vtms` da duoc import lai tu file dump moi nhat; tai khoan seed dung mat khau `123456`.
- Bi chuyen sang HTTPS khi chay Apache local: do rule trong `public/.htaccess`; dung PHP built-in server hoac cau hinh HTTPS cho Apache.
