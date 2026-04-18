<?php

namespace App\Http\Controllers;
use App\Models\Epod;
use App\Models\Setting;
use App\Models\Vehicle;
use App\Models\Tracking;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\File\File;


class HomeController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->data['set'] = $this->setting();
        $this->middleware('auth');
        $this->setting = Setting::first();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $this->data['trackingCount'] = Tracking::where('status','0')->count();
        $this->data['vehicleCount'] = Vehicle::count();
        $this->data['trackingCompleteedCount'] = Tracking::where('status','1')->count();
        $this->data['uploadCount'] = Epod::where('status','1')->count();
        try{
            $headers = [
                'Authorization' => "Bearer ".auth()->user()->access_token,
            ];
            $client = new Client(['base_uri' => $this->setting['flee_link']]);
            $response = $client->request('GET', 'analytics/live?',
            [
                    'headers' => $headers,
            ]);
            $data = json_decode((string)$response->getBody(),true);
            $this->data['json_details'] = $data;
        }catch(\Exception $e){
            $this->data['json_details'] = [
                'totalVehicles'   =>    0,
                'runningVehicles' =>    0,
                'parkedVehicles'    =>    0,
                'idleVehicles'    =>    0,
                'inshopVehicles'    =>    0,
                'disconnectedVehicles'    =>    0,
                'unreachableVehicles'    =>    0,
                'immobilisedVehicles'    =>    0,
                'standbyVehicles'    =>    0,
                'batteryDischargedVehicles'    =>    0,
                'nopowerVehicles'    =>    0,
                'utilization'    =>    0,
                'alarms'    =>    0,
            ];
        }

        return view('home', $this->data);

    }


        /**
     * Show the epod.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function epod()
    {
        try {
            $this->data['page_title'] = "Epod List";
            $this->data['epod'] = Epod::where('status', 1)->orderBy('id','DESC')->get();
            return view('pages.epod.index', $this->data);
        } catch (\Exception $e) {
            return back()->with([
                'message' => "Unable To View Index",
                'message_type' => "danger",
            ]);
        }
    }
           /**
     * Show the form for creating a epod.
     *
     * @return \Illuminate\Http\Response
     */
    public function add_epod()
    {
        try {
            $this->data['page_title'] = "Create Lr Epod";
            return view('pages.epod.create', $this->data);
            } catch (\Exception $e) {
                return back()->with([
                    'message' => "Cannot Create Epod",
                    'message_type' => "danger",
                ]);
            }
    }
    /**
     * Upload new ltrtracking .
     *
     * @return \Illuminate\Http\Response
     */
    function epod_upload(Request $request)
    {

        $this->validate($request, [
            'lspId' => "required",
            'lrNumber' => "required",
            'epod' => 'required|mimes:pdf,jpg,jpeg|max:2048',
        ]
        );
        $epods = "category" . rand(1, 1000000) . ".".$request->file('epod')->getClientOriginalExtension();
        if ($request->hasFile('epod')) {
            $file = $request->file('epod');
            $destinationPath = 'upload/epods';
            $file->move($destinationPath, $epods);
        }
        $epod_delete = Epod::where('status', 0)->delete();
        $epod = new Epod();
        $epod->lspId = $request->lspId;
        $epod->lrNumber = $request->lrNumber;
        $epod->epod = $epods;
        $epod->status = 0;
        $epod->save();
        $str = ('upload/epods/'.$epods);

        $files = str_replace('\\', '/', $str);
        $data = file_get_contents($files);
        $base64 = 'data:image/' . 'jpg' . ';base64,' . base64_encode($data);
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer ".auth()->user()->bearer_token
        ];

        $send_data = [
                'lspId' => $request->lspId,
                'lrNumber' => $request->lrNumber,
                'epod' => $base64,
        ];
        // try{
            $client = new Client(['base_uri' => $this->setting['bocsh_link']]);
            $response = $client->request('POST', '/api/lr/epod',
            [
                    'headers' => $headers,
                    'json' => $send_data,
            ]);
            $data = json_decode((string)$response->getBody(),true);
            // dd($data);
            if($data['success'] == 'true'){
                if($data['uploadFlag'] == '0'){
                    return redirect('/epod')
                    ->with('message', $data['message'])
                    ->withErrors(['msg' => $data['message']])
                    ->with('message_type', 'failed');
                }else{
                    $user = Epod::where('id', $epod->id)->Update(['status' => 1]);
                    $vehicle = Tracking::where('lspId', '=', $request->lspId)->where('lrNumber', '=', $request->lrNumber)->update(array('status' => '3'));
                    return redirect('/epod')
                    ->with('message', 'Successfully Upload Epod' . auth()->user()->username)
                    ->with('message_type', 'success');
              
                }


            }else{
                return redirect('/epod')->with('message', $data['message'])->with('message_type', 'failed');
            }
        //     }catch (\Exception $e) {
        //         \Auth::logout();
        //         \Session::flush();
        //         return redirect('/')->with('message', "Session Closed")->with('message_type', 'success');
        // }

    }
}

