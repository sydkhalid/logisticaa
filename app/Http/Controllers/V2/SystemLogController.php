<?php

namespace App\Http\Controllers\V2;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class SystemLogController extends BaseController
{
    public function __construct(\App\Services\V2\ExternalLogisticsService $integrations)
    {
        parent::__construct($integrations);
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $query = $this->filteredQuery($filters);
        $retentionCutoff = $this->retentionCutoff();

        return $this->render('logs.index', [
            'pageTitle' => 'System Logs',
            'filters' => $filters,
            'summary' => [
                'total' => (clone $query)->count(),
                'success' => (clone $query)->whereIn('type', ['success', 'info'])->count(),
                'warning' => (clone $query)->where('type', 'warning')->count(),
                'danger' => (clone $query)->whereIn('type', ['danger', 'emergency'])->count(),
            ],
            'allLogsCount' => ActivityLog::query()->count(),
            'oldLogsCount' => ActivityLog::query()->where('created_at', '<', $retentionCutoff)->count(),
            'retentionCutoff' => $this->displayDate($retentionCutoff),
            'canManageLogs' => $this->canManageLogs($request),
            'types' => ['info', 'success', 'warning', 'danger', 'emergency'],
        ]);
    }

    public function data(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $query = $this->filteredQuery($filters)
            ->select(['id', 'type', 'title', 'description', 'uri', 'ip', 'created_at', 'created_by'])
            ->latest('created_at');

        return $this->datatableResponse(
            $request,
            $query,
            ['title', 'description', 'uri', 'ip', 'created_by'],
            ['created_at', 'created_at', 'type', 'title', 'created_by', 'uri', null],
            function (ActivityLog $log, int $index) {
                $badgeMap = [
                    'success' => 'success',
                    'info' => 'info',
                    'warning' => 'warning',
                    'danger' => 'danger',
                    'emergency' => 'danger',
                ];
                $badge = $badgeMap[$log->type] ?? 'secondary';

                return [
                    'index' => $index,
                    'created_at' => e($this->displayDate($log->created_at)),
                    'type' => '<span class="badge badge-' . e($badge) . '">' . e(strtoupper((string) $log->type)) . '</span>',
                    'title' => e($log->title),
                    'created_by' => e($log->created_by ?: 'System'),
                    'uri' => e($log->uri),
                    'actions' => $this->actionGroup([
                        $this->actionLink(route('v2.logs.show', $log), 'View', 'btn-outline-info'),
                    ]),
                ];
            }
        );
    }

    public function show(ActivityLog $log)
    {
        $requestInfo = json_decode((string) $log->request_info, true);
        $prettyInfo = $requestInfo !== null
            ? json_encode($requestInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : (string) $log->request_info;

        return $this->render('logs.show', [
            'pageTitle' => 'Log Details',
            'logEntry' => $log,
            'prettyInfo' => $prettyInfo ?: '{}',
            'displayDate' => $this->displayDate($log->created_at),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $filename = 'system-logs-' . $filters['from'] . '_to_' . $filters['to'] . '.csv';
        $rows = $this->filteredQuery($filters)
            ->select(['id', 'type', 'title', 'description', 'uri', 'ip', 'is_api', 'request_info', 'created_at', 'created_by', 'user_id'])
            ->latest('created_at');

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_map([$this, 'csvCell'], [
                'ID',
                'Date',
                'Type',
                'Title',
                'Description',
                'Actor',
                'User ID',
                'URI',
                'IP',
                'API Request',
                'Request Info',
            ]));

            foreach ($rows->cursor() as $log) {
                fputcsv($handle, array_map([$this, 'csvCell'], [
                    $log->id,
                    $log->created_at,
                    $log->type,
                    $log->title,
                    $log->description,
                    $log->created_by ?: 'System',
                    $log->user_id,
                    $log->uri,
                    $log->ip,
                    (int) $log->is_api === 1 ? 'Yes' : 'No',
                    $log->request_info,
                ]));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function clearOld(Request $request)
    {
        $this->authorizeLogClear($request);

        $cutoff = $this->retentionCutoff();
        $deleted = ActivityLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->activityLogs->logSystem('warning', 'Old System Logs Cleared', 'Admin cleared system logs older than 30 days.', [
            'deleted_count' => $deleted,
            'cutoff' => $cutoff,
        ], $request->user()->id, $request->user()->name ?: $request->user()->email);

        return redirect()->route('v2.logs.index')
            ->with('message', $deleted . ' system log(s) older than 30 days cleared.')
            ->with('message_type', 'success');
    }

    public function clear(Request $request)
    {
        $this->authorizeLogClear($request);

        $deleted = ActivityLog::query()->delete();

        $this->activityLogs->logSystem('warning', 'System Logs Cleared', 'Admin cleared all system logs.', [
            'deleted_count' => $deleted,
        ], $request->user()->id, $request->user()->name ?: $request->user()->email);

        return redirect()->route('v2.logs.index')
            ->with('message', $deleted . ' system log(s) cleared successfully.')
            ->with('message_type', 'success');
    }

    private function validatedFilters(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'type' => ['nullable', 'in:info,success,warning,danger,emergency'],
            'actor' => ['nullable', 'string'],
        ]);

        return [
            'from' => $validated['from'] ?? date('Y-m-d', strtotime('-7 days')),
            'to' => $validated['to'] ?? date('Y-m-d'),
            'type' => $validated['type'] ?? '',
            'actor' => $validated['actor'] ?? '',
        ];
    }

    private function filteredQuery(array $filters)
    {
        return ActivityLog::query()
            ->whereBetween('created_at', [
                $filters['from'] . ' 00:00:00',
                $filters['to'] . ' 23:59:59',
            ])
            ->when($filters['type'] !== '', function ($builder) use ($filters) {
                $builder->where('type', $filters['type']);
            })
            ->when($filters['actor'] !== '', function ($builder) use ($filters) {
                $builder->where('created_by', 'like', '%' . $filters['actor'] . '%');
            });
    }

    private function authorizeLogClear(Request $request): void
    {
        abort_unless($this->canManageLogs($request), 403, 'Only administrators can clear system logs.');
    }

    private function canManageLogs(Request $request): bool
    {
        $user = $request->user();

        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    private function retentionCutoff(): string
    {
        return date('Y-m-d H:i:s', strtotime('-30 days'));
    }

    private function csvCell($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) === 1
            ? "'" . $value
            : $value;
    }
}
