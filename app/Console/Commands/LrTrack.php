<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tracking;
use App\Models\Vehicle;
use App\Http\Controllers\TrackController;
use Illuminate\Support\Facades\Log;

class LrTrack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Lr:Track';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lr Tracking';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('------------------------------------------------------');
        Log::info('Starting Lr Track....');
        $auth = new TrackController();
        $access_token = $auth->getauthenticate();
        if($access_token['success'] == 'true'){
               $wheelseye = Tracking::where('status' ,'0')->where('vehicle_status' ,'0')->get();
                 if($wheelseye){
                    foreach($wheelseye as $key=>$lrlist){
                       $list = $auth->wheelseyeapi($lrlist['vehicleNo']);
                     
                   }               
               }
            $flee = Tracking::where('status','0')->where('vehicle_status','1')->get();
              foreach($flee as $key=>$lrlistflee){
                  if($flee){
                      $list = $auth->fleeapi($lrlistflee['vehicleNo']);
                   
                  }               
              }
        }
        $sendtobocsh = Tracking::where('status' ,'0')->get();
        $save = $auth->sendbocsh($sendtobocsh);
        Log::info('Lr Track was finished');
        Log::info('------------------------------------------------------');

    }
}
