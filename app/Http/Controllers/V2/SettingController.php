<?php

namespace App\Http\Controllers\V2;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends BaseController
{
    public function __construct(\App\Services\V2\ExternalLogisticsService $integrations)
    {
        parent::__construct($integrations);
        $this->middleware('auth');
    }

    public function edit()
    {
        return $this->render('settings.edit', [
            'pageTitle' => 'Application Settings',
            'setting' => Setting::query()->first() ?: new Setting(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => ['nullable', 'exists:settings,id'],
            'name' => ['nullable', 'string'],
            'copyright' => ['nullable', 'string'],
            'bocsh_link' => ['nullable', 'string'],
            'tracing_link' => ['nullable', 'string'],
            'flee_link' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'access_token' => ['nullable', 'string'],
        ]);

        try {
            $setting = empty($validated['id'])
                ? (Setting::query()->first() ?: new Setting())
                : Setting::query()->findOrFail($validated['id']);

            $setting->name = $validated['name'] ?? null;
            $setting->copyright = $validated['copyright'] ?? null;
            $setting->bocsh_link = $validated['bocsh_link'] ?? null;
            $setting->tracing_link = $validated['tracing_link'] ?? null;
            $setting->flee_link = $validated['flee_link'] ?? null;
            $setting->address = $validated['address'] ?? null;
            $setting->access_token = $validated['access_token'] ?? null;
            $setting->save();
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Settings Update Failed', $request, [
                'setting_id' => $validated['id'] ?? null,
            ]);

            return back()
                ->withInput()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        return redirect()->route('v2.settings.edit')
            ->with('message', 'Settings updated successfully.')
            ->with('message_type', 'success');
    }
}
