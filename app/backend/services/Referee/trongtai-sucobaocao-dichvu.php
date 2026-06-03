<?php

declare(strict_types=1);

namespace App\Backend\Services\Referee;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Baocaosuco;
use App\Backend\Models\Trongtai;
use Throwable;

final class RefereeIncidentReportService
{
    private const REPORT_STATUSES = ['DA_GUI', 'DA_TIEP_NHAN', 'DA_XU_LY', 'TU_CHOI'];
    private const MATCH_STATUSES = ['CHUA_DIEN_RA', 'SAP_DIEN_RA', 'TRONG_TAI_TRE_GIAM_SAT', 'DANG_DIEN_RA', 'TAM_DUNG', 'DA_KET_THUC', 'DA_HUY', 'DA_HUY_KHONG_CO_GIAM_SAT'];

    public function __construct(
        private ?Baocaosuco $reports = null,
        private ?Trongtai $referees = null
    ) {
        $this->reports ??= new Baocaosuco();
        $this->referees ??= new Trongtai();
    }

    public function all(int $accountId, array $filters = [], ?Request $request = null): array
    {
        $referee = $this->referee($accountId);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        [$normalized, $errors] = $this->filters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc bao cao su co khong hop le.', 422, $errors);
        }

        try {
            $reports = $this->reports->listForReferee((int) $referee['idtrongtai'], $normalized);
            $stats = $this->reports->statsForReferee((int) $referee['idtrongtai'], $normalized);
            $this->reports->recordIncidentReportListView(
                (int) $referee['idtrongtai'],
                $accountId,
                $request?->ip(),
                $this->listLogNote((int) $referee['idtrongtai'], $normalized, count($reports))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay danh sach bao cao su co thanh cong.',
                'reports' => $reports,
                'meta' => [
                    'referee' => $referee,
                    'filters' => $normalized,
                    'statuses' => self::REPORT_STATUSES,
                    'stats' => $stats,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay danh sach bao cao su co.', 500, [
                'database' => 'Loi doc co so du lieu.',
            ]);
        }
    }

    public function show(int $reportId, int $accountId, ?Request $request = null): array
    {
        $referee = $this->referee($accountId);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        try {
            $report = $this->reports->findForReferee((int) $referee['idtrongtai'], $reportId);

            if ($report === null) {
                return $this->failure('Khong tim thay bao cao su co.', 404);
            }

            $this->reports->recordIncidentReportDetailView(
                $reportId,
                $accountId,
                $request?->ip(),
                $this->detailLogNote((int) $referee['idtrongtai'], $report)
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay chi tiet bao cao su co thanh cong.',
                'report' => $report,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay chi tiet bao cao su co.', 500, [
                'database' => 'Loi doc co so du lieu.',
            ]);
        }
    }

    public function reportableMatches(int $accountId, array $filters = [], ?Request $request = null): array
    {
        $referee = $this->referee($accountId);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        [$normalized, $errors] = $this->matchFilters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc tran dau co the bao cao khong hop le.', 422, $errors);
        }

        try {
            $matches = $this->reports->reportableMatchesForReferee((int) $referee['idtrongtai'], $normalized);
            $this->reports->recordReportableMatchListView(
                (int) $referee['idtrongtai'],
                $accountId,
                $request?->ip(),
                $this->matchListLogNote((int) $referee['idtrongtai'], $normalized, count($matches))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay danh sach tran dau co the bao cao thanh cong.',
                'matches' => $matches,
                'meta' => [
                    'referee' => $referee,
                    'filters' => $normalized,
                    'match_statuses' => self::MATCH_STATUSES,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay danh sach tran dau co the bao cao.', 500, [
                'database' => 'Loi doc co so du lieu.',
            ]);
        }
    }

    public function create(array $payload, int $accountId, ?Request $request = null, ?int $routeMatchId = null): array
    {
        $referee = $this->referee($accountId);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        [$report, $errors] = $this->reportFromPayload($payload, (int) $referee['idtrongtai'], $routeMatchId);

        if ($errors !== []) {
            return $this->failure('Du lieu bao cao su co khong hop le.', 422, $errors);
        }

        $assignment = $this->referees->matchAssignmentDetailForReferee((int) $referee['idtrongtai'], (int) $report['idtrandau']);

        if ($assignment === null) {
            return $this->failure('Trong tai chi duoc bao cao su co cho tran dau duoc phan cong.', 403);
        }

        if ((string) $assignment['phancong_trangthai'] === 'DA_HUY') {
            return $this->failure('Phan cong tran dau da huy, khong the gui bao cao su co.', 409);
        }

        try {
            $reportId = $this->reports->createReport(
                $report,
                $accountId,
                $request?->ip(),
                $this->logNote((int) $referee['idtrongtai'], $assignment, $report)
            );
            $created = $this->reports->findForReferee((int) $referee['idtrongtai'], $reportId);

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Bao cao su co thanh cong.',
                'report' => $created,
                'meta' => [
                    'report_id' => $reportId,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the gui bao cao su co.', 500, [
                'database' => 'Loi ghi co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    private function referee(int $accountId): array
    {
        $referee = $this->referees->findByAccountId($accountId);

        if ($referee === null) {
            return $this->failure('Tai khoan khong co ho so trong tai.', 403);
        }

        return $referee;
    }

    private function filters(array $filters): array
    {
        $errors = [];
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $tournamentId = $this->optionalPositiveInt($filters['tournament_id'] ?? $filters['idgiaidau'] ?? null);
        $matchId = $this->optionalPositiveInt($filters['match_id'] ?? $filters['idtrandau'] ?? null);
        $from = trim((string) ($filters['from'] ?? $filters['from_date'] ?? ''));
        $to = trim((string) ($filters['to'] ?? $filters['to_date'] ?? ''));

        if ($status !== '' && !in_array($status, self::REPORT_STATUSES, true)) {
            $errors['status'] = 'Trang thai bao cao su co khong hop le.';
        }

        if (($filters['tournament_id'] ?? $filters['idgiaidau'] ?? null) !== null && $tournamentId === null) {
            $errors['tournament_id'] = 'Ma giai dau khong hop le.';
        }

        if (($filters['match_id'] ?? $filters['idtrandau'] ?? null) !== null && $matchId === null) {
            $errors['match_id'] = 'Ma tran dau khong hop le.';
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
            'tournament_id' => $tournamentId,
            'match_id' => $matchId,
            'from' => $from,
            'to' => $to,
        ], $errors];
    }

    private function matchFilters(array $filters): array
    {
        $errors = [];
        $tournamentId = $this->optionalPositiveInt($filters['tournament_id'] ?? $filters['idgiaidau'] ?? null);
        $matchStatus = strtoupper(trim((string) ($filters['match_status'] ?? $filters['trangthai_trandau'] ?? '')));

        if (($filters['tournament_id'] ?? $filters['idgiaidau'] ?? null) !== null && $tournamentId === null) {
            $errors['tournament_id'] = 'Ma giai dau khong hop le.';
        }

        if ($matchStatus !== '' && !in_array($matchStatus, self::MATCH_STATUSES, true)) {
            $errors['match_status'] = 'Trang thai tran dau khong hop le.';
        }

        return [[
            'q' => trim((string) ($filters['q'] ?? $filters['keyword'] ?? '')),
            'tournament_id' => $tournamentId,
            'match_status' => $matchStatus,
        ], $errors];
    }

    private function reportFromPayload(array $payload, int $refereeId, ?int $routeMatchId): array
    {
        $errors = [];
        $matchId = $routeMatchId ?? $this->optionalPositiveInt($payload['match_id'] ?? $payload['idtrandau'] ?? $payload['matchId'] ?? null);

        if ($matchId === null) {
            $errors['match_id'] = 'Ma tran dau la bat buoc.';
        }

        $content = $this->requiredString($payload, ['noidung', 'content', 'description'], 5, 2000, 'Noi dung bao cao', $errors);
        $title = $this->optionalString($payload, ['tieude', 'title'], 300, $errors);
        $evidence = $this->optionalString($payload, ['minhchung', 'evidence', 'evidence_url', 'attachment'], 500, $errors);

        if ($title === null && $matchId !== null) {
            $title = 'Bao cao su co tran #' . $matchId;
        }

        return [[
            'idtrandau' => $matchId ?? 0,
            'idtrongtai' => $refereeId,
            'tieude' => $title ?? '',
            'noidung' => $content ?? '',
            'minhchung' => $evidence,
        ], $errors];
    }

    private function requiredString(array $payload, array $keys, int $minLength, int $maxLength, string $label, array &$errors): ?string
    {
        $value = null;

        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                $value = trim((string) $payload[$key]);
                break;
            }
        }

        $errorKey = $keys[0];

        if ($value === null || $value === '') {
            $errors[$errorKey] = $label . ' la bat buoc.';
            return null;
        }

        $length = strlen($value);

        if ($length < $minLength || $length > $maxLength) {
            $errors[$errorKey] = sprintf('%s phai dai tu %d den %d ky tu.', $label, $minLength, $maxLength);
            return null;
        }

        return $value;
    }

    private function optionalString(array $payload, array $keys, int $maxLength, array &$errors): ?string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = trim((string) $payload[$key]);

            if ($value === '') {
                return null;
            }

            if (strlen($value) > $maxLength) {
                $errors[$keys[0]] = sprintf('Gia tri %s toi da %d ky tu.', $keys[0], $maxLength);
                return null;
            }

            return $value;
        }

        return null;
    }

    private function optionalPositiveInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '' || !ctype_digit((string) $value)) {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }

    private function logNote(int $refereeId, array $assignment, array $report): string
    {
        return $this->limitLogNote(sprintf(
            'Trong tai #%d gui bao cao su co tran #%d (%s vs %s), tieu de: %s.',
            $refereeId,
            (int) $assignment['idtrandau'],
            (string) ($assignment['doi1'] ?? ''),
            (string) ($assignment['doi2'] ?? ''),
            (string) $report['tieude']
        ));
    }

    private function listLogNote(int $refereeId, array $filters, int $total): string
    {
        $parts = [
            'Trong tai #' . $refereeId . ' xem danh sach bao cao su co',
            'So dong: ' . $total,
        ];

        foreach (['q', 'status', 'tournament_id', 'match_id', 'from', 'to'] as $key) {
            if (($filters[$key] ?? '') !== '' && ($filters[$key] ?? null) !== null) {
                $parts[] = $key . '=' . (string) $filters[$key];
            }
        }

        return $this->limitLogNote(implode('. ', $parts));
    }

    private function matchListLogNote(int $refereeId, array $filters, int $total): string
    {
        $parts = [
            'Trong tai #' . $refereeId . ' xem danh sach tran dau co the bao cao su co',
            'So dong: ' . $total,
        ];

        foreach (['q', 'tournament_id', 'match_status'] as $key) {
            if (($filters[$key] ?? '') !== '' && ($filters[$key] ?? null) !== null) {
                $parts[] = $key . '=' . (string) $filters[$key];
            }
        }

        return $this->limitLogNote(implode('. ', $parts));
    }

    private function detailLogNote(int $refereeId, array $report): string
    {
        return $this->limitLogNote(sprintf(
            'Trong tai #%d xem bao cao su co #%d, tran #%d, trang thai %s.',
            $refereeId,
            (int) $report['idbaocao'],
            (int) $report['idtrandau'],
            (string) $report['trangthai']
        ));
    }

    private function limitLogNote(string $note): string
    {
        if (strlen($note) <= 1000) {
            return $note;
        }

        return substr($note, 0, 997) . '...';
    }

    private function failure(string $message, int $status, array $errors = []): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
        ];
    }
}

