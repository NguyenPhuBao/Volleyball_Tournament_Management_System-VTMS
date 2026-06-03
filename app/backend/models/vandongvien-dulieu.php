<?php

declare(strict_types=1);

namespace App\Backend\Models;

use App\Backend\Core\Model;
use Throwable;

final class Vandongvien extends Model
{
    public function listForOrganizer(int $organizerId, array $filters = []): array
    {
        [$sql, $bindings] = $this->baseAthleteQuery($organizerId, $filters);
        $sql .= ' ORDER BY vdv.idvandongvien DESC';

        $statement = $this->db()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function findForOrganizer(int $organizerId, int $athleteId): ?array
    {
        [$sql, $bindings] = $this->baseAthleteQuery($organizerId, []);
        $sql .= ' AND vdv.idvandongvien = :athlete_id LIMIT 1';
        $bindings['athlete_id'] = $athleteId;

        return $this->first($sql, $bindings);
    }

    public function accountValueExists(string $field, string $value): bool
    {
        if (!in_array($field, ['username', 'email', 'sodienthoai'], true)) {
            return false;
        }

        return $this->first(
            "SELECT 1
             FROM Taikhoan
             WHERE {$field} = :value
             LIMIT 1",
            ['value' => $value]
        ) !== null;
    }

    public function profileValueExists(string $field, string $value): bool
    {
        if (!in_array($field, ['cccd'], true)) {
            return false;
        }

        return $this->first(
            "SELECT 1
             FROM Nguoidung
             WHERE {$field} = :value
             LIMIT 1",
            ['value' => $value]
        ) !== null;
    }

    public function athleteCodeExists(string $code): bool
    {
        return $this->first(
            "SELECT 1
             FROM Vandongvien
             WHERE mavandongvien = :code
             LIMIT 1",
            ['code' => $code]
        ) !== null;
    }

    public function roleIdByName(string $roleName): ?int
    {
        $role = $this->first(
            "SELECT idrole
             FROM `Role`
             WHERE namerole = :role_name
             LIMIT 1",
            ['role_name' => $roleName]
        );

        return $role === null ? null : (int) $role['idrole'];
    }

    public function coachByAccountId(int $accountId): ?array
    {
        return $this->first(
            "SELECT
                hlv.idhuanluyenvien,
                hlv.idnguoidung,
                hlv.bangcap,
                hlv.kinhnghiem,
                hlv.trangthai,
                tk.idtaikhoan,
                tk.username,
                tk.email,
                tk.trangthai AS trangthai_taikhoan,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten
             FROM Huanluyenvien hlv
             JOIN Nguoidung nd ON nd.idnguoidung = hlv.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             WHERE tk.idtaikhoan = :account_id
             LIMIT 1",
            ['account_id' => $accountId]
        );
    }

    public function teamForCoach(int $coachId, int $teamId): ?array
    {
        return $this->first(
            "SELECT
                db.iddoibong,
                db.tendoibong,
                db.logo,
                db.diaphuong,
                db.mota,
                db.idhuanluyenvien,
                db.trangthai,
                (
                    SELECT gd.idbantochuc
                    FROM Dangkygiaidau dk
                    JOIN Giaidau gd ON gd.idgiaidau = dk.idgiaidau
                    WHERE dk.iddoibong = db.iddoibong
                    ORDER BY dk.ngaydangky DESC, dk.iddangky DESC
                    LIMIT 1
                ) AS idbantochuc,
                (
                    SELECT gd.tengiaidau
                    FROM Dangkygiaidau dk
                    JOIN Giaidau gd ON gd.idgiaidau = dk.idgiaidau
                    WHERE dk.iddoibong = db.iddoibong
                    ORDER BY dk.ngaydangky DESC, dk.iddangky DESC
                    LIMIT 1
                ) AS tengiaidau
             FROM Doibong db
             WHERE db.iddoibong = :team_id
               AND db.idhuanluyenvien = :coach_id
             LIMIT 1",
            [
                'team_id' => $teamId,
                'coach_id' => $coachId,
            ]
        );
    }

    public function receivingOrganizer(?int $organizerId = null): ?array
    {
        $bindings = [];
        $where = [
            "btc.trangthai = 'HOAT_DONG'",
            "tk.trangthai = 'HOAT_DONG'",
        ];

        if ($organizerId !== null) {
            $where[] = 'btc.idbantochuc = :organizer_id';
            $bindings['organizer_id'] = $organizerId;
        }

        return $this->first(
            "SELECT
                btc.idbantochuc,
                btc.idnguoidung,
                btc.donvi,
                btc.chucvu,
                btc.trangthai,
                tk.idtaikhoan,
                tk.username,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten
             FROM Bantochuc btc
             JOIN Nguoidung nd ON nd.idnguoidung = btc.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             WHERE " . implode(' AND ', $where) . "
             ORDER BY btc.idbantochuc ASC
             LIMIT 1",
            $bindings
        );
    }

    public function createAthleteAccount(
        array $account,
        array $profile,
        array $athlete,
        array $membership,
        int $coachId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): array {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "INSERT INTO Taikhoan (username, password, email, sodienthoai, idrole, trangthai)
                 VALUES (:username, :password, :email, :sodienthoai, :idrole, 'HOAT_DONG')"
            );
            $statement->execute([
                'username' => $account['username'],
                'password' => $account['password'],
                'email' => $account['email'],
                'sodienthoai' => $account['sodienthoai'],
                'idrole' => $account['idrole'],
            ]);

            $newAccountId = (int) $db->lastInsertId();

            $statement = $db->prepare(
                "INSERT INTO Nguoidung
                    (idtaikhoan, ten, hodem, gioitinh, ngaysinh, quequan, diachi, avatar, cccd)
                 VALUES
                    (:idtaikhoan, :ten, :hodem, :gioitinh, :ngaysinh, :quequan, :diachi, :avatar, :cccd)"
            );
            $statement->execute([
                'idtaikhoan' => $newAccountId,
                'ten' => $profile['ten'],
                'hodem' => $profile['hodem'],
                'gioitinh' => $profile['gioitinh'],
                'ngaysinh' => $profile['ngaysinh'],
                'quequan' => $profile['quequan'],
                'diachi' => $profile['diachi'],
                'avatar' => $profile['avatar'],
                'cccd' => $profile['cccd'],
            ]);

            $userId = (int) $db->lastInsertId();

            $statement = $db->prepare(
                "INSERT INTO Vandongvien
                    (idnguoidung, mavandongvien, chieucao, cannang, vitri, trangthaidaugiai)
                 VALUES
                    (:idnguoidung, :mavandongvien, :chieucao, :cannang, :vitri, 'DU_DIEU_KIEN')"
            );
            $statement->execute([
                'idnguoidung' => $userId,
                'mavandongvien' => $athlete['mavandongvien'],
                'chieucao' => $athlete['chieucao'],
                'cannang' => $athlete['cannang'],
                'vitri' => $athlete['vitri'],
            ]);

            $athleteId = (int) $db->lastInsertId();
            $memberId = null;

            if ($membership['team_id'] !== null) {
                $statement = $db->prepare(
                    "INSERT INTO Thanhviendoibong
                        (iddoibong, idvandongvien, vaitro, trangthai, ngaythamgia)
                     VALUES
                        (:team_id, :athlete_id, :role, :status, :join_date)"
                );
                $statement->execute([
                    'team_id' => $membership['team_id'],
                    'athlete_id' => $athleteId,
                    'role' => $membership['role'],
                    'status' => $membership['status'],
                    'join_date' => $membership['join_date'],
                ]);

                $memberId = (int) $db->lastInsertId();

                $statement = $db->prepare(
                    "INSERT INTO Lichsuthanhviendoibong (idthanhvien, hanhdong, ghichu, idnguoithuchien)
                     VALUES (:member_id, 'THEM_THANH_VIEN', :note, :actor_account_id)"
                );
                $statement->execute([
                    'member_id' => $memberId,
                    'note' => 'HLV tao tai khoan va them VDV vao doi bong',
                    'actor_account_id' => $actorAccountId,
                ]);
            }

            $this->recordStatusHistory('TAI_KHOAN', $newAccountId, null, 'HOAT_DONG', 'HLV tao tai khoan van dong vien', $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Tao tai khoan van dong vien', 'Taikhoan', $newAccountId, $ipAddress, $logNote);
            $this->recordSystemLog($actorAccountId, 'Tao ho so van dong vien', 'Vandongvien', $athleteId, $ipAddress, $logNote);

            if ($memberId !== null) {
                $this->recordSystemLog($actorAccountId, 'Them van dong vien vao doi bong', 'Thanhviendoibong', $memberId, $ipAddress, $logNote);
            }

            $db->commit();

            return [
                'account_id' => $newAccountId,
                'user_id' => $userId,
                'athlete_id' => $athleteId,
                'athlete_code' => $athlete['mavandongvien'],
                'membership_id' => $memberId,
                'team_id' => $membership['team_id'],
                'account_status' => 'HOAT_DONG',
                'competition_status' => 'DU_DIEU_KIEN',
            ];
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function membershipsForOrganizer(int $organizerId, int $athleteId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                tv.idthanhvien,
                tv.iddoibong,
                tv.idvandongvien,
                tv.vaitro AS vaitrotrongdoi,
                tv.trangthai AS trangthaithanhvien,
                tv.ngaythamgia,
                tv.ngayroi,
                db.tendoibong,
                db.logo AS doibong_logo,
                db.diaphuong AS doibong_diaphuong,
                db.trangthai AS trangthaidoibong,
                dk.iddangky,
                dk.idgiaidau,
                dk.trangthai AS trangthaidangky,
                gd.tengiaidau,
                gd.trangthai AS trangthaigiaidau,
                hlv.idhuanluyenvien,
                TRIM(CONCAT(COALESCE(nd_hlv.hodem, ''), ' ', COALESCE(nd_hlv.ten, ''))) AS huanluyenvien_hoten,
                tk_hlv.username AS huanluyenvien_username
             FROM Thanhviendoibong tv
             JOIN Doibong db ON db.iddoibong = tv.iddoibong
             JOIN Dangkygiaidau dk ON dk.iddoibong = db.iddoibong
             JOIN Giaidau gd ON gd.idgiaidau = dk.idgiaidau
             JOIN Huanluyenvien hlv ON hlv.idhuanluyenvien = db.idhuanluyenvien
             JOIN Nguoidung nd_hlv ON nd_hlv.idnguoidung = hlv.idnguoidung
             JOIN Taikhoan tk_hlv ON tk_hlv.idtaikhoan = nd_hlv.idtaikhoan
             WHERE gd.idbantochuc = :organizer_id
               AND tv.idvandongvien = :athlete_id
             ORDER BY gd.ngaytao DESC, db.tendoibong, tv.idthanhvien"
        );

        $statement->execute([
            'organizer_id' => $organizerId,
            'athlete_id' => $athleteId,
        ]);

        return $statement->fetchAll();
    }

    public function lineupsForOrganizer(int $organizerId, int $athleteId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                ctdh.idchitietdoihinh,
                ctdh.iddoihinh,
                ctdh.idvandongvien,
                ctdh.vitri,
                ctdh.sothutu,
                ctdh.ghichu,
                dh.tendoihinh,
                dh.trangthai AS trangthaidoihinh,
                dh.iddoibong,
                dh.idgiaidau,
                db.tendoibong,
                gd.tengiaidau
             FROM Chitietdoihinh ctdh
             JOIN Doihinh dh ON dh.iddoihinh = ctdh.iddoihinh
             JOIN Doibong db ON db.iddoibong = dh.iddoibong
             JOIN Giaidau gd ON gd.idgiaidau = dh.idgiaidau
             WHERE gd.idbantochuc = :organizer_id
               AND ctdh.idvandongvien = :athlete_id
             ORDER BY gd.ngaytao DESC, dh.iddoihinh DESC, ctdh.sothutu"
        );

        $statement->execute([
            'organizer_id' => $organizerId,
            'athlete_id' => $athleteId,
        ]);

        return $statement->fetchAll();
    }

    public function statsForOrganizer(int $organizerId, int $athleteId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                tkcn.idthongkecanhan,
                tkcn.idvandongvien,
                tkcn.idgiaidau,
                tkcn.idtrandau,
                tkcn.sodiem,
                tkcn.solanphatbong,
                tkcn.solanchanbong,
                tkcn.solanghidiem,
                tkcn.ghichu,
                gd.tengiaidau,
                vd.tenvongdau AS vongdau,
                td.thoigianbatdau
             FROM Thongkecanhan tkcn
             JOIN Giaidau gd ON gd.idgiaidau = tkcn.idgiaidau
             JOIN Trandau td ON td.idtrandau = tkcn.idtrandau
             LEFT JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             WHERE gd.idbantochuc = :organizer_id
               AND tkcn.idvandongvien = :athlete_id
             ORDER BY td.thoigianbatdau DESC, tkcn.idthongkecanhan DESC"
        );

        $statement->execute([
            'organizer_id' => $organizerId,
            'athlete_id' => $athleteId,
        ]);

        return $statement->fetchAll();
    }

    public function findByAccountId(int $accountId): ?array
    {
        return $this->first(
            "SELECT
                vdv.idvandongvien,
                vdv.idnguoidung,
                vdv.mavandongvien,
                vdv.chieucao,
                vdv.cannang,
                vdv.vitri,
                vdv.trangthaidaugiai,
                nd.idtaikhoan,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                nd.gioitinh,
                nd.ngaysinh,
                nd.quequan,
                nd.diachi,
                nd.avatar,
                nd.cccd,
                tk.username,
                tk.email,
                tk.sodienthoai,
                tk.trangthai AS trangthai_taikhoan
             FROM Vandongvien vdv
             JOIN Nguoidung nd ON nd.idnguoidung = vdv.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             WHERE tk.idtaikhoan = :account_id
             LIMIT 1",
            ['account_id' => $accountId]
        );
    }

    public function teamInvitationsForAthlete(int $athleteId, array $filters = []): array
    {
        [$where, $bindings] = $this->teamInvitationWhere($athleteId, $filters);

        $statement = $this->db()->prepare(
            $this->baseTeamInvitationSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY lm.ngaygui DESC, lm.idloimoi DESC'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function teamInvitationForAthlete(int $athleteId, int $invitationId): ?array
    {
        return $this->first(
            $this->baseTeamInvitationSelect() . '
             WHERE lm.idvandongvien = :athlete_id
               AND lm.idloimoi = :invitation_id
             LIMIT 1',
            [
                'athlete_id' => $athleteId,
                'invitation_id' => $invitationId,
            ]
        );
    }

    public function respondTeamInvitation(
        int $athleteId,
        int $invitationId,
        string $responseStatus,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): ?array {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $invitation = $this->first(
                $this->baseTeamInvitationSelect() . '
                 WHERE lm.idvandongvien = :athlete_id
                   AND lm.idloimoi = :invitation_id
                 LIMIT 1
                 FOR UPDATE',
                [
                    'athlete_id' => $athleteId,
                    'invitation_id' => $invitationId,
                ]
            );

            if ($invitation === null) {
                $db->rollBack();
                return null;
            }

            if ((string) $invitation['trangthai'] !== 'CHO_PHAN_HOI') {
                throw new \RuntimeException('INVITATION_NOT_PENDING');
            }

            if ((string) $invitation['trangthai_hienthi'] === 'HET_HAN') {
                $statement = $db->prepare(
                    "UPDATE Loimoidoibong
                     SET trangthai = 'HET_HAN'
                     WHERE idloimoi = :invitation_id
                       AND trangthai = 'CHO_PHAN_HOI'"
                );
                $statement->execute(['invitation_id' => $invitationId]);
                throw new \RuntimeException('INVITATION_EXPIRED');
            }

            if ($responseStatus === 'DONG_Y') {
                $this->acceptTeamInvitation($invitation, $actorAccountId);
                $action = 'Xac nhan tham gia doi bong';
            } elseif ($responseStatus === 'TU_CHOI') {
                $statement = $db->prepare(
                    "UPDATE Loimoidoibong
                     SET trangthai = 'TU_CHOI',
                         ngayphanhoi = CURRENT_TIMESTAMP
                     WHERE idloimoi = :invitation_id
                       AND idvandongvien = :athlete_id
                       AND trangthai = 'CHO_PHAN_HOI'"
                );
                $statement->execute([
                    'invitation_id' => $invitationId,
                    'athlete_id' => $athleteId,
                ]);
                $action = 'Tu choi tham gia doi bong';
            } else {
                throw new \RuntimeException('INVALID_INVITATION_RESPONSE');
            }

            $this->recordSystemLog($actorAccountId, $action, 'Loimoidoibong', $invitationId, $ipAddress, $logNote);

            $db->commit();

            return $this->teamInvitationForAthlete($athleteId, $invitationId);
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function teamsForAthlete(int $athleteId, array $filters = []): array
    {
        [$where, $bindings] = $this->teamWhereForAthlete($athleteId, $filters);

        $statement = $this->db()->prepare(
            $this->baseAthleteTeamSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY
                tv.idthanhvien, tv.iddoibong, tv.idvandongvien, tv.vaitro, tv.trangthai, tv.ngaythamgia, tv.ngayroi,
                db.iddoibong, db.tendoibong, db.logo, db.diaphuong, db.trangthai,
                hlv.idhuanluyenvien, nd_hlv.hodem, nd_hlv.ten, tk_hlv.username, tk_hlv.email
             ORDER BY tv.trangthai = \'DANG_THAM_GIA\' DESC, db.tendoibong ASC'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function teamForAthlete(int $athleteId, int $teamId): ?array
    {
        $rows = $this->teamsForAthlete($athleteId, ['team_id' => $teamId]);

        return $rows[0] ?? null;
    }

    public function teamMembersForAthleteTeam(int $athleteId, int $teamId): array
    {
        if ($this->teamForAthlete($athleteId, $teamId) === null) {
            return [];
        }

        $statement = $this->db()->prepare(
            "SELECT
                tv.idthanhvien,
                tv.iddoibong,
                tv.idvandongvien,
                tv.vaitro,
                tv.trangthai,
                tv.ngaythamgia,
                tv.ngayroi,
                vdv.mavandongvien,
                vdv.vitri,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                tk.username,
                tk.email,
                tk.sodienthoai
             FROM Thanhviendoibong tv
             JOIN Vandongvien vdv ON vdv.idvandongvien = tv.idvandongvien
             JOIN Nguoidung nd ON nd.idnguoidung = vdv.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             WHERE tv.iddoibong = :team_id
             ORDER BY tv.trangthai = 'DANG_THAM_GIA' DESC, tv.vaitro, hoten"
        );
        $statement->execute(['team_id' => $teamId]);

        return $statement->fetchAll();
    }

    public function confirmTeamMembership(
        int $athleteId,
        int $memberId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): ?array {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $member = $this->first(
                "SELECT tv.idthanhvien, tv.iddoibong, tv.idvandongvien, tv.trangthai, db.tendoibong
                 FROM Thanhviendoibong tv
                 JOIN Doibong db ON db.iddoibong = tv.iddoibong
                 WHERE tv.idthanhvien = :member_id
                   AND tv.idvandongvien = :athlete_id
                 LIMIT 1
                 FOR UPDATE",
                [
                    'member_id' => $memberId,
                    'athlete_id' => $athleteId,
                ]
            );

            if ($member === null) {
                $db->rollBack();
                return null;
            }

            if ((string) $member['trangthai'] !== 'CHO_XAC_NHAN') {
                throw new \RuntimeException('MEMBERSHIP_NOT_PENDING');
            }

            $statement = $db->prepare(
                "UPDATE Thanhviendoibong
                 SET trangthai = 'DANG_THAM_GIA',
                     ngaythamgia = CURRENT_DATE,
                     ngayroi = NULL
                 WHERE idthanhvien = :member_id
                   AND idvandongvien = :athlete_id
                   AND trangthai = 'CHO_XAC_NHAN'"
            );
            $statement->execute([
                'member_id' => $memberId,
                'athlete_id' => $athleteId,
            ]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('MEMBERSHIP_NOT_PENDING');
            }

            $statement = $db->prepare(
                "INSERT INTO Lichsuthanhviendoibong (idthanhvien, hanhdong, ghichu, idnguoithuchien)
                 VALUES (:member_id, 'THEM_THANH_VIEN', :note, :actor_account_id)"
            );
            $statement->execute([
                'member_id' => $memberId,
                'note' => 'VDV xac nhan tham gia doi bong',
                'actor_account_id' => $actorAccountId,
            ]);

            $this->recordSystemLog($actorAccountId, 'Xac nhan thanh vien doi bong', 'Thanhviendoibong', $memberId, $ipAddress, $logNote);

            $db->commit();

            return $this->first(
                "SELECT
                    tv.idthanhvien,
                    tv.iddoibong,
                    tv.idvandongvien,
                    tv.vaitro,
                    tv.trangthai,
                    tv.ngaythamgia,
                    tv.ngayroi,
                    db.tendoibong
                 FROM Thanhviendoibong tv
                 JOIN Doibong db ON db.iddoibong = tv.iddoibong
                 WHERE tv.idthanhvien = :member_id
                   AND tv.idvandongvien = :athlete_id
                 LIMIT 1",
                [
                    'member_id' => $memberId,
                    'athlete_id' => $athleteId,
                ]
            );
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function teamTournamentsForAthleteTeam(int $athleteId, int $teamId): array
    {
        if ($this->teamForAthlete($athleteId, $teamId) === null) {
            return [];
        }

        $statement = $this->db()->prepare(
            "SELECT
                dk.iddangky,
                dk.idgiaidau,
                dk.trangthai AS trangthaidangky,
                dk.ngaydangky,
                gd.tengiaidau,
                gd.mota,
                gd.trangthai AS trangthaigiaindau,
                gd.thoigianbatdau,
                gd.thoigianketthuc
             FROM Dangkygiaidau dk
             JOIN Giaidau gd ON gd.idgiaidau = dk.idgiaidau
             WHERE dk.iddoibong = :team_id
             ORDER BY gd.ngaytao DESC, dk.iddangky DESC"
        );
        $statement->execute(['team_id' => $teamId]);

        return $statement->fetchAll();
    }

    public function lineupsForAthlete(int $athleteId, array $filters = []): array
    {
        [$where, $bindings] = $this->lineupWhereForAthlete($athleteId, $filters);

        $statement = $this->db()->prepare(
            $this->baseAthleteLineupSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY
                dh.iddoihinh, dh.iddoibong, dh.idgiaidau, dh.tendoihinh, dh.trangthai, dh.ngaytao, dh.ngaycapnhat,
                db.tendoibong, gd.tengiaidau, gd.trangthai
             ORDER BY gd.ngaytao DESC, dh.ngaycapnhat DESC, dh.ngaytao DESC'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function lineupForAthlete(int $athleteId, int $lineupId): ?array
    {
        $rows = $this->lineupsForAthlete($athleteId, ['lineup_id' => $lineupId]);

        return $rows[0] ?? null;
    }

    public function lineupDetailsForAthlete(int $athleteId, int $lineupId): array
    {
        $lineup = $this->lineupForAthlete($athleteId, $lineupId);

        if ($lineup === null) {
            return [];
        }

        $statement = $this->db()->prepare(
            "SELECT
                ctdh.idchitietdoihinh,
                ctdh.iddoihinh,
                ctdh.idvandongvien,
                ctdh.vitri,
                ctdh.sothutu,
                ctdh.ghichu,
                vdv.mavandongvien,
                vdv.trangthaidaugiai,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                tk.username
             FROM Chitietdoihinh ctdh
             JOIN Vandongvien vdv ON vdv.idvandongvien = ctdh.idvandongvien
             JOIN Nguoidung nd ON nd.idnguoidung = vdv.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             WHERE ctdh.iddoihinh = :lineup_id
             ORDER BY ctdh.sothutu IS NULL, ctdh.sothutu ASC, hoten ASC"
        );
        $statement->execute(['lineup_id' => $lineupId]);

        return $statement->fetchAll();
    }

    public function scheduleForAthlete(int $athleteId, array $filters = []): array
    {
        (new Lichthidau())->syncSupervisorAttendanceStatuses();

        [$where, $bindings] = $this->scheduleWhereForAthlete($athleteId, $filters);

        $statement = $this->db()->prepare(
            $this->baseAthleteScheduleSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY td.thoigianbatdau ASC, td.idtrandau ASC'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function matchForAthlete(int $athleteId, int $matchId): ?array
    {
        $rows = $this->scheduleForAthlete($athleteId, ['match_id' => $matchId]);

        return $rows[0] ?? null;
    }

    public function statsForAthlete(int $athleteId, array $filters = []): array
    {
        [$where, $bindings] = $this->statsWhereForAthlete($athleteId, $filters);

        $statement = $this->db()->prepare(
            $this->baseAthleteStatsSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY td.thoigianbatdau DESC, tkcn.idthongkecanhan DESC'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function statsSummaryForAthlete(int $athleteId, array $filters = []): array
    {
        [$where, $bindings] = $this->statsWhereForAthlete($athleteId, $filters);

        $statement = $this->db()->prepare(
            "SELECT
                COUNT(*) AS total_rows,
                COUNT(DISTINCT tkcn.idgiaidau) AS total_tournaments,
                COUNT(DISTINCT tkcn.idtrandau) AS total_matches,
                COALESCE(SUM(tkcn.sodiem), 0) AS total_points,
                COALESCE(SUM(tkcn.solanphatbong), 0) AS total_serves,
                COALESCE(SUM(tkcn.solanchanbong), 0) AS total_blocks,
                COALESCE(SUM(tkcn.solanghidiem), 0) AS total_scores
             FROM Thongkecanhan tkcn
             JOIN Giaidau gd ON gd.idgiaidau = tkcn.idgiaidau
             JOIN Trandau td ON td.idtrandau = tkcn.idtrandau
             LEFT JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             JOIN Doibong db1 ON db1.iddoibong = td.iddoibong1
             JOIN Doibong db2 ON db2.iddoibong = td.iddoibong2
             WHERE " . implode(' AND ', $where)
        );
        $statement->execute($bindings);
        $row = $statement->fetch() ?: [];

        return [
            'total_rows' => (int) ($row['total_rows'] ?? 0),
            'total_tournaments' => (int) ($row['total_tournaments'] ?? 0),
            'total_matches' => (int) ($row['total_matches'] ?? 0),
            'total_points' => (int) ($row['total_points'] ?? 0),
            'total_serves' => (int) ($row['total_serves'] ?? 0),
            'total_blocks' => (int) ($row['total_blocks'] ?? 0),
            'total_scores' => (int) ($row['total_scores'] ?? 0),
        ];
    }

    public function profileChangeRequestsForAthlete(int $athleteId, array $filters = []): array
    {
        [$where, $bindings] = $this->profileChangeWhereForAthlete($athleteId, $filters);

        $statement = $this->db()->prepare(
            $this->baseAthleteProfileChangeSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY yc.ngaygui DESC, yc.idyeucaucapnhat DESC'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function profileChangeRequestForAthlete(int $athleteId, int $requestId): ?array
    {
        $rows = $this->profileChangeRequestsForAthlete($athleteId, ['request_id' => $requestId]);

        return $rows[0] ?? null;
    }

    public function hasPendingProfileChangeRequest(int $athleteId, string $targetTable, string $field): bool
    {
        return $this->first(
            "SELECT 1
             FROM Yeucaucapnhathoso yc
             JOIN Vandongvien vdv ON vdv.idnguoidung = yc.idnguoidung
             WHERE vdv.idvandongvien = :athlete_id
               AND yc.banglienquan = :target_table
               AND yc.truongcapnhat = :field
               AND yc.trangthai = 'CHO_DUYET'
             LIMIT 1",
            [
                'athlete_id' => $athleteId,
                'target_table' => $targetTable,
                'field' => $field,
            ]
        ) !== null;
    }

    public function createProfileChangeRequestForAthlete(
        int $athleteId,
        string $targetTable,
        string $field,
        ?string $oldValue,
        string $newValue,
        ?string $reason,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): int {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $athlete = $this->findAthleteIdentity($athleteId);

            if ($athlete === null) {
                throw new \RuntimeException('ATHLETE_NOT_FOUND');
            }

            $statement = $db->prepare(
                "INSERT INTO Yeucaucapnhathoso
                    (idnguoidung, banglienquan, truongcapnhat, giatricu, giatrimoi, lydo, trangthai)
                 VALUES
                    (:user_id, :target_table, :field, :old_value, :new_value, :reason, 'CHO_DUYET')"
            );
            $statement->execute([
                'user_id' => (int) $athlete['idnguoidung'],
                'target_table' => $targetTable,
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'reason' => $reason,
            ]);

            $requestId = (int) $db->lastInsertId();

            $this->recordStatusHistory('YEU_CAU_XAC_NHAN', $requestId, null, 'CHO_DUYET', 'VDV gui yeu cau sua id ca nhan', $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Gui yeu cau sua id ca nhan VDV', 'Yeucaucapnhathoso', $requestId, $ipAddress, $logNote);

            $db->commit();

            return $requestId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function leaveRequestsForAthlete(int $athleteId, array $filters = []): array
    {
        [$where, $bindings] = $this->leaveWhereForAthlete($athleteId, $filters);

        $statement = $this->db()->prepare(
            $this->baseAthleteLeaveSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY dnv.ngaygui DESC, dnv.iddonnghi DESC'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function leaveRequestForAthlete(int $athleteId, int $leaveId): ?array
    {
        $rows = $this->leaveRequestsForAthlete($athleteId, ['leave_id' => $leaveId]);

        return $rows[0] ?? null;
    }

    public function leaveRequestStatsForAthlete(int $athleteId, array $filters = []): array
    {
        [$where, $bindings] = $this->leaveWhereForAthlete($athleteId, $filters);

        $statement = $this->db()->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN dnv.trangthai = 'CHO_DUYET' THEN 1 ELSE 0 END) AS cho_duyet,
                SUM(CASE WHEN dnv.trangthai = 'DA_DUYET' THEN 1 ELSE 0 END) AS da_duyet,
                SUM(CASE WHEN dnv.trangthai = 'TU_CHOI' THEN 1 ELSE 0 END) AS tu_choi,
                SUM(CASE WHEN dnv.trangthai = 'DA_HUY' THEN 1 ELSE 0 END) AS da_huy,
                SUM(DATEDIFF(dnv.denngay, dnv.tungay) + 1) AS total_days,
                SUM(CASE WHEN dnv.trangthai = 'DA_DUYET' THEN DATEDIFF(dnv.denngay, dnv.tungay) + 1 ELSE 0 END) AS approved_days
             FROM Donnghivandongvien dnv
             LEFT JOIN Trandau td ON td.idtrandau = dnv.idtrandau
             LEFT JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             LEFT JOIN Doibong db1 ON db1.iddoibong = td.iddoibong1
             LEFT JOIN Doibong db2 ON db2.iddoibong = td.iddoibong2
             WHERE " . implode(' AND ', $where)
        );
        $statement->execute($bindings);
        $row = $statement->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'CHO_DUYET' => (int) ($row['cho_duyet'] ?? 0),
            'DA_DUYET' => (int) ($row['da_duyet'] ?? 0),
            'TU_CHOI' => (int) ($row['tu_choi'] ?? 0),
            'DA_HUY' => (int) ($row['da_huy'] ?? 0),
            'total_days' => (int) ($row['total_days'] ?? 0),
            'approved_days' => (int) ($row['approved_days'] ?? 0),
        ];
    }

    public function hasOverlappingAthleteLeaveRequest(int $athleteId, string $fromDate, string $toDate, ?int $exceptLeaveId = null): bool
    {
        $where = [
            'idvandongvien = :athlete_id',
            "trangthai IN ('CHO_DUYET','DA_DUYET')",
            'tungay <= :to_date',
            'denngay >= :from_date',
        ];
        $bindings = [
            'athlete_id' => $athleteId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];

        if ($exceptLeaveId !== null) {
            $where[] = 'iddonnghi <> :except_leave_id';
            $bindings['except_leave_id'] = $exceptLeaveId;
        }

        return $this->first(
            'SELECT 1
             FROM Donnghivandongvien
             WHERE ' . implode(' AND ', $where) . '
             LIMIT 1',
            $bindings
        ) !== null;
    }

    public function createAthleteLeaveRequest(
        int $athleteId,
        ?int $matchId,
        string $fromDate,
        string $toDate,
        string $reason,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): int {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "INSERT INTO Donnghivandongvien
                    (idvandongvien, idtrandau, tungay, denngay, lydo, trangthai)
                 VALUES
                    (:athlete_id, :match_id, :from_date, :to_date, :reason, 'CHO_DUYET')"
            );
            $statement->execute([
                'athlete_id' => $athleteId,
                'match_id' => $matchId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'reason' => $reason,
            ]);

            $leaveId = (int) $db->lastInsertId();

            $this->recordSystemLog($actorAccountId, 'Xin nghi phep thi dau VDV', 'Donnghivandongvien', $leaveId, $ipAddress, $logNote);

            $db->commit();

            return $leaveId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function cancelAthleteLeaveRequest(
        int $athleteId,
        int $leaveId,
        string $reason,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): ?array {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $leave = $this->first(
                $this->baseAthleteLeaveSelect() . '
                 WHERE dnv.idvandongvien = :athlete_id
                   AND dnv.iddonnghi = :leave_id
                 LIMIT 1
                 FOR UPDATE',
                [
                    'athlete_id' => $athleteId,
                    'leave_id' => $leaveId,
                ]
            );

            if ($leave === null) {
                $db->rollBack();
                return null;
            }

            if ((string) $leave['trangthai'] !== 'CHO_DUYET') {
                throw new \RuntimeException('LEAVE_NOT_CANCELABLE');
            }

            $statement = $db->prepare(
                "UPDATE Donnghivandongvien
                 SET trangthai = 'DA_HUY',
                     ngayxuly = CURRENT_TIMESTAMP,
                     idnguoixuly = :actor_account_id
                 WHERE iddonnghi = :leave_id
                   AND idvandongvien = :athlete_id
                   AND trangthai = 'CHO_DUYET'"
            );
            $statement->execute([
                'actor_account_id' => $actorAccountId,
                'leave_id' => $leaveId,
                'athlete_id' => $athleteId,
            ]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('LEAVE_NOT_CANCELABLE');
            }

            $this->recordSystemLog($actorAccountId, 'Huy don nghi phep thi dau VDV', 'Donnghivandongvien', $leaveId, $ipAddress, $logNote);

            $db->commit();

            return $this->leaveRequestForAthlete($athleteId, $leaveId);
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function recordAthleteSystemLog(
        int $actorAccountId,
        string $action,
        string $targetTable,
        ?int $targetId,
        ?string $ipAddress,
        ?string $note
    ): void {
        $this->recordSystemLog($actorAccountId, $action, $targetTable, $targetId, $ipAddress, $note);
    }

    public function updateCompetitionQualification(
        int $athleteId,
        string $oldStatus,
        string $newStatus,
        ?int $requestId,
        ?string $requestStatus,
        string $reason,
        int $actorAccountId,
        ?string $ipAddress,
        string $systemAction,
        string $logNote
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "UPDATE Vandongvien
                 SET trangthaidaugiai = :new_status
                 WHERE idvandongvien = :athlete_id
                   AND trangthaidaugiai = :old_status"
            );
            $statement->execute([
                'new_status' => $newStatus,
                'athlete_id' => $athleteId,
                'old_status' => $oldStatus,
            ]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('ATHLETE_QUALIFICATION_NOT_UPDATED');
            }

            $this->syncAccountStatusForQualification($athleteId, $oldStatus, $newStatus, $reason, $actorAccountId);

            if ($requestId !== null && $requestStatus !== null) {
                $statement = $db->prepare(
                    "UPDATE Yeucauxacnhan
                     SET trangthai = :request_status,
                         ngayxuly = CURRENT_TIMESTAMP,
                         ghichu = :reason
                     WHERE idyeucau = :request_id
                       AND trangthai = 'CHO_DUYET'"
                );
                $statement->execute([
                    'request_status' => $requestStatus,
                    'reason' => $reason,
                    'request_id' => $requestId,
                ]);

                if ($statement->rowCount() === 1) {
                    $this->recordStatusHistory('YEU_CAU_XAC_NHAN', $requestId, 'CHO_DUYET', $requestStatus, $reason, $actorAccountId);
                }
            }

            $this->recordSystemLog($actorAccountId, $systemAction, 'Vandongvien', $athleteId, $ipAddress, $logNote);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    private function baseAthleteQuery(int $organizerId, array $filters): array
    {
        $where = ["(
            team_stats.total_memberships IS NOT NULL
            OR (
                yc.idyeucau IS NOT NULL
                AND EXISTS (
                    SELECT 1
                    FROM Thanhviendoibong tv_scope
                    JOIN Doibong db_scope ON db_scope.iddoibong = tv_scope.iddoibong
                    WHERE tv_scope.idvandongvien = vdv.idvandongvien
                      AND tv_scope.trangthai = 'DANG_THAM_GIA'
                      AND db_scope.idkhuvucdaidien IN (SELECT scope_region_id FROM organizer_scope_regions)
                )
            )
        )"];
        $bindings = [
            'organizer_scope_seed_id' => $organizerId,
            'organizer_request_id' => $organizerId,
            'organizer_team_id' => $organizerId,
        ];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(vdv.mavandongvien LIKE :keyword
                OR vdv.vitri LIKE :keyword
                OR tk.username LIKE :keyword
                OR tk.email LIKE :keyword
                OR tk.sodienthoai LIKE :keyword
                OR nd.cccd LIKE :keyword
                OR TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) LIKE :keyword)";
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'vdv.trangthaidaugiai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['account_status'] ?? '') !== '') {
            $where[] = 'tk.trangthai = :account_status';
            $bindings['account_status'] = $filters['account_status'];
        }

        if (($filters['request_status'] ?? '') !== '') {
            $where[] = 'yc.trangthai = :request_status';
            $bindings['request_status'] = $filters['request_status'];
        }

        if (($filters['request_presence'] ?? '') === 'HAS_REQUEST') {
            $where[] = 'yc.idyeucau IS NOT NULL';
        }

        if (($filters['request_presence'] ?? '') === 'NO_REQUEST') {
            $where[] = 'yc.idyeucau IS NULL';
        }

        if (($filters['team_id'] ?? '') !== '') {
            $where[] = "EXISTS (
                SELECT 1
                FROM Thanhviendoibong tv_filter
                JOIN Doibong db_filter ON db_filter.iddoibong = tv_filter.iddoibong
                JOIN Dangkygiaidau dk_filter ON dk_filter.iddoibong = db_filter.iddoibong
                JOIN Giaidau gd_filter ON gd_filter.idgiaidau = dk_filter.idgiaidau
                WHERE gd_filter.idbantochuc = :organizer_team_filter_id
                  AND tv_filter.idvandongvien = vdv.idvandongvien
                  AND tv_filter.iddoibong = :team_id
            )";
            $bindings['organizer_team_filter_id'] = $organizerId;
            $bindings['team_id'] = (int) $filters['team_id'];
        }

        if (($filters['tournament_id'] ?? '') !== '') {
            $where[] = "EXISTS (
                SELECT 1
                FROM Thanhviendoibong tv_tournament_filter
                JOIN Doibong db_tournament_filter ON db_tournament_filter.iddoibong = tv_tournament_filter.iddoibong
                JOIN Dangkygiaidau dk_tournament_filter ON dk_tournament_filter.iddoibong = db_tournament_filter.iddoibong
                JOIN Giaidau gd_tournament_filter ON gd_tournament_filter.idgiaidau = dk_tournament_filter.idgiaidau
                WHERE gd_tournament_filter.idbantochuc = :organizer_tournament_filter_id
                  AND tv_tournament_filter.idvandongvien = vdv.idvandongvien
                  AND gd_tournament_filter.idgiaidau = :tournament_id
            )";
            $bindings['organizer_tournament_filter_id'] = $organizerId;
            $bindings['tournament_id'] = (int) $filters['tournament_id'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'COALESCE(yc.ngaygui, nd.ngaytao) >= :from_date';
            $bindings['from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'COALESCE(yc.ngaygui, nd.ngaytao) <= :to_date';
            $bindings['to_date'] = $filters['to'] . ' 23:59:59';
        }

        $sql = "WITH RECURSIVE organizer_scope_regions(scope_region_id) AS (
                SELECT kv_scope.idkhuvuc
                FROM Khuvuc kv_scope
                JOIN Bantochuc btc_scope_seed
                  ON btc_scope_seed.idkhuvucquanly = kv_scope.idkhuvuc
                 AND btc_scope_seed.idbantochuc = :organizer_scope_seed_id
                WHERE kv_scope.trangthai = 'HOAT_DONG'
                UNION ALL
                SELECT kv_child.idkhuvuc
                FROM Khuvuc kv_child
                JOIN organizer_scope_regions scope_parent
                  ON scope_parent.scope_region_id = kv_child.idkhuvuccha
                WHERE kv_child.trangthai = 'HOAT_DONG'
             )
             SELECT
                vdv.idvandongvien,
                vdv.idnguoidung,
                vdv.mavandongvien,
                vdv.chieucao,
                vdv.cannang,
                vdv.vitri,
                vdv.trangthaidaugiai,
                nd.idtaikhoan,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                nd.gioitinh,
                nd.ngaysinh,
                nd.quequan,
                nd.diachi,
                nd.avatar,
                nd.cccd,
                nd.ngaytao AS nguoidung_ngaytao,
                nd.ngaycapnhat AS nguoidung_ngaycapnhat,
                tk.username,
                tk.email,
                tk.sodienthoai,
                tk.trangthai AS trangthai_taikhoan,
                yc.idyeucau,
                yc.noidung AS yeucau_noidung,
                yc.trangthai AS yeucau_trangthai,
                yc.ngaygui AS yeucau_ngaygui,
                yc.ngayxuly AS yeucau_ngayxuly,
                yc.ghichu AS yeucau_ghichu,
                COALESCE(yc.ngaygui, nd.ngaytao) AS ngaythamchieu,
                COALESCE(team_stats.total_memberships, 0) AS total_memberships,
                COALESCE(team_stats.active_memberships, 0) AS active_memberships,
                COALESCE(team_stats.total_tournaments, 0) AS total_tournaments,
                team_stats.team_names,
                team_stats.active_team_names,
                team_stats.active_coach_names,
                team_stats.tournament_names,
                team_stats.latest_join_date
             FROM Vandongvien vdv
             JOIN Nguoidung nd ON nd.idnguoidung = vdv.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             LEFT JOIN (
                SELECT
                    idnguoigui,
                    MAX(idyeucau) AS latest_request_id
                FROM Yeucauxacnhan
                WHERE loainguoigui = 'VAN_DONG_VIEN'
                  AND loainguoinhan = 'BAN_TO_CHUC'
                  AND loaixacnhan = 'XAC_NHAN_VDV'
                  AND idnguoinhan = :organizer_request_id
                GROUP BY idnguoigui
             ) latest_yc ON latest_yc.idnguoigui = vdv.idvandongvien
             LEFT JOIN Yeucauxacnhan yc ON yc.idyeucau = latest_yc.latest_request_id
             LEFT JOIN (
                SELECT
                    tv.idvandongvien,
                    COUNT(DISTINCT tv.idthanhvien) AS total_memberships,
                    SUM(CASE WHEN tv.trangthai = 'DANG_THAM_GIA' THEN 1 ELSE 0 END) AS active_memberships,
                    COUNT(DISTINCT gd.idgiaidau) AS total_tournaments,
                    GROUP_CONCAT(DISTINCT db.tendoibong ORDER BY db.tendoibong SEPARATOR ', ') AS team_names,
                    GROUP_CONCAT(DISTINCT CASE WHEN tv.trangthai = 'DANG_THAM_GIA' THEN db.tendoibong END ORDER BY db.tendoibong SEPARATOR ', ') AS active_team_names,
                    GROUP_CONCAT(
                        DISTINCT CASE
                            WHEN tv.trangthai = 'DANG_THAM_GIA'
                            THEN COALESCE(NULLIF(TRIM(CONCAT(COALESCE(nd_hlv.hodem, ''), ' ', COALESCE(nd_hlv.ten, ''))), ''), tk_hlv.username)
                        END
                        ORDER BY COALESCE(NULLIF(TRIM(CONCAT(COALESCE(nd_hlv.hodem, ''), ' ', COALESCE(nd_hlv.ten, ''))), ''), tk_hlv.username)
                        SEPARATOR ', '
                    ) AS active_coach_names,
                    GROUP_CONCAT(DISTINCT gd.tengiaidau ORDER BY gd.tengiaidau SEPARATOR ', ') AS tournament_names,
                    MAX(tv.ngaythamgia) AS latest_join_date
                FROM Thanhviendoibong tv
                JOIN Doibong db ON db.iddoibong = tv.iddoibong
                JOIN Huanluyenvien hlv_team ON hlv_team.idhuanluyenvien = db.idhuanluyenvien
                JOIN Nguoidung nd_hlv ON nd_hlv.idnguoidung = hlv_team.idnguoidung
                JOIN Taikhoan tk_hlv ON tk_hlv.idtaikhoan = nd_hlv.idtaikhoan
                JOIN Dangkygiaidau dk ON dk.iddoibong = db.iddoibong
                JOIN Giaidau gd ON gd.idgiaidau = dk.idgiaidau
                WHERE gd.idbantochuc = :organizer_team_id
                  AND db.idkhuvucdaidien IN (SELECT scope_region_id FROM organizer_scope_regions)
                GROUP BY tv.idvandongvien
             ) team_stats ON team_stats.idvandongvien = vdv.idvandongvien
             WHERE " . implode(' AND ', $where);

        return [$sql, $bindings];
    }

    private function acceptTeamInvitation(array $invitation, int $actorAccountId): void
    {
        $db = $this->db();
        $athleteId = (int) $invitation['idvandongvien'];
        $teamId = (int) $invitation['iddoibong'];

        $activeOtherMembership = $this->first(
            "SELECT 1
             FROM Thanhviendoibong
             WHERE idvandongvien = :athlete_id
               AND iddoibong <> :team_id
               AND trangthai = 'DANG_THAM_GIA'
             LIMIT 1
             FOR UPDATE",
            [
                'athlete_id' => $athleteId,
                'team_id' => $teamId,
            ]
        );

        if ($activeOtherMembership !== null) {
            throw new \RuntimeException('ATHLETE_ALREADY_IN_TEAM');
        }

        $statement = $db->prepare(
            "UPDATE Loimoidoibong
             SET trangthai = 'DONG_Y',
                 ngayphanhoi = CURRENT_TIMESTAMP
             WHERE idloimoi = :invitation_id
               AND idvandongvien = :athlete_id
               AND trangthai = 'CHO_PHAN_HOI'"
        );
        $statement->execute([
            'invitation_id' => (int) $invitation['idloimoi'],
            'athlete_id' => $athleteId,
        ]);

        if ($statement->rowCount() !== 1) {
            throw new \RuntimeException('INVITATION_NOT_PENDING');
        }

        $membership = $this->first(
            "SELECT idthanhvien, trangthai
             FROM Thanhviendoibong
             WHERE iddoibong = :team_id
               AND idvandongvien = :athlete_id
             LIMIT 1
             FOR UPDATE",
            [
                'team_id' => $teamId,
                'athlete_id' => $athleteId,
            ]
        );

        if ($membership === null) {
            $statement = $db->prepare(
                "INSERT INTO Thanhviendoibong
                    (iddoibong, idvandongvien, vaitro, trangthai, ngaythamgia, ngayroi)
                 VALUES
                    (:team_id, :athlete_id, 'THANH_VIEN', 'DANG_THAM_GIA', CURRENT_DATE, NULL)"
            );
            $statement->execute([
                'team_id' => $teamId,
                'athlete_id' => $athleteId,
            ]);
            $memberId = (int) $db->lastInsertId();
        } else {
            $memberId = (int) $membership['idthanhvien'];
            $statement = $db->prepare(
                "UPDATE Thanhviendoibong
                 SET trangthai = 'DANG_THAM_GIA',
                     ngaythamgia = CURRENT_DATE,
                     ngayroi = NULL
                 WHERE idthanhvien = :member_id"
            );
            $statement->execute(['member_id' => $memberId]);
        }

        $statement = $db->prepare(
            "INSERT INTO Lichsuthanhviendoibong (idthanhvien, hanhdong, ghichu, idnguoithuchien)
             VALUES (:member_id, 'THEM_THANH_VIEN', :note, :actor_account_id)"
        );
        $statement->execute([
            'member_id' => $memberId,
            'note' => 'VDV dong y loi moi tham gia doi bong',
            'actor_account_id' => $actorAccountId,
        ]);
    }

    private function findAthleteIdentity(int $athleteId): ?array
    {
        return $this->first(
            "SELECT
                vdv.idvandongvien,
                vdv.idnguoidung,
                vdv.mavandongvien,
                vdv.chieucao,
                vdv.cannang,
                vdv.vitri,
                nd.cccd,
                nd.idtaikhoan,
                tk.username,
                tk.email,
                tk.sodienthoai
             FROM Vandongvien vdv
             JOIN Nguoidung nd ON nd.idnguoidung = vdv.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             WHERE vdv.idvandongvien = :athlete_id
             LIMIT 1",
            ['athlete_id' => $athleteId]
        );
    }

    private function baseTeamInvitationSelect(): string
    {
        return "SELECT
                lm.idloimoi,
                lm.iddoibong,
                lm.idvandongvien,
                lm.idhuanluyenvien,
                lm.noidung,
                lm.trangthai,
                CASE
                    WHEN lm.trangthai = 'CHO_PHAN_HOI' AND lm.ngayhethan < CURRENT_TIMESTAMP THEN 'HET_HAN'
                    ELSE lm.trangthai
                END AS trangthai_hienthi,
                lm.ngaygui,
                lm.ngayphanhoi,
                lm.ngayhethan,
                db.tendoibong,
                db.logo AS doibong_logo,
                db.diaphuong,
                db.trangthai AS doibong_trangthai,
                tv.idthanhvien,
                tv.trangthai AS trangthaithanhvien,
                TRIM(CONCAT(COALESCE(nd_hlv.hodem, ''), ' ', COALESCE(nd_hlv.ten, ''))) AS huanluyenvien_hoten,
                tk_hlv.username AS huanluyenvien_username,
                tk_hlv.email AS huanluyenvien_email
             FROM Loimoidoibong lm
             JOIN Doibong db ON db.iddoibong = lm.iddoibong
             JOIN Huanluyenvien hlv ON hlv.idhuanluyenvien = lm.idhuanluyenvien
             JOIN Nguoidung nd_hlv ON nd_hlv.idnguoidung = hlv.idnguoidung
             JOIN Taikhoan tk_hlv ON tk_hlv.idtaikhoan = nd_hlv.idtaikhoan
             LEFT JOIN Thanhviendoibong tv ON tv.iddoibong = lm.iddoibong
                AND tv.idvandongvien = lm.idvandongvien";
    }

    private function teamInvitationWhere(int $athleteId, array $filters): array
    {
        $where = ['lm.idvandongvien = :athlete_id'];
        $bindings = ['athlete_id' => $athleteId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(db.tendoibong LIKE :keyword OR db.diaphuong LIKE :keyword OR lm.noidung LIKE :keyword OR tk_hlv.username LIKE :keyword)';
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = "(CASE
                WHEN lm.trangthai = 'CHO_PHAN_HOI' AND lm.ngayhethan < CURRENT_TIMESTAMP THEN 'HET_HAN'
                ELSE lm.trangthai
            END) = :status";
            $bindings['status'] = $filters['status'];
        }

        if (($filters['team_id'] ?? '') !== '') {
            $where[] = 'lm.iddoibong = :team_id';
            $bindings['team_id'] = (int) $filters['team_id'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'lm.ngaygui >= :from_date';
            $bindings['from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'lm.ngaygui <= :to_date';
            $bindings['to_date'] = $filters['to'] . ' 23:59:59';
        }

        return [$where, $bindings];
    }

    private function baseAthleteTeamSelect(): string
    {
        return "SELECT
                tv.idthanhvien,
                tv.iddoibong,
                tv.idvandongvien,
                tv.vaitro AS vaitrotrongdoi,
                tv.trangthai AS trangthaithanhvien,
                tv.ngaythamgia,
                tv.ngayroi,
                db.tendoibong,
                db.logo,
                db.diaphuong,
                db.trangthai AS trangthaidoibong,
                hlv.idhuanluyenvien,
                TRIM(CONCAT(COALESCE(nd_hlv.hodem, ''), ' ', COALESCE(nd_hlv.ten, ''))) AS huanluyenvien_hoten,
                tk_hlv.username AS huanluyenvien_username,
                tk_hlv.email AS huanluyenvien_email,
                COUNT(DISTINCT gd.idgiaidau) AS total_tournaments,
                GROUP_CONCAT(DISTINCT gd.tengiaidau ORDER BY gd.tengiaidau SEPARATOR ', ') AS tournament_names
             FROM Thanhviendoibong tv
             JOIN Doibong db ON db.iddoibong = tv.iddoibong
             JOIN Huanluyenvien hlv ON hlv.idhuanluyenvien = db.idhuanluyenvien
             JOIN Nguoidung nd_hlv ON nd_hlv.idnguoidung = hlv.idnguoidung
             JOIN Taikhoan tk_hlv ON tk_hlv.idtaikhoan = nd_hlv.idtaikhoan
             LEFT JOIN Dangkygiaidau dk ON dk.iddoibong = db.iddoibong
             LEFT JOIN Giaidau gd ON gd.idgiaidau = dk.idgiaidau";
    }

    private function teamWhereForAthlete(int $athleteId, array $filters): array
    {
        $where = ['tv.idvandongvien = :athlete_id'];
        $bindings = ['athlete_id' => $athleteId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(db.tendoibong LIKE :keyword OR db.diaphuong LIKE :keyword OR gd.tengiaidau LIKE :keyword)';
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'tv.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['team_status'] ?? '') !== '') {
            $where[] = 'db.trangthai = :team_status';
            $bindings['team_status'] = $filters['team_status'];
        }

        if (($filters['team_id'] ?? '') !== '') {
            $where[] = 'db.iddoibong = :team_id';
            $bindings['team_id'] = (int) $filters['team_id'];
        }

        return [$where, $bindings];
    }

    private function baseAthleteLineupSelect(): string
    {
        return "SELECT
                dh.iddoihinh,
                dh.iddoibong,
                dh.idgiaidau,
                dh.tendoihinh,
                dh.trangthai,
                dh.ngaytao,
                dh.ngaycapnhat,
                db.tendoibong,
                gd.tengiaidau,
                gd.trangthai AS trangthaigiaindau,
                SUM(CASE WHEN ctdh.idvandongvien = tv.idvandongvien THEN 1 ELSE 0 END) AS current_athlete_in_lineup,
                COUNT(DISTINCT ctdh.idchitietdoihinh) AS total_members
             FROM Doihinh dh
             JOIN Doibong db ON db.iddoibong = dh.iddoibong
             LEFT JOIN Giaidau gd ON gd.idgiaidau = dh.idgiaidau
             JOIN Thanhviendoibong tv ON tv.iddoibong = dh.iddoibong
             LEFT JOIN Chitietdoihinh ctdh ON ctdh.iddoihinh = dh.iddoihinh";
    }

    private function lineupWhereForAthlete(int $athleteId, array $filters): array
    {
        $where = [
            'tv.idvandongvien = :athlete_id',
            "tv.trangthai = 'DANG_THAM_GIA'",
        ];
        $bindings = ['athlete_id' => $athleteId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(dh.tendoihinh LIKE :keyword OR db.tendoibong LIKE :keyword OR gd.tengiaidau LIKE :keyword)';
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'dh.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['team_id'] ?? '') !== '') {
            $where[] = 'dh.iddoibong = :team_id';
            $bindings['team_id'] = (int) $filters['team_id'];
        }

        if (($filters['tournament_id'] ?? '') !== '') {
            $where[] = 'dh.idgiaidau = :tournament_id';
            $bindings['tournament_id'] = (int) $filters['tournament_id'];
        }

        if (($filters['lineup_id'] ?? '') !== '') {
            $where[] = 'dh.iddoihinh = :lineup_id';
            $bindings['lineup_id'] = (int) $filters['lineup_id'];
        }

        return [$where, $bindings];
    }

    private function baseAthleteScheduleSelect(): string
    {
        return "SELECT
                td.idtrandau,
                td.idgiaidau,
                td.idbangdau,
                td.iddoibong1,
                td.iddoibong2,
                td.idsandau,
                td.thoigianbatdau,
                td.thoigianketthuc,
                vd.tenvongdau AS vongdau,
                td.trangthai,
                gd.tengiaidau,
                bd.tenbang,
                d1.tendoibong AS doi1,
                d2.tendoibong AS doi2,
                sd.tensandau,
                vt.diachi AS sandau_diachi,
                tv.iddoibong AS iddoibong_vdv,
                CASE WHEN td.iddoibong1 = tv.iddoibong THEN 1 ELSE 2 END AS ben_vdv,
                kq.idketqua,
                kq.iddoithang,
                kq.sosetdoi1,
                kq.sosetdoi2,
                kq.trangthai AS trangthaiketqua,
                dt.tendoibong AS doithang
             FROM Trandau td
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             LEFT JOIN Bangdau bd ON bd.idbangdau = td.idbangdau
             LEFT JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             LEFT JOIN Sandau sd ON sd.idsandau = td.idsandau
             LEFT JOIN Vitrithidau vt ON vt.idvitrithidau = sd.idvitrithidau
             JOIN Thanhviendoibong tv ON tv.iddoibong IN (td.iddoibong1, td.iddoibong2)
             LEFT JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau
             LEFT JOIN Doibong dt ON dt.iddoibong = kq.iddoithang";
    }

    private function scheduleWhereForAthlete(int $athleteId, array $filters): array
    {
        $where = [
            'tv.idvandongvien = :athlete_id',
            "tv.trangthai = 'DANG_THAM_GIA'",
        ];
        $bindings = ['athlete_id' => $athleteId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(gd.tengiaidau LIKE :keyword OR d1.tendoibong LIKE :keyword OR d2.tendoibong LIKE :keyword OR sd.tensandau LIKE :keyword OR bd.tenbang LIKE :keyword)';
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'td.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['tournament_id'] ?? '') !== '') {
            $where[] = 'td.idgiaidau = :tournament_id';
            $bindings['tournament_id'] = (int) $filters['tournament_id'];
        }

        if (($filters['team_id'] ?? '') !== '') {
            $where[] = 'tv.iddoibong = :team_id';
            $bindings['team_id'] = (int) $filters['team_id'];
        }

        if (($filters['match_id'] ?? '') !== '') {
            $where[] = 'td.idtrandau = :match_id';
            $bindings['match_id'] = (int) $filters['match_id'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'td.thoigianbatdau >= :from_date';
            $bindings['from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'td.thoigianbatdau <= :to_date';
            $bindings['to_date'] = $filters['to'] . ' 23:59:59';
        }

        return [$where, $bindings];
    }

    private function baseAthleteStatsSelect(): string
    {
        return "SELECT
                tkcn.idthongkecanhan,
                tkcn.idvandongvien,
                tkcn.idgiaidau,
                tkcn.idtrandau,
                tkcn.sodiem,
                tkcn.solanphatbong,
                tkcn.solanchanbong,
                tkcn.solanghidiem,
                tkcn.ghichu,
                gd.tengiaidau,
                vd.tenvongdau AS vongdau,
                td.thoigianbatdau,
                td.trangthai AS trangthaitrandau,
                db1.tendoibong AS doi1,
                db2.tendoibong AS doi2
             FROM Thongkecanhan tkcn
             JOIN Giaidau gd ON gd.idgiaidau = tkcn.idgiaidau
             JOIN Trandau td ON td.idtrandau = tkcn.idtrandau
             LEFT JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             JOIN Doibong db1 ON db1.iddoibong = td.iddoibong1
             JOIN Doibong db2 ON db2.iddoibong = td.iddoibong2";
    }

    private function statsWhereForAthlete(int $athleteId, array $filters): array
    {
        $where = ['tkcn.idvandongvien = :athlete_id'];
        $bindings = ['athlete_id' => $athleteId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(gd.tengiaidau LIKE :keyword OR db1.tendoibong LIKE :keyword OR db2.tendoibong LIKE :keyword OR vd.tenvongdau LIKE :keyword OR tkcn.ghichu LIKE :keyword)';
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        if (($filters['tournament_id'] ?? '') !== '') {
            $where[] = 'tkcn.idgiaidau = :tournament_id';
            $bindings['tournament_id'] = (int) $filters['tournament_id'];
        }

        if (($filters['match_id'] ?? '') !== '') {
            $where[] = 'tkcn.idtrandau = :match_id';
            $bindings['match_id'] = (int) $filters['match_id'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'td.thoigianbatdau >= :from_date';
            $bindings['from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'td.thoigianbatdau <= :to_date';
            $bindings['to_date'] = $filters['to'] . ' 23:59:59';
        }

        return [$where, $bindings];
    }

    private function baseAthleteProfileChangeSelect(): string
    {
        return "SELECT
                yc.idyeucaucapnhat,
                yc.idnguoidung,
                yc.banglienquan,
                yc.truongcapnhat,
                yc.giatricu,
                yc.giatrimoi,
                yc.lydo,
                yc.trangthai,
                yc.ngaygui,
                yc.ngayxuly,
                vdv.idvandongvien,
                vdv.mavandongvien,
                nd.cccd,
                tk.username,
                tk.email,
                tk.sodienthoai
             FROM Yeucaucapnhathoso yc
             JOIN Vandongvien vdv ON vdv.idnguoidung = yc.idnguoidung
             JOIN Nguoidung nd ON nd.idnguoidung = vdv.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan";
    }

    private function profileChangeWhereForAthlete(int $athleteId, array $filters): array
    {
        $where = ['vdv.idvandongvien = :athlete_id'];
        $bindings = ['athlete_id' => $athleteId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(yc.banglienquan LIKE :keyword OR yc.truongcapnhat LIKE :keyword OR yc.giatricu LIKE :keyword OR yc.giatrimoi LIKE :keyword OR yc.lydo LIKE :keyword)';
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'yc.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['target_table'] ?? '') !== '') {
            $where[] = 'yc.banglienquan = :target_table';
            $bindings['target_table'] = $filters['target_table'];
        }

        if (($filters['field'] ?? '') !== '') {
            $where[] = 'yc.truongcapnhat = :field';
            $bindings['field'] = $filters['field'];
        }

        if (($filters['request_id'] ?? '') !== '') {
            $where[] = 'yc.idyeucaucapnhat = :request_id';
            $bindings['request_id'] = (int) $filters['request_id'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'yc.ngaygui >= :from_date';
            $bindings['from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'yc.ngaygui <= :to_date';
            $bindings['to_date'] = $filters['to'] . ' 23:59:59';
        }

        return [$where, $bindings];
    }

    private function baseAthleteLeaveSelect(): string
    {
        return "SELECT
                dnv.iddonnghi,
                dnv.idvandongvien,
                dnv.idtrandau,
                dnv.tungay,
                dnv.denngay,
                DATEDIFF(dnv.denngay, dnv.tungay) + 1 AS songay,
                dnv.lydo,
                dnv.trangthai,
                dnv.ngaygui,
                dnv.ngayxuly,
                dnv.idnguoixuly,
                gd.idgiaidau,
                gd.tengiaidau,
                vd.tenvongdau AS vongdau,
                td.thoigianbatdau,
                td.thoigianketthuc,
                td.trangthai AS trangthaitrandau,
                db1.tendoibong AS doi1,
                db2.tendoibong AS doi2
             FROM Donnghivandongvien dnv
             LEFT JOIN Trandau td ON td.idtrandau = dnv.idtrandau
             LEFT JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             LEFT JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             LEFT JOIN Doibong db1 ON db1.iddoibong = td.iddoibong1
             LEFT JOIN Doibong db2 ON db2.iddoibong = td.iddoibong2";
    }

    private function leaveWhereForAthlete(int $athleteId, array $filters): array
    {
        $where = ['dnv.idvandongvien = :athlete_id'];
        $bindings = ['athlete_id' => $athleteId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(dnv.lydo LIKE :keyword OR gd.tengiaidau LIKE :keyword OR db1.tendoibong LIKE :keyword OR db2.tendoibong LIKE :keyword)';
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'dnv.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['match_id'] ?? '') !== '') {
            $where[] = 'dnv.idtrandau = :match_id';
            $bindings['match_id'] = (int) $filters['match_id'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'dnv.tungay >= :from_date';
            $bindings['from_date'] = $filters['from'];
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'dnv.tungay <= :to_date';
            $bindings['to_date'] = $filters['to'];
        }

        if (($filters['leave_id'] ?? '') !== '') {
            $where[] = 'dnv.iddonnghi = :leave_id';
            $bindings['leave_id'] = (int) $filters['leave_id'];
        }

        return [$where, $bindings];
    }

    private function syncAccountStatusForQualification(
        int $athleteId,
        string $oldAthleteStatus,
        string $newAthleteStatus,
        string $reason,
        int $actorAccountId
    ): void {
        $account = $this->accountForAthlete($athleteId);

        if ($account === null) {
            return;
        }

        $newAccountStatus = null;

        if ($newAthleteStatus === 'DU_DIEU_KIEN') {
            $newAccountStatus = 'HOAT_DONG';
        }

        if ($newAthleteStatus === 'BI_HUY_TU_CACH' && $oldAthleteStatus === 'CHO_XAC_NHAN') {
            $newAccountStatus = 'DA_HUY';
        }

        if ($newAthleteStatus === 'BI_HUY_TU_CACH' && $oldAthleteStatus === 'DU_DIEU_KIEN') {
            $newAccountStatus = 'TAM_KHOA';
        }

        if ($newAccountStatus === null || (string) $account['trangthai'] === $newAccountStatus) {
            return;
        }

        $statement = $this->db()->prepare(
            "UPDATE Taikhoan
             SET trangthai = :new_status,
                 ngaycapnhat = CURRENT_TIMESTAMP
             WHERE idtaikhoan = :account_id"
        );
        $statement->execute([
            'new_status' => $newAccountStatus,
            'account_id' => (int) $account['idtaikhoan'],
        ]);

        $this->recordStatusHistory(
            'TAI_KHOAN',
            (int) $account['idtaikhoan'],
            (string) $account['trangthai'],
            $newAccountStatus,
            $reason,
            $actorAccountId
        );
    }

    private function accountForAthlete(int $athleteId): ?array
    {
        return $this->first(
            "SELECT tk.idtaikhoan, tk.trangthai
             FROM Vandongvien vdv
             JOIN Nguoidung nd ON nd.idnguoidung = vdv.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             WHERE vdv.idvandongvien = :athlete_id
             LIMIT 1",
            ['athlete_id' => $athleteId]
        );
    }

    private function recordSystemLog(?int $accountId, string $action, string $targetTable, ?int $targetId, ?string $ipAddress, ?string $note = null): void
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Nhatkyhethong (idtaikhoan, hanhdong, bangtacdong, iddoituong, ipaddress, ghichu)
             VALUES (:account_id, :action, :target_table, :target_id, :ip_address, :note)"
        );

        $statement->execute([
            'account_id' => $accountId,
            'action' => $action,
            'target_table' => $targetTable,
            'target_id' => $targetId,
            'ip_address' => $ipAddress,
            'note' => $note,
        ]);
    }

    private function recordStatusHistory(string $targetType, int $targetId, ?string $oldStatus, string $newStatus, ?string $reason, ?int $actorId): void
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Nhatkytrangthai (loaidoituong, iddoituong, trangthaicu, trangthaimoi, lydo, idnguoithuchien)
             VALUES (:target_type, :target_id, :old_status, :new_status, :reason, :actor_id)"
        );

        $statement->execute([
            'target_type' => $targetType,
            'target_id' => $targetId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'actor_id' => $actorId,
        ]);
    }
}
