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
            'setting' => Setting::query()->first(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:settings,id'],
            'name' => ['nullable', 'string'],
            'copyright' => ['nullable', 'string'],
            'bocsh_link' => ['nullable', 'string'],
            'tracing_link' => ['nullable', 'string'],
            'flee_link' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'access_token' => ['nullable', 'string'],
        ]);

        Setting::query()->where('id', $validated['id'])->update([
            'name' => $validated['name'] ?? null,
            'copyright' => $validated['copyright'] ?? null,
            'bocsh_link' => $validated['bocsh_link'] ?? null,
            'tracing_link' => $validated['tracing_link'] ?? null,
            'flee_link' => $validated['flee_link'] ?? null,
            'address' => $validated['address'] ?? null,
            'access_token' => $validated['access_token'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->route('v2.settings.edit')
            ->with('message', 'Settings updated successfully.')
            ->with('message_type', 'success');
    }
}
