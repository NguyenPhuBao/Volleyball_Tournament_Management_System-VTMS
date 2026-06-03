# VTMS Laravel Migration Design

## Context

VTMS hien la ung dung PHP thuan theo Monolithic MVC tu viet. Source co `app/backend/controllers`, `services`, `models`, `core`, `app/frontend/views`, va assets trong `public/assets`. Route hien tai nam trong `app/backend/config/cauhinh-duongdan.php` voi 575 khai bao route web/API, gom nhieu alias tieng Viet va tieng Anh cho cung mot hanh vi.

Muc tieu la chuyen toan bo ung dung sang Laravel trong thu muc `laravel/` truoc, giu ban PHP thuan hien tai chay duoc de doi chieu trong qua trinh migrate.

## Version Target

Target framework la Laravel 12, chay tren PHP 8.2 tro len. Ly do: Laravel 12 van dang duoc ho tro, yeu cau PHP thap hon Laravel 13, va phu hop hon voi du an hien tai dang ghi PHP 8.1+ trong README. May local hien co XAMPP PHP 8.0.30 va chua co Composer trong PATH, nen can cai PHP moi/Composer hoac dung PHP portable truoc khi co the chay Laravel day du.

## Migration Approach

Tao ung dung Laravel trong `laravel/` va khong di chuyen source PHP thuan sang `legacy/` trong giai doan dau. Thu muc goc tiep tuc phuc vu ban cu bang `public/index.php`; thu muc `laravel/public` phuc vu ban Laravel moi.

Chuyen doi theo module thay vi rewrite mot lan:

1. Bootstrap Laravel shell: config `.env.example`, MySQL, session, auth helpers, middleware role, audit logging, error pages, asset handling.
2. Shared/auth/dashboard: login, logout, `/dashboard`, `/api/auth/*`, doi mat khau.
3. Admin module.
4. Ban to chuc module.
5. Trong tai module.
6. Huan luyen vien module.
7. Van dong vien module.

Moi module sau khi migrate phai giu URL, HTTP method, JSON shape, session role, va view tuong duong ban cu.

## Architecture

Laravel app se dung cau truc:

- `laravel/routes/web.php`: route tra ve view va form actions.
- `laravel/routes/api.php`: route `/api/*`, giu path hien co.
- `laravel/app/Http/Controllers/*`: controller theo role/module.
- `laravel/app/Services/*`: nghiep vu, port tu `app/backend/services`.
- `laravel/app/Repositories/*` hoac `laravel/app/Models/*`: truy van du lieu.
- `laravel/resources/views/*`: Blade views port tu `app/frontend/views` va layouts.
- `laravel/public/assets/*`: copy CSS/JS hien co de tranh thay doi frontend behavior trong dot dau.
- `laravel/app/Http/Middleware/RoleMiddleware.php`: thay `RoleMiddleware` tu viet.
- `laravel/app/Http/Middleware/AuditRequestMiddleware.php`: thay audit logging dang nam trong router tu viet.
- `laravel/app/Support/LegacySessionUser.php`: gom logic doc/ghi user session tuong duong `App\Backend\Core\Auth\Auth`.

Uu tien Query Builder/DB facade cho cac query SQL hien co. Eloquent chi dung khi no khong lam doi schema, ten bang, khoa chinh, hoac ket qua tra ve.

## Data And Schema

Khong doi `vtms5.sql` trong migration dau. Laravel dung database `vtms` hien co, MySQL charset `utf8mb4`, collation `utf8mb4_unicode_ci`.

Ten bang va cot giu nguyen chu hoa/thuan Viet khong dau, vi source hien tai va dump dang phu thuoc truc tiep vao cac ten nhu `Taikhoan`, `Nguoidung`, `Role`, `Nhatkyhethong`.

Khong tao Laravel migrations day du trong dot dau. Co the them migration/seed sau khi ban Laravel da tuong duong hanh vi.

## Authentication And Authorization

Khong dung Laravel Breeze/Fortify trong dot dau de tranh doi flow. Auth se giu co che session hien co:

- Login verify bang `password_verify` voi cot `Taikhoan.password`.
- Session user array giu cac key `id`, `username`, `name`, `email`, `role`, va cac nested key theo role.
- Login session tiep tuc ghi `Phiendangnhap`.
- Login history tiep tuc ghi `Lichsudangnhap`.
- Role middleware tra JSON 401/403 cho API va render `errors.403` cho web.

CSRF cho web dung middleware Laravel mac dinh. API route hien tai khong bat buoc CSRF, vi frontend JS goi endpoint `/api/*`.

## Views And Assets

View PHP se duoc port sang Blade theo mapping hien co trong `hethong-bando-file.php`.

Trong dot dau, giu HTML/CSS/JS hien co va chi thay:

- `<?= e(...) ?>` -> Blade escaping.
- `url(...)` -> `url(...)`/`route(...)` Laravel.
- `asset(...)` -> `asset(...)`, dong thoi copy file theo ten asset hien tai hoac tao mapping helper neu can.
- `csrf_field()` -> `@csrf`.
- `Auth::user()` -> session user helper.

Khong redesign UI va khong doi JS API contract.

## Route Compatibility

Route Laravel phai bao gom tat ca 575 route hien tai, ke ca alias:

- `/api/coach/*`, `/api/huan-luyen-vien/*`, `/api/huanluyenvien/*`
- `/api/referee/*`, `/api/trong-tai/*`, `/api/trongtai/*`
- `/api/athlete/*`, `/api/player/*`, `/api/van-dong-vien/*`, `/api/vandongvien/*`

Route co `{id}` trong router cu duoc map sang tham so Laravel cung ten. Controller lay route params tu `Illuminate\Http\Request` hoac tham so method.

## Error Handling

Laravel exception handler se render:

- 404 -> `errors.404`
- 403 -> `errors.403`
- 500 -> `errors.500` khi `APP_DEBUG=false`

API errors giu payload dang `success: false`, `message`, va neu co thi `errors`.

## Testing And Verification

Viec migrate dung TDD theo module:

1. Viet feature tests cho route/auth/role/API response cua module.
2. Chay test de thay fail tren Laravel app moi.
3. Port controller/service/model/view toi khi test pass.
4. Doi chieu nhanh browser/API voi ban PHP thuan neu can.

Minimum acceptance:

- `php artisan route:list` co route tuong duong ban cu.
- Auth login/logout/API me chay duoc voi seed user `admin_test` mat khau `123456`.
- Dashboard va cac trang role chinh render khong loi.
- API CRUD/action cua tung module tra JSON shape nhu ban cu.
- Audit logging van ghi `Nhatkyhethong`.

## Out Of Scope For First Migration

- Doi schema database.
- Chuyen toan bo SQL sang Eloquent thuần.
- Doi UI/UX, doi JS frontend, hoac them SPA/Inertia.
- Xoa ban PHP thuan khoi root.
- Toi uu performance lon khi chua co parity.

## Open Environment Issue

May local hien khong co `php`/`composer` trong PATH; XAMPP co PHP 8.0.30. Laravel 12 can PHP 8.2+. Truoc khi scaffold Laravel bang Composer, can mot trong hai cach:

- Cai PHP 8.2+ va Composer vao PATH.
- Dung PHP portable 8.2+ va Composer PHAR trong workspace de tao/chay `laravel/`.
