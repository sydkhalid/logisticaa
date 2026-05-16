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

class RegisterMarketVehicleTrackingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected $vehicleId;
    protected $payload;
    protected $userId;
    protected $reason;

    public function __construct(int $vehicleId, array $payload, ?int $userId = null, string $reason = 'manual')
    {
        $this->vehicleId = $vehicleId;
        $this->payload = $payload;
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
        $vehicle = Vehicle::query()->find($this->vehicleId);
        if (!$vehicle) {
            return;
        }

        $user = $this->userId ? User::query()->find($this->userId) : null;
        $integrations->registerSimTracking($this->payload, $user);

        $logs->logSystem('success', 'FleetX SIM Registration Complete', 'Queued FleetX SIM registration completed.', [
            'vehicle_id' => $vehicle->id,
            'vehicleNo' => $vehicle->vehicleNo,
            'mobileNo' => $vehicle->mobileNo,
            'simProvider' => $vehicle->simProvider,
            'reason' => $this->reason,
        ], $this->userId);
    }

    public function failed(Throwable $exception): void
    {
        app(ActivityLogService::class)->logThrowable($exception, 'FleetX SIM Registration Job Failed', [
            'vehicle_id' => $this->vehicleId,
            'vehicleNo' => $this->payload['vehicleNumber'] ?? null,
            'mobileNo' => $this->payload['mobileNumber'] ?? null,
            'simProvider' => $this->payload['simProvider'] ?? null,
            'reason' => $this->reason,
        ], null, $this->userId, null, 'danger');
    }
}
