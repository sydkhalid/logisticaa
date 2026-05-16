<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Weight;
use App\Services\ActivityLogService;
use App\Services\V2\ExternalLogisticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncWeightCorrectionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected $weightId;
    protected $recorrection;
    protected $userId;
    protected $reason;

    public function __construct(int $weightId, bool $recorrection = false, ?int $userId = null, string $reason = 'manual')
    {
        $this->weightId = $weightId;
        $this->recorrection = $recorrection;
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
        $weight = Weight::query()->find($this->weightId);
        if (!$weight) {
            return;
        }

        $user = $this->userId ? User::query()->find($this->userId) : null;
        $integrations->syncWeightCorrection($weight, $this->recorrection, $user);

        $logs->logSystem('success', 'Weight Correction Sync Complete', 'Queued Travis weight correction sync completed.', [
            'weight_id' => $weight->id,
            'lrNumber' => $weight->lrNumber,
            'lspId' => $weight->lspId,
            'recorrection' => $this->recorrection,
            'reason' => $this->reason,
        ], $this->userId);
    }

    public function failed(Throwable $exception): void
    {
        app(ActivityLogService::class)->logThrowable($exception, 'Weight Correction Sync Job Failed', [
            'weight_id' => $this->weightId,
            'recorrection' => $this->recorrection,
            'reason' => $this->reason,
        ], null, $this->userId, null, 'danger');
    }
}
