<?php

declare(strict_types=1);

namespace App\Backend\Services\Athlete;

use App\Backend\Models\Vandongvien;

abstract class AthleteServiceSupport
{
    public function __construct(protected ?Vandongvien $athletes = null)
    {
        $this->athletes ??= new Vandongvien();
    }

    protected function activeAthlete(int $accountId, bool $requireNotRevoked = false): array
    {
        $athlete = $this->athletes->findByAccountId($accountId);

        if ($athlete === null) {
            return $this->failure('Tai khoan khong co ho so van dong vien.', 403);
        }

        if ((string) $athlete['trangthai_taikhoan'] !== 'HOAT_DONG') {
            return $this->failure('Tai khoan van dong vien khong o trang thai hoat dong.', 403);
        }

        if ($requireNotRevoked && (string) $athlete['trangthaidaugiai'] === 'BI_HUY_TU_CACH') {
            return $this->failure('Van dong vien da bi huy tu cach thi dau.', 409);
        }

        return $athlete;
    }

    protected function commonFilters(array $filters, array $statuses = []): array
    {
        $errors = [];
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $from = trim((string) ($filters['from'] ?? $filters['from_date'] ?? $filters['tungay'] ?? ''));
        $to = trim((string) ($filters['to'] ?? $filters['to_date'] ?? $filters['denngay'] ?? ''));

        if ($status !== '' && $statuses !== [] && !in_array($status, $statuses, true)) {
            $errors['status'] = 'Trang thai khong hop le.';
        }

        if ($from !== '' && !$this->isDate($from)) {
            $errors['from'] = 'Tu ngay khong hop le.';
        }

        if ($to !== '' && !$this->isDate($to)) {
            $errors['to'] = 'Den ngay khong hop le.';
        }

        if ($from !== '' && $to !== '' && $this->isDate($from) && $this->isDate($to) && $to < $from) {
            $errors['to'] = 'Den ngay phai lon hon hoac bang tu ngay.';
        }

        return [[
            'q' => trim((string) ($filters['q'] ?? $filters['keyword'] ?? '')),
            'status' => $status,
            'from' => $from,
            'to' => $to,
            'team_id' => $this->positiveIntOrEmpty($filters['team_id'] ?? $filters['iddoibong'] ?? ''),
            'tournament_id' => $this->positiveIntOrEmpty($filters['tournament_id'] ?? $filters['idgiaidau'] ?? ''),
            'match_id' => $this->positiveIntOrEmpty($filters['match_id'] ?? $filters['idtrandau'] ?? ''),
        ], $errors];
    }

    protected function positiveIntOrNull(mixed $value): ?int
    {
        $raw = trim((string) ($value ?? ''));

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $int = (int) $raw;

        return $int > 0 ? $int : null;
    }

    protected function positiveIntOrEmpty(mixed $value): string|int
    {
        $int = $this->positiveIntOrNull($value);

        return $int ?? '';
    }

    protected function isDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }

    protected function limitLogNote(string $note): string
    {
        if (strlen($note) <= 1000) {
            return $note;
        }

        return substr($note, 0, 997) . '...';
    }

    protected function failure(string $message, int $status, array $errors = []): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
        ];
    }

    protected function isFailure(array $result): bool
    {
        return isset($result['ok']) && $result['ok'] === false;
    }
}

