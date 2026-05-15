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
            'types' => ['info', 'success', 'warning', 'danger', 'emergency'],
        ]);
    }

    public function data(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $query = $this->filteredQuery($filters)
            ->select(['id', 'type', 'title', 'description', 'uri', 'ip', 'created_at', 'created_by'])
            ->latest('id');

        return $this->datatableResponse(
            $request,
            $query,
            ['title', 'description', 'uri', 'ip', 'created_by'],
            ['id', 'created_at', 'type', 'title', 'created_by', 'uri', null],
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

    public function clear()
    {
        ActivityLog::query()->delete();

        return redirect()->route('v2.logs.index')
            ->with('message', 'System logs cleared successfully.')
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
}
