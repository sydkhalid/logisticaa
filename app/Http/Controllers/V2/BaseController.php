<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\V2\ExternalLogisticsService;

abstract class BaseController extends Controller
{
    protected $settings;
    protected $integrations;

    public function __construct(ExternalLogisticsService $integrations)
    {
        $this->integrations = $integrations;
        $this->settings = $this->integrations->getSettings();
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
}
