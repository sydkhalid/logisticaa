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
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lspId' => ['required', 'string'],
            'lrNumber' => ['required', 'string'],
            'epod' => ['required', 'file', 'mimes:pdf,jpg,jpeg', 'max:2048'],
        ]);

        $file = $request->file('epod');
        $filename = 'category' . random_int(1, 1000000) . '.' . $file->getClientOriginalExtension();
        $destination = base_path('upload/epods');

        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        Epod::query()->where('status', 0)->delete();
        $file->move($destination, $filename);

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
            $response = $this->integrations->uploadEpod($epod, $base64, $request->user());
        } catch (\Throwable $exception) {
            return redirect()->route('v2.epods.index')
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        if (($response['success'] ?? null) === 'true' && ($response['uploadFlag'] ?? null) !== '0') {
            $epod->status = 1;
            $epod->save();

            Tracking::query()
                ->where('lspId', $validated['lspId'])
                ->where('lrNumber', $validated['lrNumber'])
                ->update(['status' => 3]);

            return redirect()->route('v2.epods.index')
                ->with('message', 'EPOD uploaded successfully.')
                ->with('message_type', 'success');
        }

        return redirect()->route('v2.epods.index')
            ->with('message', $response['message'] ?? 'EPOD upload failed.')
            ->with('message_type', 'warning');
    }
}
