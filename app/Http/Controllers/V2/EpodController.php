<?php

namespace App\Http\Controllers\V2;

use App\Models\Epod;
use App\Models\Tracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
            ->select(['id', 'lspId', 'lrNumber', 'epod', 'status', 'created_at', 'updated_at'])
            ->latest('id');

        return $this->datatableResponse(
            $request,
            $query,
            ['lspId', 'lrNumber'],
            ['id', 'lspId', 'lrNumber', 'status', 'created_at', null],
            function (Epod $epod, int $index) {
                $uploaded = (int) $epod->status === 1;
                $fileExists = $this->epodPath($epod) !== null;
                $actions = [
                    $this->actionLink(route('v2.epods.show', $epod), 'View', 'btn-outline-info'),
                ];

                if ($fileExists) {
                    $actions[] = $this->actionLink(route('v2.epods.download', $epod), 'Download', 'btn-outline-primary');
                }

                if (!$uploaded && $fileExists) {
                    $actions[] = $this->actionForm(route('v2.epods.retry', $epod), 'Retry', 'btn-outline-success');
                }

                if ($this->canManageEpods(request())) {
                    $actions[] = $this->actionForm(
                        route('v2.epods.destroy', $epod),
                        'Delete',
                        'btn-outline-danger',
                        'DELETE',
                        'Delete this EPOD record and its local file?'
                    );
                }

                return [
                    'index' => $index,
                    'lspId' => e($epod->lspId),
                    'lrNumber' => e($epod->lrNumber),
                    'status' => $uploaded
                        ? '<span class="badge badge-success">Uploaded</span>'
                        : '<span class="badge badge-warning">Pending Retry</span>',
                    'created_at' => e($this->displayDate($epod->created_at)),
                    'actions' => $this->actionGroup($actions),
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
            'defaultLspId' => $this->defaultLspId(),
        ]);
    }

    public function show(Epod $epod)
    {
        $tracking = $this->resolveTracking($epod);

        return $this->render('epods.show', [
            'pageTitle' => 'EPOD Details',
            'epod' => $epod,
            'tracking' => $tracking,
            'fileExists' => $this->epodPath($epod) !== null,
            'canManageEpods' => $this->canManageEpods(request()),
        ]);
    }

    public function download(Epod $epod)
    {
        $path = $this->epodPath($epod);
        abort_unless($path, 404, 'EPOD file was not found.');

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = 'epod-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $epod->lrNumber) . ($extension ? '.' . $extension : '');

        return response()->download($path, $filename, [
            'Content-Type' => mime_content_type($path) ?: 'application/octet-stream',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lspId' => ['required', 'string'],
            'lrNumber' => ['required', 'string'],
            'epod' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:2048'],
        ]);

        $tracking = Tracking::query()
            ->where('lspId', $validated['lspId'])
            ->where('lrNumber', $validated['lrNumber'])
            ->latest('id')
            ->first();

        if (!$tracking) {
            return back()
                ->withInput($request->only('lspId', 'lrNumber'))
                ->with('message', 'Create the LR tracking before uploading EPOD.')
                ->with('message_type', 'warning');
        }

        $file = $request->file('epod');
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $filename = 'epod-' . Str::uuid()->toString() . '.' . $extension;
        $destination = $this->epodDirectory();

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
        if (Schema::hasColumn('epods', 'tracking_id')) {
            $epod->tracking_id = $tracking->id;
        }

        try {
            $epod->save();
        } catch (\Throwable $exception) {
            $this->deleteLocalFile($filename);
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

        try {
            $this->sendRemoteEpod($epod, $request, $existingUploadedEpod);
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'EPOD Upload Failed', $request, [
                'epod_id' => $epod->id,
                'lrNumber' => $epod->lrNumber,
                'lspId' => $epod->lspId,
                'reupload' => $existingUploadedEpod,
            ]);
            return redirect()->route('v2.epods.show', $epod)
                ->with('message', 'EPOD was saved locally, but Travis upload failed: ' . $exception->getMessage())
                ->with('message_type', 'danger');
        }

        try {
            $this->markUploaded($epod);
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

    public function retry(Request $request, Epod $epod)
    {
        if ((int) $epod->status === 1) {
            return redirect()->route('v2.epods.show', $epod)
                ->with('message', 'This EPOD is already uploaded.')
                ->with('message_type', 'info');
        }

        if (!$this->epodPath($epod)) {
            return redirect()->route('v2.epods.show', $epod)
                ->with('message', 'Local EPOD file is missing. Delete this draft and upload the file again.')
                ->with('message_type', 'danger');
        }

        $existingUploadedEpod = Epod::query()
            ->when($epod->tracking_id, function ($query) use ($epod) {
                $query->where('tracking_id', $epod->tracking_id);
            }, function ($query) use ($epod) {
                $query->where('lspId', $epod->lspId)
                    ->where('lrNumber', $epod->lrNumber);
            })
            ->where('status', 1)
            ->where('id', '<>', $epod->id)
            ->exists();

        try {
            $this->sendRemoteEpod($epod, $request, $existingUploadedEpod);
            $this->markUploaded($epod);
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'EPOD Retry Failed', $request, [
                'epod_id' => $epod->id,
                'lrNumber' => $epod->lrNumber,
                'lspId' => $epod->lspId,
                'reupload' => $existingUploadedEpod,
            ]);

            return redirect()->route('v2.epods.show', $epod)
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        return redirect()->route('v2.epods.index')
            ->with('message', 'Pending EPOD uploaded successfully.')
            ->with('message_type', 'success');
    }

    public function destroy(Request $request, Epod $epod)
    {
        $wasUploaded = (int) $epod->status === 1;
        $trackingId = $epod->tracking_id;
        $lspId = (string) $epod->lspId;
        $lrNumber = (string) $epod->lrNumber;
        $filename = (string) $epod->epod;

        try {
            $this->deleteLocalFile($filename);
            $epod->delete();

            if ($wasUploaded) {
                $hasUploadedReplacement = Epod::query()
                    ->when($trackingId, function ($query) use ($trackingId) {
                        $query->where('tracking_id', $trackingId);
                    }, function ($query) use ($lspId, $lrNumber) {
                        $query->where('lspId', $lspId)
                            ->where('lrNumber', $lrNumber);
                    })
                    ->where('status', 1)
                    ->exists();

                if (!$hasUploadedReplacement) {
                    $trackingQuery = Tracking::query()
                        ->when($trackingId, function ($query) use ($trackingId) {
                            $query->where('id', $trackingId);
                        }, function ($query) use ($lspId, $lrNumber) {
                            $query->where('lspId', $lspId)
                                ->where('lrNumber', $lrNumber);
                        });

                    $trackingQuery->where('status', 3)->update(['status' => 1]);
                }
            }
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'EPOD Delete Failed', $request, [
                'epod_id' => $epod->id,
                'lrNumber' => $lrNumber,
                'lspId' => $lspId,
            ]);

            return redirect()->route('v2.epods.index')
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        return redirect()->route('v2.epods.index')
            ->with('message', 'EPOD record and local file deleted successfully.')
            ->with('message_type', 'success');
    }

    private function sendRemoteEpod(Epod $epod, Request $request, bool $reupload): void
    {
        $path = $this->epodPath($epod);
        if (!$path) {
            throw new \RuntimeException('Local EPOD file is missing.');
        }

        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
        $base64 = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($path));

        $reupload
            ? $this->integrations->reuploadEpod($epod, $base64, $request->user())
            : $this->integrations->uploadEpod($epod, $base64, $request->user());
    }

    private function markUploaded(Epod $epod): void
    {
        $epod->status = 1;
        $epod->save();

        Tracking::query()
            ->when($epod->tracking_id, function ($query) use ($epod) {
                $query->where('id', $epod->tracking_id);
            }, function ($query) use ($epod) {
                $query->where('lspId', $epod->lspId)
                    ->where('lrNumber', $epod->lrNumber);
            })
            ->update(['status' => 3]);
    }

    private function clearPendingDrafts(string $lspId, string $lrNumber, string $destination)
    {
        $drafts = Epod::query()
            ->where('lspId', $lspId)
            ->where('lrNumber', $lrNumber)
            ->where('status', 0)
            ->get();

        foreach ($drafts as $draft) {
            $this->deleteLocalFile($draft->epod);
            $draft->delete();
        }
    }

    private function resolveTracking(Epod $epod): ?Tracking
    {
        if ($epod->tracking_id) {
            $tracking = Tracking::query()->find($epod->tracking_id);
            if ($tracking) {
                return $tracking;
            }
        }

        return Tracking::query()
            ->where('lspId', $epod->lspId)
            ->where('lrNumber', $epod->lrNumber)
            ->latest('id')
            ->first();
    }

    private function epodDirectory(): string
    {
        return storage_path('app/epods');
    }

    private function epodPath(Epod $epod): ?string
    {
        $filename = basename((string) $epod->epod);
        if ($filename === '') {
            return null;
        }

        $path = $this->epodDirectory() . DIRECTORY_SEPARATOR . $filename;

        return is_file($path) ? $path : null;
    }

    private function deleteLocalFile(?string $filename): void
    {
        $filename = basename((string) $filename);
        if ($filename === '') {
            return;
        }

        $path = $this->epodDirectory() . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function canManageEpods(Request $request): bool
    {
        $user = $request->user();

        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }
}
