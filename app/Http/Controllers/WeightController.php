<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tracking;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use App\Http\Controllers\TrackController;
use App\Models\Setting;
use App\Models\Weight;
use Illuminate\Support\Facades\Log;

class WeightController extends Controller
{
    public function __construct()
    {
        $this->setting = Setting::first();
        $this->data['set'] = $this->setting();
    }
    /**
     * Display a listing of the lr tracking.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $this->data['page_title'] = "Weight Of LR List";
            $this->data['lrtracking'] = Weight::get();
            return view('pages.weight.index', $this->data);
        } catch (\Exception $e) {
            return back()->with([
                'message' => "Unable To View Index",
                'message_type' => "danger",
            ]);
        }
    }

    /**
     * Show the form for creating a lr tracking.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // try {
            $this->data['page_title'] = "Create Weight";
            $this->data['lrs'] = Tracking::where('status','2')->get();
            return view('pages.weight.create', $this->data);
            // } catch (\Exception $e) {
            //     return back()->with([
            //         'message' => "Cannot Create Lr Tracking",
            //         'message_type' => "danger",
            //     ]);
            // }
    }

    /**
     * Store a newly created resource in lr tracking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $this->validate($request, [
            'lspId' => "required",
            'lrNumber' => "required",
        ]
        );

        // try{
            $check_weight = Weight::where('lspId', $request->lspId)->where('lrNumber', $request->lrNumber)->count();
            if($check_weight >= 1){
                return redirect('/weight-correction')
                ->with('message', 'Weight Already Added Please Recorrect it')
                ->with('message_type', 'warning');
            }
                $weight = new Weight();
                $weight->lspId = $request->lspId;
                $weight->lrNumber = $request->lrNumber;
                $weight->correctedWeight = $request->actualWeight;
                $weight->length = $request->length;
                $weight->height = $request->height;
                $weight->breadth = $request->breadth;
                $weight->save();
                $get_lrtrack = Weight::where('id',$weight->id)->first();
                $auth = new TrackController();
                $track = $auth->updatetoweight($get_lrtrack,'correction');
            return redirect('/weight-correction')
            ->with('message', 'Weight Added Successfully')
            ->with('message_type', 'success');
        // }catch(\Exception $e) {
        //     return redirect('/weight-correction')
        //     ->with('message', 'Failed To Created Weight')
        //     ->with('message_type', 'failed');
        // }
    }

    public function fetchlr(Request $request)
    {
       
        $lr = Tracking::select('id','lrnumber','actualWeight','length','breadth','height')
        ->where('lrNumber', $request->value)
        ->first();
        if($lr){
            return $lr;
        }else{
            return 0;
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // try {
            $this->data['page_title'] = "Edit Weight";
            $this->data['weight'] = Weight::where('id',$id)->first();
            return view('pages.weight.edit', $this->data);
            // } catch (\Exception $e) {
            //     return back()->with([
            //         'message' => "Cannot Edit Lr Tracking",
            //         'message_type' => "danger",
            //     ]);
            // }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        // try{

                $vehicle = Weight::where('id', '=', $id)->update(
                    array(
                        'correctedWeight' => $request->actualWeight,
                        'length' => $request->length,
                        'breadth' => $request->breadth,
                        'height' => $request->height,
                    )
                );
              $get_lrtrack = Weight::where('id',$id)->first();
              $auth = new TrackController();
              $track = $auth->updatetoweight($get_lrtrack,'recorrection');
              return redirect('/weight-correction')
              ->with('message', 'Weight Recorrected Successfully')
              ->with('message_type', 'success');
        // } catch (\Exception $e) {
        //     return back()->with([
        //         'message' => "Cannot Update Lr Tracking",
        //         'message_type' => "danger",
        //     ]);
        // }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
