<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Vehicle;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function __construct()
    {
        $this->setting = Setting::first();
        $this->data['set'] = $this->setting();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $this->data['page_title'] = "Vehicle List";
            $this->data['vehicles'] = Vehicle::where('vehicleStatus',0)->get();
            return view('pages.vehicle.index', $this->data);
        } catch (\Exception $e) {
            return back()->with([
                'message' => "Unable To View Index",
                'message_type' => "danger",
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {
            $this->data['page_title'] = "Create Vehicle";
            return view('pages.vehicle.create', $this->data);
            } catch (\Exception $e) {
                return back()->with([
                    'message' => "Cannot Create Vehicle",
                    'message_type' => "danger",
                ]);
            }
    }
    public function fetch_vehicles(Request $request)
    {
        $vehicle_no = Vehicle::where('id',$request['value'])->first();
            if($vehicle_no['vehicleStatus'] == 1){
                $headers = [
                    'Authorization' => "Bearer ".auth()->user()->access_token,
                    'Content-Type' => 'application/json',
                ];
                $client = new Client(['base_uri' => $this->setting['flee_link']]);
                $response = $client->request('GET', 'analytics/live?',
                [
                        'headers' => $headers,
                ]);
                $data = json_decode((string)$response->getBody(),true);
                
                foreach($data['vehicles'] as $key=>$truck){
                    if($truck['vehicleNumber'] == strtoupper($vehicle_no['vehicleNo'])){
                        print_r(0);
                    }
                }
            }
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'vehicleNo' => "required|unique:vehicles",
        ]
        );
        try{
            $vehicle = new Vehicle();
            $vehicle->vehicleNo = $request->vehicleNo;
            $vehicle->vehicleStatus = 0;
            $vehicle->save();

            return redirect('/vehicle')
            ->with('message', 'Successfully Created Vehicle Number' . auth()->user()->username)
            ->with('message_type', 'success');

        }catch(\Exception $e) {
            return redirect('/vehicle')
            ->with('message', 'Failed To Created Vehicle Number')
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
        try{
            $vehicle_no = Vehicle::where('id',$id)->first();
            $client = new Client(['base_uri' => $this->setting['tracing_link']]);
            $response = $client->request('get', 'currentLoc?accessToken='.$this->setting['address']);
            $data = json_decode((string)$response->getBody(),true);
            $vehicle_list = '';
            $this->data['page_title'] = "Vehicle Details";
            foreach($data['data']['list'] as $key=>$truck){
                if($truck['vehicleNumber'] == $vehicle_no['vehicleNo']){
                        $vehicle_list = $data['data']['list'][$key];
                }
            }
            $this->data['vehicle_details'] = $vehicle_list;
            return view('pages.vehicle.show', $this->data);
        } catch (\Exception $e) {
            return back()->with([
                'message' => "No Vehicle Available At the moment ",
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
            $this->data['page_title'] = "Edit Vehicle";
            $this->data['vehicle_details'] = Vehicle::where('id',$id)->first();
            return view('pages.vehicle.edit', $this->data);
            } catch (\Exception $e) {
                return back()->with([
                    'message' => "Cannot Edit Vehicle",
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
        $this->validate($request, [
            'vehicleNo' => "required|unique:vehicles",
        ]
        );
        try{
            $vehicle = Vehicle::where('id', '=', $id)->update(array('vehicleNo' => $request->vehicleNo));

            return redirect('/vehicle')
            ->with('message', 'Successfully Updated Vehicle Number' . auth()->user()->username)
            ->with('message_type', 'success');

        }catch(\Exception $e) {
            return redirect('/vehicle')
            ->with('message', 'Failed To Updated Vehicle Number')
            ->with('message_type', 'failed');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $vehicle = Vehicle::find($id);
            $vehicle->delete();
            return back()->with([
                'message' => "Vehicle Number $vehicle->VehicleNo was removed!",
                'message_type' => "success"
            ]);
            }  catch (\Exception $e) {
                return back()->with([
                    'message' => "Unable To Delete Vehicle Number",
                    'message_type' => "danger",
                ]);
            }
    }
}
