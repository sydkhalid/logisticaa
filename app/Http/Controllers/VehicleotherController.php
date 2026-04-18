<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;

class VehicleotherController extends Controller
{
    public function __construct()
    {
        $this->setting = Setting::first();
        $this->data['set'] = $this->setting();
        $headers = [
            'Authorization' => 'Basic ZmxlZXR4OnNlY3JldA==',
        ];

        $data = [];
        $data['username'] = 'API_User_Dont_Delete_10087';
        $data['password'] = 'sPQe45lW';
        $data['grant_type'] = 'password';
        // dd($data);
       $formData = $data;

        $client = new Client(['base_uri' => $this->setting['flee_link']]);
                $response = $client->request('POST', 'login',
                [
                        'headers' => $headers,
                        'form_params' =>  $formData
                ]);
        $data = json_decode((string)$response->getBody(),true);
        $user = User::where('id', 1)->Update(['access_token' => $data['access_token']]);
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
            $this->data['vehicles'] = Vehicle::where('vehicleStatus',1)->get();
            return view('pages.vehicleother.index', $this->data);
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
            $this->data['page_title'] = "Create Sim Base Vehicle";
            return view('pages.vehicleother.create', $this->data);
            } catch (\Exception $e) {
                return back()->with([
                    'message' => "Cannot Create Vehicle",
                    'message_type' => "danger",
                ]);
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
        $date = date_create($request->expireDate);
        $expireDate = (date_format($date,"Y-m-d H:i:s"));
        $this->validate($request, [
            // 'vehicleNo' => "required|unique:vehicles",
            'mobileNo' => "required",
            'expireDate' => "required",
            'simProvider' => "required",
        ]
        );
        try{

            $headers = [
                // 'Accept' => 'application/json',
                'Authorization' => "Bearer ".auth()->user()->access_token,
                'Content-Type' => 'application/json',
            ];
            $send_data = [
                'mobileNumber' => $request->mobileNo,
                'vehicleNumber' => $request->vehicleNo,
                'expiryDate' => $expireDate,
                'simProvider' => $request->simProvider,
                'pingFrequency' =>'3600'
            ];
            $client = new Client(['base_uri' => $this->setting['flee_link']]);
            $response = $client->request('POST', 'tp/tracking/sim',
            [
                    'headers' => $headers,
                    'json' => $send_data,
            ]);
            $data = json_decode((string)$response->getBody(),true);

            $vehicle = new Vehicle();
            $vehicle->vehicleNo = $request->vehicleNo;
            $vehicle->mobileNo = $request->mobileNo;
            $vehicle->expireDate = $expireDate;
            $vehicle->simProvider = $request->simProvider;
            $vehicle->vehicleStatus = 1;
            $vehicle->save();

            return redirect('/vehicleOther')
            ->with('message', 'Successfully Created Vehicle With Sim' . auth()->user()->username)
            ->with('message_type', 'success');
        }catch(\Exception $e) {
            return redirect('/vehicleOther')
            ->with('message', 'Failed To Created Vehicle With Sim')
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
                    $k =  $data['vehicles'][$key];
                }
            }
            $this->data['vehicle_details'] = $k;
            $this->data['page_title'] = "Vehicle Details";
            return view('pages.vehicleother.show', $this->data);
        } catch (\Exception $e) {
            return back()->with([
                'message' => "Waiting For Approve From Flee Api ",
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
            $this->data['page_title'] = "Change New Mobile";
            $this->data['vehicle_details'] = Vehicle::where('id',$id)->first();
            return view('pages.vehicleother.edit', $this->data);
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
        $date = date_create($request->expireDate);
        $expireDate = (date_format($date,"Y-m-d H:i:s"));
        try{
            $headers = [
                'Authorization' => "Bearer ".auth()->user()->access_token,
                'Content-Type' => 'application/json',
            ];
            $send_data = [
                'mobileNumber' => $request->mobileNo,
                'vehicleNumber' => $request->vehicleNo,
                'expiryDate' => $expireDate,
                'simProvider' => $request->simProvider,
                'pingFrequency' =>'3600'
            ];
            $client = new Client(['base_uri' => $this->setting['flee_link']]);
            $response = $client->request('POST', 'tp/tracking/sim',
            [
                    'headers' => $headers,
                    'json' => $send_data,
            ]);
            $data = json_decode((string)$response->getBody(),true);
            $vehicle = Vehicle::where('id', '=', $id)->update(array('mobileNo' => $request->mobileNo,'expireDate' => $expireDate,'simProvider' => $request->simProvider));
            return redirect('/vehicle')
            ->with('message', 'Successfully Updated Vehicle Number' . auth()->user()->username)
            ->with('message_type', 'success');

        }catch(\Exception $e) {
            return redirect('/vehicle')
            ->with('message', 'Failed To Updated Vehicle Number')
            ->with('message_type', 'failed');
        }
    }
    public function vehiclecheckTrack($id){
        $vehicle_no = Vehicle::where('id',$id)->first();
        try{
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
                                $k =  $data['vehicles'][$key]['currentStatus'];
                                return back()->with([
                                    'message' => 'Status '.$k,
                                    'message_type' => "success",
                                ]);
                            }
                            // else{
                            //     return back()->with([
                            //         'message' => 'Contact to Flee Api',
                            //         'message_type' => "danger",
                            //     ]);
                            // }
                            }
        }catch (BadResponseException $e) {
            return back()->with([
                'message' => 'API Error',
                'message_type' => "danger",
            ]);
        }
    }
    public function vehicleStopTrack($id){
            try{
                $vehicle_no = Vehicle::where('id',$id)->first();
                $headers = [
                    // 'Accept' => 'application/json',
                    'Authorization' => "Bearer ".auth()->user()->access_token,
                    'Content-Type' => 'application/json',
                ];
                $data = [];
                $data['mobileNumber'] =  $vehicle_no['mobileNo'];
                $data['simProvider'] =  $vehicle_no['simProvider'];
                $formData = $data;
                $client = new Client(['base_uri' => $this->setting['flee_link']]);
                $response = $client->request('DELETE', 'devices/sim/',
                [
                        'headers' => $headers,
                        'json' =>  $formData
                ]);
                $data = json_decode((string)$response->getBody(),true);
                $vehicle = Vehicle::where('id', '=', $id)->update(array('statusStop' =>'1'));
                return back()->with([
                    'message' => "Vehicle Number was removed!",
                    'message_type' => "success"
                ]);


            }  catch (BadResponseException $e) {
                return back()->with([
                    'message' => 'API Error',
                    'message_type' => "danger",
                ]);
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
