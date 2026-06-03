<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Giaidau;
use App\Backend\Models\Khieunai;
use RuntimeException;
use Throwable;

final class OrganizerComplaintService
{
    private const STATUSES = ['CHO_TIEP_NHAN', 'DANG_XU_LY', 'DA_XU_LY', 'TU_CHOI', 'KHONG_XU_LY'];
    private const STATUS_ALIASES = [
        'CHO_XU_LY' => 'DANG_XU_LY',
        'TU_CHOI_TIEP_NHAN' => 'TU_CHOI',
    ];

    public function __construct(
        private ?Khieunai $complaints = null,
        private ?Giaidau $tournaments = null,
        private ?OrganizerMatchResultService $resultService = null
    ) {
        $this->complaints ??= new Khieunai();
        $this->tournaments ??= new Giaidau();
        $this->resultService ??= new OrganizerMatchResultService();
    }

    public function all(int $accountId, array $filters = []): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $normalized = $this->filters($filters);

        if ($normalized['errors'] !== []) {
            return $this->failure('Bo loc khieu nai khong hop le.', 422, $normalized['errors']);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach khieu nai thanh cong.',
            'complaints' => $this->complaints->listForOrganizer((int) $organizer['idbantochuc'], $normalized['filters']),
            'meta' => [
                'filters' => $normalized['filters'],
                'statuses' => self::STATUSES,
                'stats' => $this->complaints->statsForOrganizer((int) $organizer['idbantochuc'], $normalized['filters']),
            ],
        ];
    }

    public function find(int $complaintId, int $accountId): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $complaint = $this->complaints->findForOrganizer((int) $organizer['idbantochuc'], $complaintId);

        if ($complaint === null) {
            return $this->failure('Khong tim thay khieu nai.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin khieu nai thanh cong.',
            'complaint' => $complaint,
        ];
    }

    public function receive(int $complaintId, array $payload, int $accountId, ?Request $request = null): array
    {
        return $this->transition(
            complaintId: $complaintId,
            expectedStatus: 'CHO_TIEP_NHAN',
            newStatus: 'DANG_XU_LY',
            payload: $payload,
            accountId: $accountId,
            request: $request,
            defaultReason: 'Tiep nhan khieu nai',
            successMessage: 'Tiep nhan khieu nai thanh cong.',
            setProcessedAt: false,
            requireReason: true
        );
    }

    public function reject(int $complaintId, array $payload, int $accountId, ?Request $request = null): array
    {
        return $this->transition(
            complaintId: $complaintId,
            expectedStatus: 'CHO_TIEP_NHAN',
            newStatus: 'TU_CHOI',
            payload: $payload,
            accountId: $accountId,
            request: $request,
            defaultReason: 'Tu choi tiep nhan khieu nai',
            successMessage: 'Tu choi tiep nhan khieu nai thanh cong.',
            setProcessedAt: true,
            requireReason: true
        );
    }

    public function resolve(int $complaintId, array $payload, int $accountId, ?Request $request = null): array
    {
        return $this->transition(
            complaintId: $complaintId,
            expectedStatus: 'DANG_XU_LY',
            newStatus: 'DA_XU_LY',
            payload: $payload,
            accountId: $accountId,
            request: $request,
            defaultReason: 'Da xu ly khieu nai',
            successMessage: 'Ghi nhan khieu nai da xu ly thanh cong.',
            setProcessedAt: true,
            requireReason: true,
            adjustScore: true
        );
    }

    public function noProcess(int $complaintId, array $payload, int $accountId, ?Request $request = null): array
    {
        return $this->transition(
            complaintId: $complaintId,
            expectedStatus: 'DANG_XU_LY',
            newStatus: 'KHONG_XU_LY',
            payload: $payload,
            accountId: $accountId,
            request: $request,
            defaultReason: 'Khong xu ly khieu nai',
            successMessage: 'Ghi nhan khieu nai khong xu ly thanh cong.',
            setProcessedAt: true,
            requireReason: true
        );
    }

    private function transition(
        int $complaintId,
        string $expectedStatus,
        string $newStatus,
        array $payload,
        int $accountId,
        ?Request $request,
        string $defaultReason,
        string $successMessage,
        bool $setProcessedAt,
        bool $requireReason = false,
        bool $adjustScore = false
    ): array {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $complaint = $this->complaints->findForOrganizer((int) $organizer['idbantochuc'], $complaintId);

        if ($complaint === null) {
            return $this->failure('Khong tim thay khieu nai.', 404);
        }

        if ((string) $complaint['trangthai'] !== $expectedStatus) {
            return $this->failure($this->invalidTransitionMessage($expectedStatus, $newStatus), 409, [
                'trangthai' => 'Trang thai hien tai: ' . (string) $complaint['trangthai'],
            ]);
        }

        $explicitReason = $this->explicitReason($payload);

        if ($requireReason && $explicitReason === '') {
            return $this->failure('Vui long nhap noi dung phan hoi khieu nai.', 422, [
                'lydo' => 'Noi dung phan hoi la bat buoc.',
            ]);
        }

        $reason = $explicitReason === '' ? $defaultReason : $explicitReason;

        if (strlen($reason) > 1000) {
            return $this->failure('Ghi chu xu ly khieu nai khong hop le.', 422, [
                'lydo' => 'Ghi chu khong duoc vuot qua 1000 ky tu.',
            ]);
        }

        if ($adjustScore && $this->hasScorePayload($payload)) {
            if (empty($complaint['idketqua'])) {
                return $this->failure('Khieu nai nay khong gan voi ket qua tran dau de dieu chinh ty so.', 422, [
                    'idketqua' => 'Khong tim thay ket qua tran dau.',
                ]);
            }

            $scorePayload = $payload;
            $scorePayload['reason'] = $reason;
            $scorePayload['lydo'] = $reason;
            $adjusted = $this->resultService->adjust((int) $complaint['idketqua'], $scorePayload, $accountId, $request);

            if (isset($adjusted['ok']) && $adjusted['ok'] === false) {
                return $adjusted;
            }
        }

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d cap nhat khieu nai #%d cua giai "%s": %s -> %s. Ghi chu: %s',
            (int) $organizer['idbantochuc'],
            $complaintId,
            (string) $complaint['tengiaidau'],
            $expectedStatus,
            $newStatus,
            $reason
        ));

        try {
            $this->complaints->updateStatus(
                $complaintId,
                $expectedStatus,
                $newStatus,
                $accountId,
                $request?->ip(),
                $logNote,
                $reason,
                $setProcessedAt
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => $successMessage,
                'complaint' => $this->complaints->findForOrganizer((int) $organizer['idbantochuc'], $complaintId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'COMPLAINT_STATUS_NOT_UPDATED') {
                return $this->failure('Khong the cap nhat trang thai khieu nai hien tai.', 409);
            }

            return $this->failure('Khong the cap nhat khieu nai.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the cap nhat khieu nai.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    private function filters(array $filters): array
    {
        $errors = [];
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $status = self::STATUS_ALIASES[$status] ?? $status;
        $tournamentId = $this->optionalPositiveInt($filters['tournament_id'] ?? $filters['idgiaidau'] ?? null, 'tournament_id', $errors);
        $matchId = $this->optionalPositiveInt($filters['match_id'] ?? $filters['idtrandau'] ?? null, 'match_id', $errors);
        $from = trim((string) ($filters['from'] ?? $filters['from_date'] ?? ''));
        $to = trim((string) ($filters['to'] ?? $filters['to_date'] ?? ''));

        if ($status !== '' && !in_array($status, self::STATUSES, true)) {
            $errors['status'] = 'Trang thai khieu nai khong hop le.';
        }

        if ($from !== '' && !$this->isDate($from)) {
            $errors['from'] = 'Ngay bat dau khong hop le.';
        }

        if ($to !== '' && !$this->isDate($to)) {
            $errors['to'] = 'Ngay ket thuc khong hop le.';
        }

        if ($from !== '' && $to !== '' && $this->isDate($from) && $this->isDate($to) && $to < $from) {
            $errors['to'] = 'Ngay ket thuc phai lon hon hoac bang ngay bat dau.';
        }

        return [
            'filters' => [
                'q' => trim((string) ($filters['q'] ?? $filters['keyword'] ?? '')),
                'status' => $status,
                'tournament_id' => $tournamentId,
                'match_id' => $matchId,
                'from' => $from,
                'to' => $to,
            ],
            'errors' => $errors,
        ];
    }

    private function optionalPositiveInt(mixed $value, string $errorKey, array &$errors): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (!ctype_digit((string) $value) || (int) $value <= 0) {
            $errors[$errorKey] = 'Gia tri khong hop le.';
            return null;
        }

        return (int) $value;
    }

    private function explicitReason(array $payload): string
    {
        return trim((string) ($payload['lydo'] ?? $payload['ly_do'] ?? $payload['reason'] ?? $payload['note'] ?? $payload['ghichu'] ?? ''));
    }

    private function hasScorePayload(array $payload): bool
    {
        foreach (['iddoithang', 'winner_team_id', 'winner_id', 'diemdoi1', 'score1', 'team_one_score', 'diemdoi2', 'score2', 'team_two_score', 'sosetdoi1', 'sets1', 'team_one_sets', 'sosetdoi2', 'sets2', 'team_two_sets'] as $key) {
            if (array_key_exists($key, $payload) && trim((string) $payload[$key]) !== '') {
                return true;
            }
        }

        return false;
    }

    private function invalidTransitionMessage(string $expectedStatus, string $newStatus): string
    {
        if ($expectedStatus === 'CHO_TIEP_NHAN' && $newStatus === 'DANG_XU_LY') {
            return 'Chi duoc tiep nhan khieu nai dang cho tiep nhan.';
        }

        if ($expectedStatus === 'CHO_TIEP_NHAN' && $newStatus === 'TU_CHOI') {
            return 'Chi duoc tu choi khieu nai dang cho tiep nhan.';
        }

        if ($expectedStatus === 'DANG_XU_LY') {
            return 'Chi duoc hoan tat khieu nai dang xu ly.';
        }

        return 'Trang thai khieu nai khong hop le cho thao tac nay.';
    }

    private function isDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }

    private function activeOrganizer(int $accountId): array
    {
        $organizer = $this->tournaments->findOrganizerByAccountId($accountId);

        if ($organizer === null) {
            return $this->failure('Tai khoan khong co ho so ban to chuc.', 403);
        }

        if ((string) $organizer['trangthai'] !== 'HOAT_DONG') {
            return $this->failure('Ban to chuc khong o trang thai hoat dong.', 403);
        }

        return $organizer;
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

