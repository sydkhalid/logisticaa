<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\V2\ExternalLogisticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncFleetLiveJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;
    public $timeout = 180;

    protected $userId;
    protected $reason;

    public function __construct(?int $userId = null, string $reason = 'scheduled')
    {
        $this->userId = $userId;
        $this->reason = $reason;
        $this->onQueue('integrations');
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(ExternalLogisticsService $integrations, ActivityLogService $logs): void
    {
        $user = $this->userId ? User::query()->find($this->userId) : null;
        $updated = $integrations->syncFleetLiveLocations($user);

        $logs->logSystem('success', 'FleetX Live Sync Complete', 'Queued FleetX live location sync completed.', [
            'updated_tracking_count' => $updated,
            'reason' => $this->reason,
        ], $this->userId);
    }

    public function failed(Throwable $exception): void
    {
        app(ActivityLogService::class)->logThrowable($exception, 'FleetX Live Sync Job Failed', [
            'reason' => $this->reason,
        ], null, $this->userId, null, 'danger');
    }
}
