<?php

namespace App\Jobs;

use App\Models\Tracking;
use App\Services\ActivityLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PushActiveTrackingsToTravisJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;
    public $timeout = 120;

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

    public function handle(ActivityLogService $logs): void
    {
        $trackingIds = Tracking::query()
            ->where('status', 0)
            ->pluck('id');

        foreach ($trackingIds as $trackingId) {
            PushTrackingToTravisJob::dispatch((int) $trackingId, $this->userId, $this->reason);
        }

        $logs->logSystem('info', 'Travis Push Jobs Queued', 'Queued active LR Travis push jobs.', [
            'queued_tracking_count' => $trackingIds->count(),
            'reason' => $this->reason,
        ], $this->userId);
    }

    public function failed(Throwable $exception): void
    {
        app(ActivityLogService::class)->logThrowable($exception, 'Travis Push Queue Job Failed', [
            'reason' => $this->reason,
        ], null, $this->userId, null, 'danger');
    }
}
