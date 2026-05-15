<?php

namespace App\Http\Controllers\V2;

use App\Models\Epod;
use App\Models\Tracking;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        ]);
    }

    public function data(Request $request)
    {
        $query = Epod::query()
            ->select(['id', 'lspId', 'lrNumber', 'status', 'created_at'])
            ->where('status', 1)
            ->latest('id');

        return $this->datatableResponse(
            $request,
            $query,
            ['lspId', 'lrNumber'],
            ['id', 'lspId', 'lrNumber', 'status', 'created_at'],
            function (Epod $epod, int $index) {
                return [
                    'index' => $index,
                    'lspId' => e($epod->lspId),
                    'lrNumber' => e($epod->lrNumber),
                    'status' => '<span class="badge badge-success">Uploaded</span>',
                    'created_at' => e($this->displayDate($epod->created_at)),
                ];
            }
        );
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
            'epod' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:2048'],
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
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $filename = 'epod-' . Str::uuid()->toString() . '.' . $extension;
        $destination = storage_path('app/epods');

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $this->clearPendingDrafts($validated['lspId'], $validated['lrNumber'], $destination);
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

        try {
            $epod->save();
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'EPOD Draft Save Failed', $request, [
                'lrNumber' => $validated['lrNumber'],
                'lspId' => $validated['lspId'],
                'epod' => $filename,
            ]);

            return back()
                ->withInput($request->only('lspId', 'lrNumber'))
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        $path = $destination . DIRECTORY_SEPARATOR . $filename;
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
        $base64 = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($path));

        try {
            $existingUploadedEpod
                ? $this->integrations->reuploadEpod($epod, $base64, $request->user())
                : $this->integrations->uploadEpod($epod, $base64, $request->user());
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'EPOD Upload Failed', $request, [
                'epod_id' => $epod->id,
                'lrNumber' => $epod->lrNumber,
                'lspId' => $epod->lspId,
                'reupload' => $existingUploadedEpod,
            ]);
            return back()
                ->withInput($request->only('lspId', 'lrNumber'))
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        try {
            $epod->status = 1;
            $epod->save();

            Tracking::query()
                ->where('lspId', $validated['lspId'])
                ->where('lrNumber', $validated['lrNumber'])
                ->update(['status' => 3]);
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'EPOD Status Finalize Failed', $request, [
                'epod_id' => $epod->id,
                'lrNumber' => $epod->lrNumber,
                'lspId' => $epod->lspId,
            ], 'warning');

            return redirect()->route('v2.epods.index')
                ->with('message', 'EPOD reached the remote API, but local status update failed. Please check logs.')
                ->with('message_type', 'warning');
        }

        return redirect()->route('v2.epods.index')
            ->with('message', $existingUploadedEpod ? 'EPOD re-uploaded successfully.' : 'EPOD uploaded successfully.')
            ->with('message_type', 'success');
    }

    private function clearPendingDrafts(string $lspId, string $lrNumber, string $destination)
    {
        $drafts = Epod::query()
            ->where('lspId', $lspId)
            ->where('lrNumber', $lrNumber)
            ->where('status', 0)
            ->get();

        foreach ($drafts as $draft) {
            $path = $destination . DIRECTORY_SEPARATOR . $draft->epod;

            if ($draft->epod && file_exists($path)) {
                @unlink($path);
            }

            $draft->delete();
        }
    }
}
