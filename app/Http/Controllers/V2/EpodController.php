<?php

namespace App\Http\Controllers\V2;

use App\Models\Epod;
use App\Models\Tracking;
use Illuminate\Http\Request;

class EpodController extends BaseController
{
    public function __construct(\App\Services\V2\ExternalLogisticsService $integrations)
    {
        parent::__construct($integrations);
        $this->middleware('auth');
    }

    public function index()
    {
        return $this->render('epods.index', [
            'pageTitle' => 'EPOD Uploads',
            'epods' => Epod::query()
                ->where('status', 1)
                ->latest('id')
                ->get(),
        ]);
    }

    public function create()
    {
        return $this->render('epods.form', [
            'pageTitle' => 'Upload EPOD',
            'recentTrackings' => Tracking::query()
                ->whereIn('status', [1, 3])
                ->latest('id')
                ->limit(20)
                ->get(['lrNumber', 'lspId']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lspId' => ['required', 'string'],
            'lrNumber' => ['required', 'string'],
            'epod' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        $trackingExists = Tracking::query()
            ->where('lspId', $validated['lspId'])
            ->where('lrNumber', $validated['lrNumber'])
            ->exists();

        if (!$trackingExists) {
            return back()
                ->withInput($request->only('lspId', 'lrNumber'))
                ->with('message', 'Create the LR tracking before uploading EPOD.')
                ->with('message_type', 'warning');
        }

        $file = $request->file('epod');
        $filename = 'category' . random_int(1, 1000000) . '.' . $file->getClientOriginalExtension();
        $destination = base_path('upload/epods');

        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        Epod::query()->where('status', 0)->delete();
        $file->move($destination, $filename);

        $existingUploadedEpod = Epod::query()
            ->where('lspId', $validated['lspId'])
            ->where('lrNumber', $validated['lrNumber'])
            ->where('status', 1)
            ->exists();

        $epod = new Epod();
        $epod->lspId = $validated['lspId'];
        $epod->lrNumber = $validated['lrNumber'];
        $epod->epod = $filename;
        $epod->status = 0;
        $epod->save();

        $path = $destination . DIRECTORY_SEPARATOR . $filename;
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
        $base64 = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($path));

        try {
            $existingUploadedEpod
                ? $this->integrations->reuploadEpod($epod, $base64, $request->user())
                : $this->integrations->uploadEpod($epod, $base64, $request->user());
        } catch (\Throwable $exception) {
            return back()
                ->withInput($request->only('lspId', 'lrNumber'))
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        $epod->status = 1;
        $epod->save();

        Tracking::query()
            ->where('lspId', $validated['lspId'])
            ->where('lrNumber', $validated['lrNumber'])
            ->update(['status' => 3]);

        return redirect()->route('v2.epods.index')
            ->with('message', $existingUploadedEpod ? 'EPOD re-uploaded successfully.' : 'EPOD uploaded successfully.')
            ->with('message_type', 'success');
    }
}
