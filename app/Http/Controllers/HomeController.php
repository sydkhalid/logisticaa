<?php

namespace App\Http\Controllers;
use App\Models\Epod;
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
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $trackingCount = Tracking::where('status','1')->count();
        $uploadCount = Epod::where('status','1')->count();
        return view('home', compact('trackingCount','uploadCount'));

    }

        /**
     * Show the lr tracking
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function lrtracking()
    {
        try {
            $this->data['page_title'] = "Lr Tracking List";
            $this->data['lrtracking'] = Tracking::where('status', '!=' ,'0')->get();
            return view('pages.lrtracking.index', $this->data);
        } catch (\Exception $e) {
            return back()->with([
                'message' => "Unable To View Index",
                'message_type' => "danger",
            ]);
        }
    }
            /**
     * view the lr tracking
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function showlrtracking($id)
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
     * Show the form for creating a lr track.
     *
     * @return \Illuminate\Http\Response
     */
    public function add_lrtracking()
    {
        try {
            $this->data['page_title'] = "Create Lr Tracking";
            return view('pages.lrtracking.create', $this->data);
            } catch (\Exception $e) {
                return back()->with([
                    'message' => "Cannot Create Lr Tracking",
                    'message_type' => "danger",
                ]);
            }
    }
       /**
     * Upload new ltrtracking .
     *
     * @return \Illuminate\Http\Response
     */
    function ltrtracking_upload(Request $request)
    {
            // dd($request->all());
            $this->validate($request, [
                'lspId' => "required",
                'lrNumber' => "required",
            ]
            );
            $epod_delete = Tracking::where('status', 0)->delete();
            $epod = new Tracking();
            $epod->lspId = $request->lspId;
            $epod->lrNumber = $request->lrNumber;
            $epod->lrStatus = $request->lrStatus;
            $epod->latitude = $request->latitude;
            $epod->longitude = $request->longitude;
            $epod->location = $request->location;
            $epod->pickUpDate = $request->pickUpDate;
            $epod->lrDate = $request->lrDate;
            $epod->actualDeliveredDate = $request->actualDeliveredDate;
            $epod->edd = $request->edd;
            $epod->receiverName = $request->receiverName;
            $epod->deliveredToPerson = $request->deliveredToPerson;
            $epod->actualWeight = $request->actualWeight;
            $epod->numberOfPackages = $request->numberOfPackages;
            $epod->lrNumber = $request->lrNumber;
            $epod->length = $request->length;
            $epod->height = $request->height;
            $epod->truckType = $request->truckType;
            $epod->truckTonnage = $request->truckTonnage;
            $epod->vehicleNo = $request->vehicleNo;
            $epod->deliveryNotes = $request->deliveryNotes;
            $epod->status = 0;
            $epod->save();
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer ".auth()->user()->bearer_token
            ];

            $send_data = [
                'lrTrackingDetails' =>[
                    'lspId' => $request->lspId,
                    'lrNumber' => $request->lrNumber,
                    'lrStatus' => $request->lrStatus,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'location' => $request->location,
                    'pickUpDate' => $request->pickUpDate,
                    'lrDate' => $request->lrDate,
                    'actualDeliveredDate' => "$request->actualDeliveredDate",
                    'edd' => $request->edd,
                    'receiverName' => $request->receiverName,
                    'deliveredToPerson' => $request->deliveredToPerson,
                    'actualWeight' => $request->actualWeight,

                    'numberOfPackages' => $request->numberOfPackages,
                    'length' => $request->length,
                    'breadth' => $request->breadth,
                    'height' => $request->height,
                    'truckType' => $request->truckType,
                    'truckTonnage' => $request->truckTonnage,
                    'vehicleNo' => $request->vehicleNo,
                    'deliveryNotes' => $request->deliveryNotes,

                ]
            ];
            // dd($send_data);
            try{
            $client = new Client(['base_uri' => 'http://trackingapi-dev.bosch-travis.com/']);
            $response = $client->request('POST', '/api/lr/tracking',
            [
                    'headers' => $headers,
                    'json' => $send_data,
            ]);
                $data = json_decode((string)$response->getBody(),true);
                if($data['success'] == 'true'){
                    if($data['message'] = 'Duplicate Record'){
                        $user = Tracking::where('id', $epod->id)->Update(['status' => 2]);
                    }else{
                        $user = Tracking::where('id', $epod->id)->Update(['status' => 2]);
                    }
                    return redirect('/lrtracking')
                    ->with('message', 'Welcome back ' . auth()->user()->username)
                    ->with('message_type', 'success');
                }
            }catch (\Exception $e) {
                \Auth::logout();
                \Session::flush();
                return redirect('/')->with('message', "Session Closed")->with('message_type', 'success');
            }

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
            $this->data['epod'] = Epod::where('status', 1)->get();
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
            'epod' => 'required|mimes:pdf,xlx,csv|max:2048',
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
        $files = public_path('upload/epods/'.$epods);
        $data = file_get_contents($files);
        $base64 = base64_encode($data);
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

        try{
            $client = new Client(['base_uri' => 'http://trackingapi-dev.bosch-travis.com/']);
            $response = $client->request('POST', '/api/lr/tracking',
            [
                    'headers' => $headers,
                    'json' => $send_data,
            ]);
            $data = json_decode((string)$response->getBody(),true);
            if($data['success'] == 'true'){
                $user = Tracking::where('id', $epod->id)->Update(['status' => 1]);
                return redirect('/epod')
                ->with('message', 'Successfully Upload Epod' . auth()->user()->username)
                ->with('message_type', 'success');
            }else{
                return redirect('/epod')->with('message', "Failed to upload data")->with('message_type', 'failed');
            }
            }catch (\Exception $e) {
                \Auth::logout();
                \Session::flush();
                return redirect('/')->with('message', "Session Closed")->with('message_type', 'success');
        }

    }
}
