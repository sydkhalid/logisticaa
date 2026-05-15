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
            'name' => ['nullable', 'string', 'max:255'],
            'copyright' => ['nullable', 'string', 'max:255'],
            'bocsh_link' => ['nullable', 'url', 'starts_with:http://,https://', 'max:255'],
            'tracing_link' => ['nullable', 'url', 'starts_with:http://,https://', 'max:255'],
            'flee_link' => ['nullable', 'url', 'starts_with:http://,https://', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'access_token' => ['nullable', 'string', 'max:2000'],
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

            if (array_key_exists('address', $validated) && trim((string) $validated['address']) !== '') {
                $setting->address = $validated['address'];
            }

            if (array_key_exists('access_token', $validated) && trim((string) $validated['access_token']) !== '') {
                $setting->access_token = $validated['access_token'];
            }

            $setting->save();
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Settings Update Failed', $request, [
                'setting_id' => $validated['id'] ?? null,
            ]);

            return back()
                ->withInput()
                ->with('message', 'Settings could not be saved. Please check the values and try again.')
                ->with('message_type', 'danger');
        }

        return redirect()->route('v2.settings.edit')
            ->with('message', 'Settings updated successfully.')
            ->with('message_type', 'success');
    }
}
