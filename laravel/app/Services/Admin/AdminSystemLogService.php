<?php

namespace App\Services\Admin;

use App\Repositories\Admin\AdminSystemLogRepository;

final class AdminSystemLogService
{
    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE = 100;

    public function __construct(private readonly AdminSystemLogRepository $logs)
    {
    }

    public function list(array $filters = []): array
    {
        [$normalized, $page, $perPage] = $this->normalize($filters);
        $total = $this->logs->count($normalized);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;

        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;

        return [
            'logs' => $this->logs->list($normalized, $perPage, $offset),
            'filters' => $normalized,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }

    public function find(int $logId): ?array
    {
        return $this->logs->findById($logId);
    }

    public function options(): array
    {
        return [
            'target_tables' => $this->logs->targetTables(),
            'actions' => $this->logs->actions(),
            'actors' => $this->logs->actors(),
            'per_page' => [
                'default' => self::DEFAULT_PER_PAGE,
                'max' => self::MAX_PER_PAGE,
            ],
        ];
    }

    private function normalize(array $filters): array
    {
        $accountId = $this->positiveInt($filters['idtaikhoan'] ?? $filters['actor_id'] ?? $filters['account_id'] ?? null);

        $normalized = [
            'q' => trim((string) ($filters['q'] ?? '')),
            'idtaikhoan' => $accountId,
            'bangtacdong' => trim((string) ($filters['bangtacdong'] ?? $filters['target_table'] ?? '')),
            'hanhdong' => trim((string) ($filters['hanhdong'] ?? $filters['action'] ?? '')),
            'from' => $this->dateOrEmpty($filters['from'] ?? $filters['from_date'] ?? ''),
            'to' => $this->dateOrEmpty($filters['to'] ?? $filters['to_date'] ?? ''),
        ];

        $page = $this->positiveInt($filters['page'] ?? null) ?? 1;
        $perPage = $this->positiveInt($filters['per_page'] ?? $filters['limit'] ?? null) ?? self::DEFAULT_PER_PAGE;
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return [$normalized, $page, $perPage];
    }

    private function positiveInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (!ctype_digit((string) $value)) {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    private function dateOrEmpty(mixed $value): string
    {
        $date = trim((string) $value);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return '';
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));

        return checkdate($month, $day, $year) ? $date : '';
    }
}
