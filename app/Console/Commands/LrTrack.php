<?php

namespace App\Console\Commands;

use App\Jobs\PushActiveTrackingsToTravisJob;
use App\Jobs\SyncFleetLiveJob;
use App\Jobs\SyncWheelsEyeJob;
use Illuminate\Console\Command;
use App\Services\ActivityLogService;
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
        $activityLogs = app(ActivityLogService::class);

        Log::info('------------------------------------------------------');
        Log::info('Starting Lr Track....');
        $activityLogs->logSystem('info', 'Lr Track Start', 'Scheduled LR tracking sync started.');

        try {
            SyncWheelsEyeJob::dispatch(null, 'scheduled-lr-track');
            SyncFleetLiveJob::dispatch(null, 'scheduled-lr-track');
            PushActiveTrackingsToTravisJob::dispatch(null, 'scheduled-lr-track')->delay(now()->addMinute());

            Log::info('Lr Track jobs were queued');
            Log::info('------------------------------------------------------');
            $activityLogs->logSystem('success', 'Lr Track Queued', 'Scheduled LR tracking queue chain was dispatched.');
        } catch (\Throwable $exception) {
            $activityLogs->logSystem('danger', 'Lr Track Failed', $exception->getMessage(), [
                'exception' => get_class($exception),
            ]);
            throw $exception;
        }

    }
}
