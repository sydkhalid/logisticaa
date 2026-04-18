<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tracking;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use App\Http\Controllers\TrackController;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class LrtrackingController extends Controller
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
            $this->data['page_title'] = "Lr Tracking List";
            $this->data['lrtracking'] = Tracking::where('status', '0')->get();
            return view('pages.lrtracking.index', $this->data);
        } catch (\Exception $e) {
            return back()->with([
                'message' => "Unable To View Index",
                'message_type' => "danger",
            ]);
        }
    }
       /**
     * Display a listing of the lr tracking.
     *
     * @return \Illuminate\Http\Response
     */
    public function delivered_list()
    {
        try {
            $this->data['page_title'] = "Lr Tracking List Completed";
            $this->data['lrtracking'] = Tracking::where('status', '1')->get();
            return view('pages.lrtracking.delivered_list', $this->data);
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
        try {
            $this->data['page_title'] = "Create Lr Tracking";
            $this->data['vehicles'] = Vehicle::get();
            return view('pages.lrtracking.create', $this->data);
            } catch (\Exception $e) {
                return back()->with([
                    'message' => "Cannot Create Lr Tracking",
                    'message_type' => "danger",
                ]);
            }
    }

    /**
     * Store a newly created resource in lr tracking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
     
        $this->validate($request, [
            'lspId' => "required",
            'lrNumber' => "required",
        ]
        );
    
        try{
            $getvehilcle = Vehicle::where('id', $request->vehicleNo)->first();
            $pickUpDate = date("Y-m-d h:m:s", strtotime($request->pickUpDate));
            $lrDate = date("Y-m-d h:m:s", strtotime($request->lrDate));
            $edd = date("Y-m-d h:m:s", strtotime($request->edd));

            $check_tracking = Tracking::where('lspId', $request->lspId)->where('vehicleNo', $getvehilcle->vehicleNo)->where('status', 0)->count();
            if($check_tracking >= 1){
                return redirect('/lrtracking')
                ->with('message', 'Vehical Already Added')
                ->with('message_type', 'warning');
            }
            $epod = new Tracking();
                $epod->lspId = $request->lspId;
                $epod->lrNumber = $request->lrNumber;
                $epod->lrStatus = $request->lrStatus;
                $epod->latitude = '';
                $epod->longitude = '';
                $epod->location = '';
                $epod->pickUpDate = $pickUpDate;
                $epod->lrDate = $lrDate;
                $epod->actualDeliveredDate = '';
                $epod->edd = $edd;
                $epod->receiverName = $request->receiverName;
                $epod->deliveredToPerson = $request->deliveredToPerson;
                $epod->actualWeight = $request->actualWeight;
                $epod->numberOfPackages = $request->numberOfPackages;
                $epod->lrNumber = $request->lrNumber;
                $epod->length = $request->length;
                $epod->height = $request->height;
                $epod->breadth = $request->breadth;
                $epod->truckType = $request->truckType;
                $epod->truckTonnage = $request->truckTonnage;
                $epod->vehicleNo = $getvehilcle->vehicleNo;
                $epod->deliveryNotes = $request->deliveryNotes;
                $epod->status = 0;
                $epod->vehicle_status = $getvehilcle->vehicleStatus;
                $epod->save();
            $get_lrtrack = Tracking::where('id', $epod->id)->first();
            try{
                $auth = new TrackController();
                $track = $auth->updatetobocsh($get_lrtrack);
            }catch(\Throwable $syncError){
                Log::error('LR created but tracking sync failed', [
                    'tracking_id' => $epod->id,
                    'vehicleNo' => $get_lrtrack ? $get_lrtrack->vehicleNo : null,
                    'error' => $syncError->getMessage(),
                ]);
            }
            return redirect('/lrtracking')
            ->with('message', 'Lr Tracking Added Successfully')
            ->with('message_type', 'success');
        }catch(\Throwable $e) {
            Log::error('Failed to create LR tracking', [
                'error' => $e->getMessage(),
            ]);
            return redirect('/lrtracking')
            ->with('message', 'Failed To Created Lr Tracking')
            ->with('message_type', 'failed');
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
        try {
            $this->data['page_title'] = "Lr Tracking Show";
            $this->data['lrtracking'] = Tracking::where('id',$id)->first();
            return view('pages.lrtracking.show', $this->data);
        } catch (\Exception $e) {
            return back()->with([
                'message' => "Unable To View Index",
                'message_type' => "danger",
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $this->data['page_title'] = "Edit Lr Tracking";
            $this->data['lrtracking'] = Tracking::where('id',$id)->first();
            return view('pages.lrtracking.edit', $this->data);
            } catch (\Exception $e) {
                return back()->with([
                    'message' => "Cannot Edit Lr Tracking",
                    'message_type' => "danger",
                ]);
            }
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
        // try{
            if($request->lrStatus == 'Shipment Delivered'){

                $actualDeliveredDate = date("Y-m-d h:m:s", strtotime($request->actualDeliveredDate));
                $vehicle = Tracking::where('id', '=', $id)->update(
                    array(
                        'lrStatus' => $request->lrStatus,
                        'actualDeliveredDate' => $actualDeliveredDate,
                        'status' => 1
                    )
                );
              }else{
                $vehicle = Tracking::where('id', '=', $id)->update(
                    array(
                        'lrStatus' => $request->lrStatus,
                    )
                );
              }
              $get_lrtrack = Tracking::where('id',$id)->first();
              try{
                  $auth = new TrackController();
                  $track = $auth->updatetobocsh($get_lrtrack);
              }catch(\Throwable $syncError){
                  Log::error('LR updated but tracking sync failed', [
                      'tracking_id' => $id,
                      'vehicleNo' => $get_lrtrack ? $get_lrtrack->vehicleNo : null,
                      'error' => $syncError->getMessage(),
                  ]);
              }
              return redirect('/lrtracking')
              ->with('message', 'Lr Tracking Updated Successfully')
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
