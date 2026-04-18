<?php

namespace App\Http\Controllers\V2;

use App\Models\Tracking;
use App\Models\Weight;
use Illuminate\Http\Request;

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
            'weights' => Weight::query()->latest('id')->get(),
        ]);
    }

    public function create()
    {
        return $this->render('weight-corrections.form', [
            'pageTitle' => 'Add Weight Correction',
            'weight' => new Weight(),
            'recentTrackings' => Tracking::query()->latest('id')->limit(20)->get(['lrNumber']),
            'formAction' => route('v2.weight-corrections.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lspId' => ['required', 'string'],
            'lrNumber' => ['required', 'string'],
            'actualWeight' => ['required'],
            'length' => ['required'],
            'breadth' => ['required'],
            'height' => ['required'],
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

        $weight = new Weight();
        $weight->lspId = $validated['lspId'];
        $weight->lrNumber = $validated['lrNumber'];
        $weight->correctedWeight = $validated['actualWeight'];
        $weight->length = $validated['length'];
        $weight->breadth = $validated['breadth'];
        $weight->height = $validated['height'];
        $weight->save();

        try {
            $this->integrations->syncWeightCorrection($weight, false, $request->user());
            $message = 'Weight correction saved successfully.';
            $messageType = 'success';
        } catch (\Throwable $exception) {
            $message = 'Weight saved, but sync failed: ' . $exception->getMessage();
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
            'recentTrackings' => Tracking::query()->latest('id')->limit(20)->get(['lrNumber']),
            'formAction' => route('v2.weight-corrections.update', $weight),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, Weight $weight)
    {
        $validated = $request->validate([
            'actualWeight' => ['required'],
            'length' => ['required'],
            'breadth' => ['required'],
            'height' => ['required'],
        ]);

        $weight->correctedWeight = $validated['actualWeight'];
        $weight->length = $validated['length'];
        $weight->breadth = $validated['breadth'];
        $weight->height = $validated['height'];
        $weight->save();

        try {
            $this->integrations->syncWeightCorrection($weight, true, $request->user());
            $message = 'Weight re-corrected successfully.';
            $messageType = 'success';
        } catch (\Throwable $exception) {
            $message = 'Weight updated, but sync failed: ' . $exception->getMessage();
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
            ->select('lrNumber', 'actualWeight', 'length', 'breadth', 'height')
            ->where('lrNumber', $validated['lrNumber'])
            ->first();

        if (!$tracking) {
            return response()->json([
                'message' => 'LR number not found.',
            ], 404);
        }

        return response()->json($tracking);
    }
}
