<?php

namespace App\Http\Controllers\V2;

use App\Jobs\SyncWeightCorrectionJob;
use App\Models\Tracking;
use App\Models\Weight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class WeightCorrectionController extends BaseController
{
    public function __construct(\App\Services\V2\ExternalLogisticsService $integrations)
    {
        parent::__construct($integrations);
        $this->middleware('auth');
    }

    public function index()
    {
        return $this->render('weight-corrections.index', [
            'pageTitle' => 'Weight Corrections',
        ]);
    }

    public function data(Request $request)
    {
        $query = Weight::query()
            ->select(['id', 'lrNumber', 'correctedWeight', 'length', 'breadth', 'height', 'created_at'])
            ->latest('created_at');

        return $this->datatableResponse(
            $request,
            $query,
            ['lrNumber', 'correctedWeight', 'length', 'breadth', 'height'],
            ['created_at', 'lrNumber', 'correctedWeight', 'length', 'breadth', 'height', null],
            function (Weight $weight, int $index) {
                return [
                    'index' => $index,
                    'lrNumber' => e($weight->lrNumber),
                    'correctedWeight' => e($weight->correctedWeight),
                    'length' => e($weight->length),
                    'breadth' => e($weight->breadth),
                    'height' => e($weight->height),
                    'actions' => $this->actionGroup([
                        $this->actionLink(route('v2.weight-corrections.edit', $weight), 'Re-Correct', 'btn-outline-primary'),
                    ]),
                ];
            }
        );
    }

    public function create()
    {
        return $this->render('weight-corrections.form', [
            'pageTitle' => 'Add Weight Correction',
            'weight' => new Weight(),
            'recentTrackings' => Tracking::query()->latest('created_at')->limit(20)->get(['lrNumber']),
            'defaultLspId' => $this->defaultLspId(),
            'formAction' => route('v2.weight-corrections.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lspId' => ['required', 'string'],
            'lrNumber' => ['required', 'string'],
            'actualWeight' => ['required', 'numeric', 'min:0'],
            'length' => ['required', 'numeric', 'min:0'],
            'breadth' => ['required', 'numeric', 'min:0'],
            'height' => ['required', 'numeric', 'min:0'],
        ]);

        $exists = Weight::query()
            ->where('lspId', $validated['lspId'])
            ->where('lrNumber', $validated['lrNumber'])
            ->exists();

        if ($exists) {
            return redirect()->route('v2.weight-corrections.index')
                ->with('message', 'Weight already exists. Use re-correction instead.')
                ->with('message_type', 'warning');
        }

        $tracking = $this->findTracking($validated['lspId'], $validated['lrNumber']);

        $weight = new Weight();
        $weight->lspId = $validated['lspId'];
        $weight->lrNumber = $validated['lrNumber'];
        $weight->correctedWeight = $validated['actualWeight'];
        $weight->length = $validated['length'];
        $weight->breadth = $validated['breadth'];
        $weight->height = $validated['height'];
        if ($tracking && Schema::hasColumn('weights', 'tracking_id')) {
            $weight->tracking_id = $tracking->id;
        }

        try {
            $weight->save();
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Weight Correction Create Failed', $request, [
                'lrNumber' => $weight->lrNumber,
                'lspId' => $weight->lspId,
            ]);

            return back()
                ->withInput()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        try {
            $this->queueWeightSync($weight, false, $request, 'weight-correction-create');
            $message = 'Weight correction saved. Travis sync has been queued.';
            $messageType = 'success';
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Weight Correction Sync Queue Failed After Create', $request, [
                'weight_id' => $weight->id,
                'lrNumber' => $weight->lrNumber,
                'lspId' => $weight->lspId,
            ], 'warning');
            $message = 'Weight saved, but Travis sync could not be queued: ' . $exception->getMessage();
            $messageType = 'warning';
        }

        return redirect()->route('v2.weight-corrections.index')
            ->with('message', $message)
            ->with('message_type', $messageType);
    }

    public function edit(Weight $weight)
    {
        return $this->render('weight-corrections.form', [
            'pageTitle' => 'Re-Correct Weight',
            'weight' => $weight,
            'recentTrackings' => Tracking::query()->latest('created_at')->limit(20)->get(['lrNumber']),
            'defaultLspId' => $this->defaultLspId(),
            'formAction' => route('v2.weight-corrections.update', $weight),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, Weight $weight)
    {
        $validated = $request->validate([
            'actualWeight' => ['required', 'numeric', 'min:0'],
            'length' => ['required', 'numeric', 'min:0'],
            'breadth' => ['required', 'numeric', 'min:0'],
            'height' => ['required', 'numeric', 'min:0'],
        ]);

        $weight->correctedWeight = $validated['actualWeight'];
        $weight->length = $validated['length'];
        $weight->breadth = $validated['breadth'];
        $weight->height = $validated['height'];
        if (!$weight->tracking_id && Schema::hasColumn('weights', 'tracking_id')) {
            $tracking = $this->findTracking($weight->lspId, $weight->lrNumber);
            if ($tracking) {
                $weight->tracking_id = $tracking->id;
            }
        }

        try {
            $weight->save();
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Weight Correction Update Failed', $request, [
                'weight_id' => $weight->id,
                'lrNumber' => $weight->lrNumber,
                'lspId' => $weight->lspId,
            ]);

            return back()
                ->withInput()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        try {
            $this->queueWeightSync($weight, true, $request, 'weight-correction-update');
            $message = 'Weight re-corrected. Travis sync has been queued.';
            $messageType = 'success';
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Weight Correction Sync Queue Failed After Update', $request, [
                'weight_id' => $weight->id,
                'lrNumber' => $weight->lrNumber,
                'lspId' => $weight->lspId,
            ], 'warning');
            $message = 'Weight updated, but Travis sync could not be queued: ' . $exception->getMessage();
            $messageType = 'warning';
        }

        return redirect()->route('v2.weight-corrections.index')
            ->with('message', $message)
            ->with('message_type', $messageType);
    }

    public function fetchLr(Request $request)
    {
        $validated = $request->validate([
            'lrNumber' => ['required', 'string'],
        ]);

        $tracking = Tracking::query()
            ->select('lspId', 'lrNumber', 'actualWeight', 'numberOfPackages', 'length', 'breadth', 'height')
            ->where('lrNumber', $validated['lrNumber'])
            ->first();

        if (!$tracking) {
            return response()->json([
                'message' => 'LR number not found.',
            ], 404);
        }

        return response()->json($tracking);
    }

    private function queueWeightSync(Weight $weight, bool $recorrection, Request $request, string $reason): void
    {
        SyncWeightCorrectionJob::dispatch(
            (int) $weight->id,
            $recorrection,
            optional($request->user())->id,
            $reason
        );
    }

    private function findTracking(?string $lspId, ?string $lrNumber): ?Tracking
    {
        if (!$lspId || !$lrNumber) {
            return null;
        }

        return Tracking::query()
            ->where('lspId', $lspId)
            ->where('lrNumber', $lrNumber)
            ->latest('created_at')
            ->first();
    }
}
