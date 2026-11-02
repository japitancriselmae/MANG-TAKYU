<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);

        $now = now();
        $startOfDay = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek(Carbon::MONDAY);

        $activityStats = [
            'today' => ActivityLog::query()
                ->whereBetween('created_at', [$startOfDay, $now])
                ->count(),
            'thisWeek' => ActivityLog::query()
                ->whereBetween('created_at', [$startOfWeek, $now])
                ->count(),
            'successful' => ActivityLog::query()
                ->where('status', ActivityLog::STATUS_SUCCESS)
                ->count(),
            'failed' => ActivityLog::query()
                ->where('status', ActivityLog::STATUS_FAILURE)
                ->count(),
        ];

        $query = $this->filteredActivityQuery($filters)
            ->select([
                'id',
                'actor_name',
                'action',
                'module',
                'description',
                'status',
                'created_at',
            ]);

        $activities = $this->applyActivitySort($query, $filters)
            ->paginate(15)
            ->withQueryString();
        $modules = ActivityLog::query()->distinct()->orderBy('module')->pluck('module');
        $actions = ActivityLog::query()->distinct()->orderBy('action')->pluck('action');
        $dailyActivitySummaries = $this->dailyActivitySummaries($now);
        $weeklyActivitySummaries = $this->weeklyActivitySummaries($now);
        $frequentActions = $this->frequentActions($now);

        return view('activity-logs.index', compact(
            'activityStats',
            'activities',
            'actions',
            'dailyActivitySummaries',
            'filters',
            'frequentActions',
            'modules',
            'weeklyActivitySummaries',
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $query = $this->applyActivitySort(
            $this->filteredActivityQuery($filters)->select([
                'actor_name',
                'action',
                'module',
                'description',
                'status',
                'created_at',
            ]),
            $filters,
        );

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                throw new RuntimeException('Unable to open the activity export stream.');
            }

            if (fputcsv($output, ['User', 'Action', 'Module', 'Description', 'Status', 'Date and Time'], ',', '"', '') === false) {
                throw new RuntimeException('Unable to write the activity export header.');
            }

            $query->chunk(500, function ($activities) use ($output): void {
                foreach ($activities as $activity) {
                    $row = [
                        $activity->actor_name,
                        $activity->action,
                        $activity->module,
                        $activity->description,
                        $activity->status,
                        $activity->created_at->toIso8601String(),
                    ];

                    if (fputcsv($output, array_map($this->safeCsvValue(...), $row), ',', '"', '') === false) {
                        throw new RuntimeException('Unable to write an activity export row.');
                    }
                }
            });

            fclose($output);
        }, 'activity-logs-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(ActivityLog $activityLog): View
    {
        return view('activity-logs.show', [
            'activity' => $activityLog,
            'subjectType' => $activityLog->subject_type === null
                ? null
                : Str::afterLast($activityLog->subject_type, '\\'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'module' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'end_date' => array_filter([
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
                $request->filled('start_date') ? 'after_or_equal:start_date' : null,
            ]),
            'status' => ['nullable', 'in:success,failure'],
            'sort' => ['nullable', 'in:newest,oldest'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredActivityQuery(array $filters): Builder
    {
        return ActivityLog::query()
            ->when(
                ! empty($filters['search']),
                fn (Builder $query) => $query->where('actor_name', 'like', '%'.$filters['search'].'%'),
            )
            ->when(
                ! empty($filters['module']),
                fn (Builder $query) => $query->where('module', $filters['module']),
            )
            ->when(
                ! empty($filters['action']),
                fn (Builder $query) => $query->where('action', $filters['action']),
            )
            ->when(
                ! empty($filters['status']),
                fn (Builder $query) => $query->where('status', $filters['status']),
            )
            ->when(
                ! empty($filters['start_date']),
                fn (Builder $query) => $query->where(
                    'created_at',
                    '>=',
                    Carbon::createFromFormat('Y-m-d', $filters['start_date'])->startOfDay(),
                ),
            )
            ->when(
                ! empty($filters['end_date']),
                fn (Builder $query) => $query->where(
                    'created_at',
                    '<=',
                    Carbon::createFromFormat('Y-m-d', $filters['end_date'])->endOfDay(),
                ),
            );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyActivitySort(Builder $query, array $filters): Builder
    {
        if (($filters['sort'] ?? 'newest') === 'oldest') {
            return $query->orderBy('created_at')->orderBy('id');
        }

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * @return list<array{label: string, total: int}>
     */
    private function dailyActivitySummaries(Carbon $now): array
    {
        $startOfPeriod = $now->copy()->subDays(6)->startOfDay();
        $dailyCounts = ActivityLog::query()
            ->where('created_at', '>=', $startOfPeriod)
            ->where('created_at', '<=', $now)
            ->selectRaw('DATE(created_at) as activity_date, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'activity_date');

        $summaries = [];

        for ($day = $startOfPeriod->copy(); $day->lessThanOrEqualTo($now); $day->addDay()) {
            $date = $day->toDateString();
            $summaries[] = [
                'label' => $day->format('D, M j'),
                'total' => (int) $dailyCounts->get($date, 0),
            ];
        }

        return $summaries;
    }

    /**
     * @return list<array{label: string, total: int}>
     */
    private function weeklyActivitySummaries(Carbon $now): array
    {
        $currentWeekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $summaries = [];

        for ($weekOffset = 3; $weekOffset >= 0; $weekOffset--) {
            $weekStart = $currentWeekStart->copy()->subWeeks($weekOffset);
            $weekEnd = $weekStart->copy()->addWeek();
            $summaries[] = [
                'label' => $weekStart->format('M j').' – '.$weekEnd->copy()->subDay()->format('M j'),
                'total' => ActivityLog::query()
                    ->where('created_at', '>=', $weekStart)
                    ->where('created_at', '<', $weekEnd)
                    ->count(),
            ];
        }

        return $summaries;
    }

    /**
     * @return Collection<int, ActivityLog>
     */
    private function frequentActions(Carbon $now): Collection
    {
        return ActivityLog::query()
            ->where('created_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->where('created_at', '<=', $now)
            ->select('action')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('action')
            ->orderByDesc('total')
            ->orderBy('action')
            ->limit(5)
            ->get();
    }

    private function safeCsvValue(string|int|null $value): string
    {
        $value = (string) ($value ?? '');

        if (preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
