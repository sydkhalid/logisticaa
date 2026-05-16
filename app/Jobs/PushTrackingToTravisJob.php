<?php

namespace App\Jobs;

use App\Models\Tracking;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\V2\ExternalLogisticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PushTrackingToTravisJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected $trackingId;
    protected $userId;
    protected $reason;

    public function __construct(int $trackingId, ?int $userId = null, string $reason = 'scheduled')
    {
        $this->trackingId = $trackingId;
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
        $tracking = Tracking::query()->find($this->trackingId);
        if (!$tracking) {
            return;
        }

        $user = $this->userId ? User::query()->find($this->userId) : null;
        $integrations->pushTrackingToTravis($tracking, $user);

        $logs->logSystem('success', 'Travis Push Complete', 'Queued Travis LR push completed.', [
            'tracking_id' => $tracking->id,
            'lrNumber' => $tracking->lrNumber,
            'vehicleNo' => $tracking->vehicleNo,
            'reason' => $this->reason,
        ], $this->userId);
    }

    public function failed(Throwable $exception): void
    {
        app(ActivityLogService::class)->logThrowable($exception, 'Travis Push Job Failed', [
            'tracking_id' => $this->trackingId,
            'reason' => $this->reason,
        ], null, $this->userId, null, 'danger');
    }
}
