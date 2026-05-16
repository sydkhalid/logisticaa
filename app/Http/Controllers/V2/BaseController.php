<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogService;
use App\Services\V2\ExternalLogisticsService;
use Illuminate\Http\Request;
use Throwable;

abstract class BaseController extends Controller
{
    protected $settings;
    protected $integrations;
    protected $activityLogs;

    public function __construct(ExternalLogisticsService $integrations)
    {
        $this->integrations = $integrations;
        $this->settings = $this->integrations->getSettings() ?: new Setting();
        $this->activityLogs = app(ActivityLogService::class);
    }

    protected function render(string $view, array $data = [])
    {
        return view('v2.' . $view, array_merge([
            'settings' => $this->settings,
            'appName' => $this->settings && $this->settings->name
                ? $this->settings->name
                : config('app.name', 'Logisticaa'),
        ], $data));
    }

    protected function formatDateTime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $date = date_create($value);

        return $date ? $date->format('Y-m-d H:i:s') : null;
    }

    protected function htmlDateTime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $date = date_create($value);

        return $date ? $date->format('Y-m-d\TH:i') : null;
    }

    protected function displayDate($value): string
    {
        if (!$value) {
            return '-';
        }

        $date = $value instanceof \DateTimeInterface ? $value : date_create((string) $value);

        return $date ? $date->format('d M Y, h:i A') : (string) $value;
    }

    protected function defaultLspId(): string
    {
        return trim((string) config('integrations.travis.default_lsp_id', ''));
    }

    protected function datatableResponse(
        \Illuminate\Http\Request $request,
        $baseQuery,
        array $searchableColumns,
        array $orderableColumns,
        callable $transform
    ) {
        $draw = max((int) $request->input('draw', 1), 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 25);

        if ($length === -1 || $length > 100) {
            $length = 100;
        }

        if ($length < 1) {
            $length = 25;
        }

        $recordsTotal = (clone $baseQuery)->count();
        $query = clone $baseQuery;
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($searchableColumns, $search) {
                foreach ($searchableColumns as $column) {
                    $builder->orWhere($column, 'like', '%' . $search . '%');
                }
            });
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = $orderableColumns[$orderColumnIndex] ?? null;

        if ($orderColumn) {
            $query->reorder($orderColumn, $orderDirection);
        }

        $rows = $query->skip($start)->take($length)->get();
        $data = [];

        foreach ($rows as $offset => $row) {
            $data[] = $transform($row, $start + $offset + 1);
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    protected function actionLink(string $url, string $label, string $class): string
    {
        return '<a href="' . e($url) . '" class="btn ' . e($class) . ' btn-sm">' . e($label) . '</a>';
    }

    protected function actionForm(
        string $action,
        string $label,
        string $class,
        string $method = 'POST',
        ?string $confirmMessage = null
    ): string {
        $confirmAttributes = '';

        if ($confirmMessage) {
            $confirmAttributes = ' data-confirm="' . e($confirmMessage) . '" onsubmit="return window.V2.confirmDelete(this, this.getAttribute(\'data-confirm\'));"';
        }

        return '<form class="d-inline-block" method="POST" action="' . e($action) . '"' . $confirmAttributes . '>'
            . csrf_field()
            . ($method !== 'POST' ? method_field($method) : '')
            . '<button type="submit" class="btn ' . e($class) . ' btn-sm">' . e($label) . '</button>'
            . '</form>';
    }

    protected function actionGroup(array $actions): string
    {
        return '<div class="v2-action-cluster justify-content-end">' . implode('', $actions) . '</div>';
    }

    protected function logHandledException(
        Throwable $exception,
        string $title,
        ?Request $request = null,
        array $context = [],
        string $type = 'danger'
    ): void {
        $this->activityLogs->logThrowable(
            $exception,
            $title,
            array_merge($context, ['handled' => true]),
            $request,
            null,
            null,
            $type
        );
    }
}
