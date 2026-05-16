<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Vehicle;
use App\Services\ActivityLogService;
use App\Services\V2\ExternalLogisticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class StopMarketVehicleTrackingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected $mobileNumber;
    protected $simProvider;
    protected $vehicleId;
    protected $vehicleNo;
    protected $userId;
    protected $reason;
    protected $markStopped;

    public function __construct(
        string $mobileNumber,
        string $simProvider,
        ?int $vehicleId = null,
        ?string $vehicleNo = null,
        ?int $userId = null,
        string $reason = 'manual',
        bool $markStopped = false
    ) {
        $this->mobileNumber = $mobileNumber;
        $this->simProvider = $simProvider;
        $this->vehicleId = $vehicleId;
        $this->vehicleNo = $vehicleNo;
        $this->userId = $userId;
        $this->reason = $reason;
        $this->markStopped = $markStopped;
        $this->onQueue('integrations');
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(ExternalLogisticsService $integrations, ActivityLogService $logs): void
    {
        $user = $this->userId ? User::query()->find($this->userId) : null;
        $integrations->stopSimTracking($this->mobileNumber, $this->simProvider, $user);

        if ($this->markStopped && $this->vehicleId) {
            Vehicle::query()
                ->where('id', $this->vehicleId)
                ->update(['statusStop' => 1]);
        }

        $logs->logSystem('success', 'FleetX SIM Stop Complete', 'Queued FleetX SIM stop completed.', [
            'vehicle_id' => $this->vehicleId,
            'vehicleNo' => $this->vehicleNo,
            'mobileNo' => $this->mobileNumber,
            'simProvider' => $this->simProvider,
            'reason' => $this->reason,
        ], $this->userId);
    }

    public function failed(Throwable $exception): void
    {
        app(ActivityLogService::class)->logThrowable($exception, 'FleetX SIM Stop Job Failed', [
            'vehicle_id' => $this->vehicleId,
            'vehicleNo' => $this->vehicleNo,
            'mobileNo' => $this->mobileNumber,
            'simProvider' => $this->simProvider,
            'reason' => $this->reason,
        ], null, $this->userId, null, 'danger');
    }
}
