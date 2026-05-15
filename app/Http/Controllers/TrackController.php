<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Tracking;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;

class TrackController extends Controller
{
    public function __construct()
    {
        $this->setting = Setting::first();
        $this->data['set'] = $this->setting();
    }

    /**
     * Get authenticate from bocsh
     * @return object
     */
    function getauthenticate()
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        $send_data = [
            'userDetails' => [
                'emailId' => $this->travisSystemEmail(),
                'password' => $this->travisSystemPassword(),
            ],
        ];
        $client = new Client(['base_uri' => $this->setting['bocsh_link']]);
                $response = $client->request('POST', 'api/auth/login',
                [
                        'headers' => $headers,
                        'json' => $send_data,
                ]);
        $data = json_decode((string)$response->getBody(),true);
        $user = User::where('id', 1)->Update(['bearer_token' => $data['token']]);
        return $data;
    }

    private function travisSystemEmail(): string
    {
        $systemUser = User::query()->first();
        $email = trim((string) env('TRAVIS_SYSTEM_EMAIL', ''));

        if ($email !== '') {
            return $email;
        }

        if ($systemUser && $systemUser->email) {
            return $systemUser->email;
        }

        return 'connect@logisticaa.co.in';
    }

    private function travisSystemPassword(): string
    {
        $password = trim((string) env('TRAVIS_SYSTEM_PASSWORD', ''));

        if ($password === '') {
            throw new \RuntimeException('Travis system password is not configured.');
        }

        return $password;
    }

    /**
     * Get Get Lr Tracking
     * @return object
     */
    function getlrtracking($data)
    {
        foreach($data as $key=>$lrlist){
                $getvehilcle = Vehicle::where('vehicleNo', strtoupper($lrlist['vehicleNo']))->first();
                Log::info($getvehilcle);
                    if($getvehilcle['vehicleStatus'] == 0){
                            $list = self::wheelseyeapi($lrlist['vehicleNo']);
                        }else{
                            $list = self::fleeapi($lrlist['vehicleNo']);
                        }
            $save = self::sendtobocsh($lrlist['vehicleNo']);
        }
    }
     /**
     * Get send To Bocsh
     * @return object
     */
    function sendbocsh($data){
         $user = User::where('id', 1)->first();
         foreach($data as $key=>$lrlist){
             if($lrlist['latitude'] != ''){
                   $headers = [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer ".$user['bearer_token']
                ];
                    $date = '';
                    if($lrlist['actualDeliveredDate']){
                        $date = $lrlist['actualDeliveredDate'];
                    }
                $send_data = [
                    'lrTrackingDetails' =>[
                        'lspId' => $lrlist['lspId'],
                        'lrNumber' => $lrlist['lrNumber'],
                        'lrStatus' => $lrlist['lrStatus'],
                        'latitude' => $lrlist['latitude'],
                        'longitude' => $lrlist['longitude'],
                        'location' => $lrlist['location'],
                        'pickUpDate' => $lrlist['pickUpDate'],
                        'lrDate' => $lrlist['lrDate'],
                        'actualDeliveredDate' => $date,
                        'edd' => $lrlist->edd,
                        'receiverName' => $lrlist['receiverName'],
                        'deliveredToPerson' => $lrlist['deliveredToPerson'],
                        'actualWeight' => $lrlist['actualWeight'],
                        'numberOfPackages' => $lrlist['numberOfPackages'],
                        'length' => $lrlist['length'],
                        'breadth' => $lrlist['breadth'],
                        'height' => $lrlist['height'],
                        'truckType' => $lrlist['truckType'],
                        'truckTonnage' => $lrlist['truckTonnage'],
                        'vehicleNo' => $lrlist['vehicleNo'],
                        'deliveryNotes' => $lrlist['deliveryNotes'],
                    ]
                ];
                $client = new Client(['base_uri' => $this->setting['bocsh_link']]);
                $response = $client->request('POST', '/api/lr/tracking',
                [
                        'headers' => $headers,
                        'json' => $send_data,
                ]);
                    $responsefrombocsh = json_decode((string)$response->getBody(),true);
                    Log::info($lrlist['vehicleNo'].'-'.$responsefrombocsh['message']);
             }
         }
    }
    /**
     * Get send To Bocsh
     * @return object
     */
    function sendtobocsh($lrNumber){
        $user = User::where('id', 1)->first();
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer ".$user['bearer_token']
        ];
        $lrdetails = Tracking::where('lrNumber', $lrNumber)->orderBy('id', 'DESC')->first();

        $date = '';
        if($lrdetails->actualDeliveredDate){
            $date = $lrdetails->actualDeliveredDate;
        }
        $send_data = [
            'lrTrackingDetails' =>[
                'lspId' => $lrdetails->lspId,
                'lrNumber' => $lrdetails->lrNumber,
                'lrStatus' => $lrdetails->lrStatus,
                'latitude' => $lrdetails->latitude,
                'longitude' => $lrdetails->longitude,
                'location' => $lrdetails->location,
                'pickUpDate' => $lrdetails->pickUpDate,
                'lrDate' => $lrdetails->lrDate,
                'actualDeliveredDate' => $date,
                'edd' => $lrdetails->edd,
                'receiverName' => $lrdetails->receiverName,
                'deliveredToPerson' => $lrdetails->deliveredToPerson,
                'actualWeight' => $lrdetails->actualWeight,
                'numberOfPackages' => $lrdetails->numberOfPackages,
                'length' => $lrdetails->length,
                'breadth' => $lrdetails->breadth,
                'height' => $lrdetails->height,
                'truckType' => $lrdetails->truckType,
                'truckTonnage' => $lrdetails->truckTonnage,
                'vehicleNo' => $lrdetails->vehicleNo,
                'deliveryNotes' => $lrdetails->deliveryNotes,
            ]
        ];
        // Log::info($send_data);
        $client = new Client(['base_uri' => $this->setting['bocsh_link']]);
        $response = $client->request('POST', '/api/lr/tracking',
        [
                'headers' => $headers,
                'json' => $send_data,
        ]);
            $responsefrombocsh = json_decode((string)$response->getBody(),true);
            Log::info($responsefrombocsh);
            Log::info('<br>');
            Log::info($lrdetails->vehicleNo);
            Log::info('<br>');
            Log::info($lrdetails->lrNumber);
    }
     /**
     * track Wheels Eye
     * @return object
     */
    function wheelseyeapi($vehicleNo){
        $client = new Client(['base_uri' => $this->setting['tracing_link']]);
        $response = $client->request('get', 'currentLoc?accessToken='.$this->setting['address']);
        $data = json_decode((string)$response->getBody(),true);
        $vehicle_list = '';
        foreach($data['data']['list'] as $key=>$truck){
        if($truck['vehicleNumber'] == $vehicleNo){
                $vehicle_list = $data['data']['list'][$key];
                $vehicle = Tracking::where('vehicleNo', '=', strtoupper($vehicle_list['vehicleNumber']))
                ->update(array(
                    'latitude' => $vehicle_list['latitude'],
                    'longitude' =>$vehicle_list['longitude'],
                    'location' => $vehicle_list['location']
                ));
        }
        }
        }
    /**
     * Flee Api
     * @return object
     */
    function fleeapi($vehicleNo){
        $headers = [
            'Authorization' => "Bearer ".$this->setting['access_token'],
            'Content-Type' => 'application/json',
        ];
        $client = new Client(['base_uri' => $this->setting['flee_link']]);
        try {
            $response = $client->request('GET', 'analytics/live?',
            [
                    'headers' => $headers,
            ]);
            $data = json_decode((string)$response->getBody(),true);
        } catch (ClientException $e) {
            $errorBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : null;
            Log::error('FleetX API error in fleeapi', [
                'vehicleNo' => strtoupper($vehicleNo),
                'status' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null,
                'response' => $errorBody,
            ]);
            return null;
        }

        if (!isset($data['vehicles']) || !is_array($data['vehicles'])) {
            Log::warning('FleetX API returned invalid payload in fleeapi', [
                'vehicleNo' => strtoupper($vehicleNo),
                'payload' => $data,
            ]);
            return null;
        }

        foreach($data['vehicles'] as $truck){
            if(isset($truck['vehicleNumber']) && $truck['vehicleNumber'] == strtoupper($vehicleNo)){
                $vehicle = Tracking::where('vehicleNo', '=', strtoupper($vehicleNo))
                ->update(array(
                    'latitude' => $truck['latitude'] ?? null,
                    'longitude' => $truck['longitude'] ?? null,
                    'location' => $truck['address'] ?? null
                ));
                break;
            }
        }
        }
    /**
     * Single Lr tracking
     * @return object
     */
    function getsinglelrtracking($vehicleNo,$lrnumber)
    {
        $getvehilcle = Vehicle::where('vehicleNo', $vehicleNo)->first();
            if($getvehilcle['vehicleStatus'] == 0){
                $list = self::gettrackingdetails($vehicleNo);
                if(!isset($list['list']) || empty($list['list'])){
                    return redirect('/lrtracking')
                    ->with('message', 'Tracking data not available for vehicle '.strtoupper($vehicleNo))
                    ->with('message_type', 'danger');
                }
                        $vehicle = Tracking::where('vehicleNo', '=', strtoupper($vehicleNo))->where('lrnumber', '=',$lrnumber)->update(array('latitude' => $list['list']['latitude'],
                    'longitude' => $list['list']['longitude'],
                    'location' => $list['list']['location']));
            }else{
                $list = self::getfleetrackingdetails($vehicleNo);
                if(!isset($list['list']) || empty($list['list'])){
                    return redirect('/lrtracking')
                    ->with('message', 'FleetX token invalid or tracking data unavailable for vehicle '.strtoupper($vehicleNo))
                    ->with('message_type', 'danger');
                }
                $vehicle = Tracking::where('vehicleNo', '=', strtoupper($vehicleNo))->where('lrnumber', '=',$lrnumber)->update(array('latitude' => $list['list']['latitude'],
                'longitude' => $list['list']['longitude'],
                'location' => $list['list']['address']));
            }
            $save = self::sendtobocsh($lrnumber);
            return redirect('/lrtracking')
            ->with('message', 'Successfully Track Vehicle' . auth()->user()->username)
            ->with('message_type', 'success');
    }
    /**
     * Get Wheels Eye Tracking Details
     * @return object
     */
    function gettrackingdetails($vehicleNo)
    {
        $client = new Client(['base_uri' => $this->setting['tracing_link']]);
        $response = $client->request('get', 'currentLoc?accessToken='.$this->setting['address']);
        $data = json_decode((string)$response->getBody(),true);
        $vehicle_list = [];
        if(isset($data['data']['list']) && is_array($data['data']['list'])){
            foreach($data['data']['list'] as $truck){
                if(isset($truck['vehicleNumber']) && $truck['vehicleNumber'] == $vehicleNo){
                        $vehicle_list = $truck;
                        break;
                }
            }
        }
        $vehicle_list['list'] = $vehicle_list;
        return $vehicle_list;
    }
    /**
     * Get Flee Tracking Details
     * @return object
     */
    function getfleetrackingdetails($vehicleNo){
        $headers = [
            'Authorization' => "Bearer ".$this->setting['access_token'],
            'Content-Type' => 'application/json',
        ];
        $client = new Client(['base_uri' => $this->setting['flee_link']]);
        try {
            $response = $client->request('GET', 'analytics/live?',
            [
                    'headers' => $headers,
            ]);
            $data = json_decode((string)$response->getBody(),true);
        } catch (ClientException $e) {
            $errorBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : null;
            Log::error('FleetX API error in getfleetrackingdetails', [
                'vehicleNo' => strtoupper($vehicleNo),
                'status' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null,
                'response' => $errorBody,
            ]);
            return ['list' => []];
        }

        $vehicle_list = [];
        if(isset($data['vehicles']) && is_array($data['vehicles'])){
            foreach($data['vehicles'] as $truck){
               if(isset($truck['vehicleNumber']) && $truck['vehicleNumber'] == strtoupper($vehicleNo)){
                $vehicle_list = $truck;
                break;
                }
            }
        }
        $vehicle_list['list'] = $vehicle_list;
        return $vehicle_list;
    }
    /**
     * Save to Bocsh
     * @return object
     */
    function savetobocsh($lrdetails,$locationdetails){
        $user = User::where('id', 1)->first();
        $getvehilcle = Vehicle::where('vehicleNo',$lrdetails->vehicleNo)->first();
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer ".$user['bearer_token']
        ];
        if($getvehilcle['vehicleStatus'] == 0){
            $latitude = $locationdetails['latitude'];
            $longitude = $locationdetails['longitude'];
            $location = $locationdetails['location'];
         }else{
            $latitude = $locationdetails['list']['latitude'];
            $longitude = $locationdetails['list']['longitude'];
            $location = $locationdetails['list']['address'];
         }
        $send_data = [
            'lrTrackingDetails' =>[
                'lspId' => $lrdetails->lspId,
                'lrNumber' => $lrdetails->lrNumber,
                'lrStatus' => $lrdetails->lrStatus,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'location' => $location,
                'pickUpDate' => $lrdetails->pickUpDate,
                'lrDate' => $lrdetails->lrDate,
                'actualDeliveredDate' => "",
                'edd' => $lrdetails->edd,
                'receiverName' => $lrdetails->receiverName,
                'deliveredToPerson' => $lrdetails->deliveredToPerson,
                'actualWeight' => $lrdetails->actualWeight,
                'numberOfPackages' => $lrdetails->numberOfPackages,
                'length' => $lrdetails->length,
                'breadth' => $lrdetails->breadth,
                'height' => $lrdetails->height,
                'truckType' => $lrdetails->truckType,
                'truckTonnage' => $lrdetails->truckTonnage,
                'vehicleNo' => $lrdetails->vehicleNo,
                'deliveryNotes' => $lrdetails->deliveryNotes,
            ]
        ];

        $client = new Client(['base_uri' => $this->setting['bocsh_link']]);
        $response = $client->request('POST', '/api/lr/tracking',
        [
                'headers' => $headers,
                'json' => $send_data,
        ]);
            $responsefrombocsh = json_decode((string)$response->getBody(),true);
            Log::info($responsefrombocsh);
            if($responsefrombocsh['success'] == 'true') {
                if($getvehilcle['vehicleStatus'] == 0){
                   $vehicle = Tracking::where('vehicleNo', '=', $lrdetails->vehicleNo)->update(array('latitude' => $locationdetails['latitude'],
                    'longitude' => $locationdetails['longitude'],
                    'location' => $locationdetails['location']));
                       return 'true';
                }else{
                    $vehicle = Tracking::where('vehicleNo', '=', $lrdetails->vehicleNo)->update(array('latitude' => $locationdetails['list']['latitude'],
                    'longitude' => $locationdetails['list']['longitude'],
                    'location' => $locationdetails['list']['address']));
                }

            }else{
                    return 'false';
            }
    }
    /**
     * Update to Bocsh
     * @return object
     */
    function updatetobocsh($lrdetails){
        $getvehilcle = Vehicle::where('vehicleNo',$lrdetails->vehicleNo)->first();
        $actualDeliveredDate = $lrdetails->actualDeliveredDate;
        if($lrdetails->actualDeliveredDate == ''){
            $actualDeliveredDate = NULL;
        }
            if($getvehilcle['vehicleStatus'] == 0){
                $access_token = self::getauthenticate();
                $access_location = self::gettrackingdetails($lrdetails->vehicleNo);
                Log::info($access_location);
                if(isset($access_location['list']) && !empty($access_location['list'])) {
                    $vehicle = Tracking::where('vehicleNo', '=', $lrdetails->vehicleNo)->where('status', '0')->update(array('latitude' => $access_location['list']['latitude'],
                    'longitude' => $access_location['list']['longitude'],
                    'location' => $access_location['list']['location']));
                    return 'true';

                }
            }else{
                $access_location = self::getfleetrackingdetails($lrdetails->vehicleNo);
                Log::info($access_location);
                if(isset($access_location['list']) && !empty($access_location['list'])) {
                    $vehicle = Tracking::where('vehicleNo', '=', $lrdetails->vehicleNo)->where('status', '0')->update(array('latitude' => $access_location['list']['latitude'],
                    'longitude' => $access_location['list']['longitude'],
                    'location' => $access_location['list']['address']));
                    return 'true';
                }
            }
        $user = User::where('id', 1)->first();
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer ".$user['bearer_token']
        ];

        $send_data = [
            'lrTrackingDetails' =>[
                'lspId' => $lrdetails->lspId,
                'lrNumber' => $lrdetails->lrNumber,
                'lrStatus' => $lrdetails->lrStatus,
                'latitude' => $lrdetails->latitude,
                'longitude' => $lrdetails->longitude,
                'location' => $lrdetails->location,
                'pickUpDate' => $lrdetails->pickUpDate,
                'lrDate' => $lrdetails->lrDate,
                'actualDeliveredDate' => $lrdetails->actualDeliveredDate,
                'edd' => $lrdetails->edd,
                'receiverName' => $lrdetails->receiverName,
                'deliveredToPerson' => $lrdetails->deliveredToPerson,
                'actualWeight' => $lrdetails->actualWeight,
                'numberOfPackages' => $lrdetails->numberOfPackages,
                'length' => $lrdetails->length,
                'breadth' => $lrdetails->breadth,
                'height' => $lrdetails->height,
                'truckType' => $lrdetails->truckType,
                'truckTonnage' => $lrdetails->truckTonnage,
                'vehicleNo' => $lrdetails->vehicleNo,
                'deliveryNotes' => $lrdetails->deliveryNotes,
            ]
        ];

        $client = new Client(['base_uri' => $this->setting['bocsh_link']]);
        $response = $client->request('POST', '/api/lr/tracking',
        [
                'headers' => $headers,
                'json' => $send_data,
        ]);
            $responsefrombocsh = json_decode((string)$response->getBody(),true);
            Log::info($responsefrombocsh);
            return $responsefrombocsh;
    }
      /**
     * Save to Bocsh
     * @return object
     */
    function updatetoweight($lrdetails,$method){
        $user = User::where('id', 1)->first();
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer ".$user['bearer_token']
        ];
        $send_data = [
            'lrNumber' => $lrdetails->lrNumber,
            'lspId' => $lrdetails->lspId,
            'correctedWeight' => $lrdetails->actualWeight,
            'numberOfPackages' => $lrdetails->numberOfPackages,
            'length' => $lrdetails->length,
            'breadth' => $lrdetails->breadth,
            'height' => $lrdetails->height,
        ];
        $client = new Client(['base_uri' => $this->setting['bocsh_link']]);
        if($method == 'recorrection'){
            $response = $client->request('POST', '/api/ilsp/weight-recorrection',
            [
                    'headers' => $headers,
                    'json' => $send_data,
            ]);
        }else{
            $response = $client->request('POST', '/api/ilsp/weight-correction',
            [
                    'headers' => $headers,
                    'json' => $send_data,
            ]);
        }
        $responsefrombocsh = json_decode((string)$response->getBody(),true);
        Log::info($responsefrombocsh);
    }
}
