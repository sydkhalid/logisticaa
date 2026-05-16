<?php

namespace Tests\Feature;

use App\Jobs\RefreshLrTrackingJob;
use App\Jobs\RegisterMarketVehicleTrackingJob;
use App\Jobs\StopMarketVehicleTrackingJob;
use App\Jobs\SyncWeightCorrectionJob;
use App\Models\ActivityLog;
use App\Models\Epod;
use App\Models\Setting;
use App\Models\Tracking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Weight;
use App\Services\V2\ExternalLogisticsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class V2ApplicationFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_uses_travis_and_refreshes_fleetx_token()
    {
        $password = 'secret-pass';
        $user = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'login-flow-' . uniqid() . '@example.com',
            'password' => Hash::make($password),
        ]);
        $setting = $this->setting();
        $integrations = $this->mockIntegrations($setting);
        $integrations->shouldReceive('loginToBocsh')
            ->once()
            ->with($user->email, $password)
            ->andReturn(['success' => 'true', 'token' => 'travis-login-token']);
        $integrations->shouldReceive('loginSucceeded')
            ->once()
            ->with(['success' => 'true', 'token' => 'travis-login-token'])
            ->andReturn(true);
        $integrations->shouldReceive('refreshFleetToken')
            ->once()
            ->andReturn('fleet-token');

        $this->post(route('v2.login.submit'), [
            'email' => $user->email,
            'password' => $password,
        ])->assertRedirect(route('v2.home'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('travis-login-token', $user->fresh()->bearer_token);
    }

    public function test_settings_save_updates_links_and_tokens()
    {
        $user = $this->adminUser();
        $setting = $this->setting();
        $this->mockIntegrations($setting);

        $this->actingAs($user)
            ->post(route('v2.settings.update'), [
                'id' => $setting->id,
                'name' => 'Logisticaa Test',
                'copyright' => '2026',
                'bocsh_link' => 'https://trackingapi.bosch-travis.com/',
                'tracing_link' => 'https://api.wheelseye.com/',
                'flee_link' => 'https://api.fleetx.io/api/v1/',
                'address' => 'wheelseye-token',
                'access_token' => 'fleetx-token',
            ])
            ->assertRedirect(route('v2.settings.edit'));

        $fresh = $setting->fresh();
        $this->assertSame('Logisticaa Test', $fresh->name);
        $this->assertSame('https://api.fleetx.io/api/v1/', $fresh->flee_link);
        $this->assertSame('wheelseye-token', $fresh->address);
        $this->assertSame('fleetx-token', $fresh->access_token);
    }

    public function test_fleetx_refresh_route_calls_integration_service()
    {
        $user = $this->adminUser();
        $integrations = $this->mockIntegrations($this->setting());
        $integrations->shouldReceive('refreshFleetToken')
            ->once()
            ->with(Mockery::on(function ($argument) use ($user) {
                return $argument instanceof User && (int) $argument->id === (int) $user->id;
            }))
            ->andReturn('fresh-fleetx-token');

        $this->actingAs($user)
            ->post(route('v2.integrations.fleetx.refresh-token'))
            ->assertRedirect(route('v2.integrations.index'))
            ->assertSessionHas('message_type', 'success');
    }

    public function test_epod_upload_creates_uploaded_record_and_closes_tracking()
    {
        $user = $this->user();
        $tracking = $this->tracking([
            'lspId' => 'LSP-EPOD',
            'lrNumber' => 'LR-EPOD',
            'status' => 1,
        ]);
        $integrations = $this->mockIntegrations($this->setting());
        $integrations->shouldReceive('uploadEpod')
            ->once()
            ->with(Mockery::type(Epod::class), Mockery::on(function ($base64) {
                return is_string($base64) && strpos($base64, 'data:image/jpeg;base64,') === 0;
            }), Mockery::type(User::class))
            ->andReturn(['success' => true]);

        $this->actingAs($user)
            ->post(route('v2.epods.store'), [
                'lspId' => $tracking->lspId,
                'lrNumber' => $tracking->lrNumber,
                'epod' => UploadedFile::fake()->image('proof.jpg')->size(32),
            ])
            ->assertRedirect(route('v2.epods.index'))
            ->assertSessionHas('message_type', 'success');

        $epod = Epod::query()
            ->where('lspId', 'LSP-EPOD')
            ->where('lrNumber', 'LR-EPOD')
            ->first();

        $this->assertNotNull($epod);
        $this->assertSame(1, (int) $epod->status);
        $this->assertSame((int) $tracking->id, (int) $epod->tracking_id);
        $this->assertSame(3, (int) $tracking->fresh()->status);

        if ($epod && $epod->epod) {
            @unlink(storage_path('app/epods/' . $epod->epod));
        }
    }

    public function test_failed_epod_upload_can_be_retried()
    {
        $user = $this->user();
        $tracking = $this->tracking([
            'lspId' => 'LSP-RETRY',
            'lrNumber' => 'LR-RETRY',
            'status' => 1,
        ]);
        $integrations = $this->mockIntegrations($this->setting());
        $integrations->shouldReceive('uploadEpod')
            ->once()
            ->andThrow(new \RuntimeException('Travis upload down'));

        $this->actingAs($user)
            ->post(route('v2.epods.store'), [
                'lspId' => $tracking->lspId,
                'lrNumber' => $tracking->lrNumber,
                'epod' => UploadedFile::fake()->image('retry-proof.jpg')->size(32),
            ])
            ->assertRedirect()
            ->assertSessionHas('message_type', 'danger');

        $epod = Epod::query()
            ->where('lspId', 'LSP-RETRY')
            ->where('lrNumber', 'LR-RETRY')
            ->first();

        $this->assertNotNull($epod);
        $this->assertSame(0, (int) $epod->status);
        $this->assertSame((int) $tracking->id, (int) $epod->tracking_id);
        $this->assertFileExists(storage_path('app/epods/' . $epod->epod));

        $integrations = $this->mockIntegrations($this->setting());
        $integrations->shouldReceive('uploadEpod')
            ->once()
            ->andReturn(['success' => true]);

        $this->actingAs($user)
            ->post(route('v2.epods.retry', $epod))
            ->assertRedirect(route('v2.epods.index'))
            ->assertSessionHas('message_type', 'success');

        $this->assertSame(1, (int) $epod->fresh()->status);
        $this->assertSame(3, (int) $tracking->fresh()->status);

        $this->deleteEpodFile($epod->fresh());
    }

    public function test_epod_file_can_be_downloaded()
    {
        $user = $this->user();
        $this->mockIntegrations($this->setting());
        $epod = $this->epod([
            'lspId' => 'LSP-DOWNLOAD',
            'lrNumber' => 'LR-DOWNLOAD',
            'status' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('v2.epods.download', $epod))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->deleteEpodFile($epod);
        $epod->delete();
    }

    public function test_admin_can_delete_epod_and_local_file()
    {
        $admin = $this->adminUser();
        $this->mockIntegrations($this->setting());
        $tracking = $this->tracking([
            'lspId' => 'LSP-DELETE',
            'lrNumber' => 'LR-DELETE',
            'status' => 3,
        ]);
        $epod = $this->epod([
            'lspId' => $tracking->lspId,
            'lrNumber' => $tracking->lrNumber,
            'status' => 1,
        ]);
        $path = storage_path('app/epods/' . $epod->epod);

        $this->actingAs($admin)
            ->delete(route('v2.epods.destroy', $epod))
            ->assertRedirect(route('v2.epods.index'))
            ->assertSessionHas('message_type', 'success');

        $this->assertDatabaseMissing('epods', ['id' => $epod->id]);
        $this->assertFileDoesNotExist($path);
        $this->assertSame(1, (int) $tracking->fresh()->status);
    }

    public function test_lr_tracking_refresh_queues_background_job()
    {
        Queue::fake();
        $user = $this->user();
        $tracking = $this->tracking();
        $this->mockIntegrations($this->setting());

        $this->actingAs($user)
            ->from(route('v2.lr-trackings.index'))
            ->post(route('v2.lr-trackings.refresh', $tracking))
            ->assertRedirect(route('v2.lr-trackings.index'))
            ->assertSessionHas('message_type', 'success');

        Queue::assertPushed(RefreshLrTrackingJob::class);
    }

    public function test_lr_tracking_update_to_delivered_queues_background_refresh()
    {
        Queue::fake();
        $user = $this->user();
        $tracking = $this->tracking([
            'lrStatus' => 'Shipment In Transit',
            'status' => 0,
        ]);
        $this->mockIntegrations($this->setting());

        $this->actingAs($user)
            ->put(route('v2.lr-trackings.update', $tracking), [
                'lrStatus' => 'Shipment Delivered',
                'actualDeliveredDate' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('v2.lr-trackings.completed'))
            ->assertSessionHas('message_type', 'success');

        $tracking->refresh();

        $this->assertSame('Shipment Delivered', $tracking->lrStatus);
        $this->assertSame(1, (int) $tracking->status);
        $this->assertNotNull($tracking->actualDeliveredDate);
        Queue::assertPushed(RefreshLrTrackingJob::class);
    }

    public function test_lr_tracking_create_populates_vehicle_relationship()
    {
        Queue::fake();
        $user = $this->user();
        $this->mockIntegrations($this->setting());
        $vehicle = new Vehicle();
        $vehicle->vehicleNo = 'KA01REL1234';
        $vehicle->vehicleStatus = 0;
        $vehicle->statusStop = 0;
        $vehicle->save();

        $this->actingAs($user)
            ->post(route('v2.lr-trackings.store'), [
                'vehicle_id' => $vehicle->id,
                'lspId' => 'LSP-REL',
                'lrNumber' => 'LR-REL',
                'lrStatus' => 'Shipment In Transit',
                'edd' => now()->addDay()->format('Y-m-d H:i:s'),
                'actualWeight' => '10',
                'numberOfPackages' => '1',
                'length' => '1',
                'breadth' => '1',
                'height' => '1',
                'truckType' => 'LTL',
                'truckTonnage' => '1 T',
            ])
            ->assertRedirect(route('v2.lr-trackings.index'))
            ->assertSessionHas('message_type', 'success');

        $tracking = Tracking::query()
            ->where('lspId', 'LSP-REL')
            ->where('lrNumber', 'LR-REL')
            ->first();

        $this->assertNotNull($tracking);
        $this->assertSame((int) $vehicle->id, (int) $tracking->vehicle_id);
        $this->assertTrue($tracking->vehicle->is($vehicle));
        Queue::assertPushed(RefreshLrTrackingJob::class);
    }

    public function test_market_vehicle_create_queues_fleetx_registration()
    {
        Queue::fake();
        $user = $this->user();
        $this->mockIntegrations($this->setting());

        $this->actingAs($user)
            ->post(route('v2.market-vehicles.store'), [
                'vehicleNo' => 'ka01ab7788',
                'mobileNo' => '9999999999',
                'simProvider' => 'airtel',
                'expireDate' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('v2.market-vehicles.index'))
            ->assertSessionHas('message_type', 'success');

        $this->assertDatabaseHas('vehicles', [
            'vehicleNo' => 'KA01AB7788',
            'mobileNo' => '9999999999',
            'simProvider' => 'AIRTEL',
            'vehicleStatus' => 1,
            'statusStop' => 0,
        ]);
        Queue::assertPushed(RegisterMarketVehicleTrackingJob::class);
    }

    public function test_market_vehicle_stop_queues_fleetx_stop()
    {
        Queue::fake();
        $user = $this->adminUser();
        $this->mockIntegrations($this->setting());
        $vehicle = new Vehicle();
        $vehicle->vehicleNo = 'KA01AB8899';
        $vehicle->mobileNo = '9999999998';
        $vehicle->simProvider = 'AIRTEL';
        $vehicle->expireDate = now()->addDay()->format('Y-m-d H:i:s');
        $vehicle->vehicleStatus = 1;
        $vehicle->statusStop = 0;
        $vehicle->save();

        $this->actingAs($user)
            ->post(route('v2.market-vehicles.stop-tracking', $vehicle))
            ->assertRedirect()
            ->assertSessionHas('message_type', 'success');

        $this->assertSame(1, (int) $vehicle->fresh()->statusStop);
        Queue::assertPushed(StopMarketVehicleTrackingJob::class);
    }

    public function test_market_vehicle_update_queues_new_registration_and_old_sim_stop()
    {
        Queue::fake();
        $user = $this->user();
        $this->mockIntegrations($this->setting());
        $vehicle = $this->marketVehicle([
            'vehicleNo' => 'KA01AB9900',
            'mobileNo' => '9999999900',
            'simProvider' => 'AIRTEL',
        ]);

        $this->actingAs($user)
            ->put(route('v2.market-vehicles.update', $vehicle), [
                'vehicleNo' => 'ka01ab9901',
                'mobileNo' => '9999999901',
                'simProvider' => 'jio',
                'expireDate' => now()->addDays(2)->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('v2.market-vehicles.index'))
            ->assertSessionHas('message_type', 'success');

        $vehicle->refresh();

        $this->assertSame('KA01AB9901', $vehicle->vehicleNo);
        $this->assertSame('9999999901', $vehicle->mobileNo);
        $this->assertSame('JIO', $vehicle->simProvider);
        $this->assertSame(0, (int) $vehicle->statusStop);
        Queue::assertPushed(RegisterMarketVehicleTrackingJob::class);
        Queue::assertPushed(StopMarketVehicleTrackingJob::class);
    }

    public function test_market_vehicle_delete_queues_remote_stop_after_local_delete()
    {
        Queue::fake();
        $admin = $this->adminUser();
        $this->mockIntegrations($this->setting());
        $vehicle = $this->marketVehicle([
            'vehicleNo' => 'KA01AB9910',
            'mobileNo' => '9999999910',
            'simProvider' => 'AIRTEL',
        ]);

        $this->actingAs($admin)
            ->delete(route('v2.market-vehicles.destroy', $vehicle))
            ->assertRedirect(route('v2.market-vehicles.index'))
            ->assertSessionHas('message_type', 'success');

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        Queue::assertPushed(StopMarketVehicleTrackingJob::class);
    }

    public function test_weight_correction_create_queues_travis_sync()
    {
        Queue::fake();
        $user = $this->user();
        $this->mockIntegrations($this->setting());

        $this->actingAs($user)
            ->post(route('v2.weight-corrections.store'), [
                'lspId' => 'LSP-WEIGHT',
                'lrNumber' => 'LR-WEIGHT',
                'actualWeight' => '15.5',
                'length' => '10',
                'breadth' => '5',
                'height' => '4',
            ])
            ->assertRedirect(route('v2.weight-corrections.index'))
            ->assertSessionHas('message_type', 'success');

        $this->assertDatabaseHas('weights', [
            'lspId' => 'LSP-WEIGHT',
            'lrNumber' => 'LR-WEIGHT',
            'correctedWeight' => '15.5',
        ]);
        Queue::assertPushed(SyncWeightCorrectionJob::class);
    }

    public function test_weight_correction_links_to_tracking_when_available()
    {
        Queue::fake();
        $user = $this->user();
        $this->mockIntegrations($this->setting());
        $tracking = $this->tracking([
            'lspId' => 'LSP-WEIGHT-LINK',
            'lrNumber' => 'LR-WEIGHT-LINK',
        ]);

        $this->actingAs($user)
            ->post(route('v2.weight-corrections.store'), [
                'lspId' => $tracking->lspId,
                'lrNumber' => $tracking->lrNumber,
                'actualWeight' => '20',
                'length' => '10',
                'breadth' => '5',
                'height' => '4',
            ])
            ->assertRedirect(route('v2.weight-corrections.index'))
            ->assertSessionHas('message_type', 'success');

        $weight = \App\Models\Weight::query()
            ->where('lspId', $tracking->lspId)
            ->where('lrNumber', $tracking->lrNumber)
            ->first();

        $this->assertNotNull($weight);
        $this->assertSame((int) $tracking->id, (int) $weight->tracking_id);
        $this->assertTrue($weight->tracking->is($tracking));
        Queue::assertPushed(SyncWeightCorrectionJob::class);
    }

    public function test_weight_recorrection_queues_travis_sync()
    {
        Queue::fake();
        $user = $this->user();
        $tracking = $this->tracking([
            'lspId' => 'LSP-WEIGHT-UPDATE',
            'lrNumber' => 'LR-WEIGHT-UPDATE',
        ]);
        $weight = $this->weight([
            'lspId' => $tracking->lspId,
            'lrNumber' => $tracking->lrNumber,
        ]);
        $this->mockIntegrations($this->setting());

        $this->actingAs($user)
            ->put(route('v2.weight-corrections.update', $weight), [
                'actualWeight' => '30',
                'length' => '12',
                'breadth' => '6',
                'height' => '5',
            ])
            ->assertRedirect(route('v2.weight-corrections.index'))
            ->assertSessionHas('message_type', 'success');

        $weight->refresh();

        $this->assertSame('30', (string) $weight->correctedWeight);
        $this->assertSame((int) $tracking->id, (int) $weight->tracking_id);
        Queue::assertPushed(SyncWeightCorrectionJob::class);
    }

    public function test_vehicle_crud_flow()
    {
        $user = $this->adminUser();
        $integrations = $this->mockIntegrations($this->setting());

        $this->actingAs($user)
            ->post(route('v2.vehicles.store'), ['vehicleNo' => 'ka01ab1234'])
            ->assertRedirect(route('v2.vehicles.index'));

        $vehicle = Vehicle::query()->where('vehicleNo', 'KA01AB1234')->first();
        $this->assertNotNull($vehicle);

        $integrations->shouldReceive('locateVehicle')
            ->once()
            ->andReturn([
                'source' => 'wheelseye',
                'latitude' => '12.9716',
                'longitude' => '77.5946',
                'location' => 'Bengaluru',
            ]);

        $this->actingAs($user)
            ->get(route('v2.vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('KA01AB1234');

        $this->actingAs($user)
            ->put(route('v2.vehicles.update', $vehicle), ['vehicleNo' => 'ka01ab5678'])
            ->assertRedirect(route('v2.vehicles.index'));

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'vehicleNo' => 'KA01AB5678',
        ]);

        $this->actingAs($user)
            ->delete(route('v2.vehicles.destroy', $vehicle->fresh()))
            ->assertRedirect(route('v2.vehicles.index'));

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    public function test_v2_route_security_requires_authentication()
    {
        $protectedRoutes = [
            ['GET', route('v2.home')],
            ['GET', route('v2.settings.edit')],
            ['POST', route('v2.settings.update')],
            ['POST', route('v2.integrations.fleetx.refresh-token')],
            ['GET', route('v2.epods.create')],
            ['POST', route('v2.epods.store')],
            ['GET', route('v2.vehicles.index')],
            ['POST', route('v2.vehicles.store')],
            ['POST', route('v2.lr-trackings.refresh', 1)],
            ['GET', route('v2.reports.index')],
            ['GET', route('v2.reports.print')],
            ['GET', route('v2.reports.export', ['dataset' => 'trackings'])],
        ];

        foreach ($protectedRoutes as [$method, $url]) {
            $this->call($method, $url)->assertRedirect(route('v2.login'));
        }
    }

    public function test_default_lsp_id_is_loaded_from_config()
    {
        $user = $this->user();
        $this->mockIntegrations($this->setting());
        Config::set('integrations.travis.default_lsp_id', 'LSP-CONFIG-100');

        $this->actingAs($user)
            ->get(route('v2.lr-trackings.create'))
            ->assertOk()
            ->assertSee('value="LSP-CONFIG-100"', false);

        $this->actingAs($user)
            ->get(route('v2.weight-corrections.create'))
            ->assertOk()
            ->assertSee('value="LSP-CONFIG-100"', false);

        $this->actingAs($user)
            ->get(route('v2.epods.create'))
            ->assertOk()
            ->assertSee('value="LSP-CONFIG-100"', false);
    }

    public function test_non_admin_cannot_access_admin_routes_or_destructive_actions()
    {
        $user = $this->user();
        $vehicle = new Vehicle();
        $vehicle->vehicleNo = 'KA01ZZ9999';
        $vehicle->vehicleStatus = 0;
        $vehicle->statusStop = 0;
        $vehicle->save();

        $marketVehicle = new Vehicle();
        $marketVehicle->vehicleNo = 'KA01ZZ8888';
        $marketVehicle->mobileNo = '9999999999';
        $marketVehicle->simProvider = 'AIRTEL';
        $marketVehicle->expireDate = now()->addDay()->format('Y-m-d H:i:s');
        $marketVehicle->vehicleStatus = 1;
        $marketVehicle->statusStop = 0;
        $marketVehicle->save();
        $epod = $this->epod([
            'lspId' => 'LSP-FORBID',
            'lrNumber' => 'LR-FORBID',
            'status' => 0,
        ]);

        $restrictedRoutes = [
            ['GET', route('v2.settings.edit')],
            ['POST', route('v2.settings.update')],
            ['GET', route('v2.integrations.index')],
            ['POST', route('v2.integrations.fleetx.refresh-token')],
            ['POST', route('v2.integrations.travis.refresh-token')],
            ['GET', route('v2.logs.index')],
            ['GET', route('v2.logs.export')],
            ['POST', route('v2.logs.clear-old')],
            ['POST', route('v2.logs.clear')],
            ['DELETE', route('v2.vehicles.destroy', $vehicle)],
            ['POST', route('v2.market-vehicles.stop-tracking', $marketVehicle)],
            ['DELETE', route('v2.market-vehicles.destroy', $marketVehicle)],
            ['DELETE', route('v2.epods.destroy', $epod)],
        ];

        foreach ($restrictedRoutes as [$method, $url]) {
            $this->actingAs($user)->call($method, $url)->assertForbidden();
        }

        $this->deleteEpodFile($epod);
        $epod->delete();
    }

    public function test_admin_can_clear_old_logs_without_deleting_recent_logs()
    {
        $admin = $this->adminUser();
        $this->mockIntegrations($this->setting());
        $oldLog = $this->logEntry([
            'title' => 'Old log',
            'created_at' => date('Y-m-d H:i:s', strtotime('-45 days')),
        ]);
        $recentLog = $this->logEntry([
            'title' => 'Recent log',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
        ]);

        $this->actingAs($admin)
            ->post(route('v2.logs.clear-old'))
            ->assertRedirect(route('v2.logs.index'))
            ->assertSessionHas('message_type', 'success');

        $this->assertDatabaseMissing('logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('logs', ['id' => $recentLog->id]);
    }

    public function test_reports_export_trackings_returns_csv()
    {
        $user = $this->user();
        $tracking = $this->tracking([
            'lspId' => 'LSP-REPORT',
            'lrNumber' => 'LR-REPORT',
        ]);

        $response = $this->actingAs($user)
            ->get(route('v2.reports.export', [
                'dataset' => 'trackings',
                'from' => date('Y-m-d', strtotime('-1 day')),
                'to' => date('Y-m-d', strtotime('+1 day')),
            ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('v2-trackings-', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString($tracking->lrNumber, $response->streamedContent());
    }

    public function test_reports_export_vehicles_returns_excel_compatible_file()
    {
        $user = $this->user();
        $vehicle = $this->marketVehicle([
            'vehicleNo' => 'KA01XLS1000',
            'mobileNo' => '9999991000',
        ]);

        $response = $this->actingAs($user)
            ->get(route('v2.reports.export', [
                'dataset' => 'vehicles',
                'from' => date('Y-m-d', strtotime('-1 day')),
                'to' => date('Y-m-d', strtotime('+1 day')),
                'format' => 'xls',
            ]));

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.ms-excel', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('v2-vehicles-', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString($vehicle->vehicleNo, $response->streamedContent());
    }

    public function test_reports_print_view_renders_pdf_ready_summary()
    {
        $user = $this->user();
        $tracking = $this->tracking([
            'lspId' => 'LSP-PRINT',
            'lrNumber' => 'LR-PRINT',
        ]);
        $integrations = $this->mockIntegrations($this->setting());
        $integrations->shouldReceive('cachedFleetAnalytics')
            ->once()
            ->andReturn([
                'runningVehicles' => 2,
                'disconnectedVehicles' => 1,
            ]);

        $this->actingAs($user)
            ->get(route('v2.reports.print', [
                'from' => date('Y-m-d', strtotime('-1 day')),
                'to' => date('Y-m-d', strtotime('+1 day')),
            ]))
            ->assertOk()
            ->assertSee('Operational Report')
            ->assertSee('Print / Save PDF')
            ->assertSee($tracking->lrNumber);
    }

    private function mockIntegrations(?Setting $setting = null)
    {
        $mock = Mockery::mock(ExternalLogisticsService::class);
        $mock->shouldReceive('getSettings')->andReturn($setting)->byDefault();
        $mock->shouldReceive('cachedFleetAnalytics')->andReturn([])->byDefault();
        $this->app->instance(ExternalLogisticsService::class, $mock);

        return $mock;
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'v2-test-' . uniqid() . '@example.com',
            'is_admin' => false,
        ], $attributes));
    }

    private function adminUser(array $attributes = []): User
    {
        return $this->user(array_merge([
            'is_admin' => true,
        ], $attributes));
    }

    private function setting(array $attributes = []): Setting
    {
        $setting = new Setting();
        $setting->name = $attributes['name'] ?? 'Logisticaa Test';
        $setting->bocsh_link = $attributes['bocsh_link'] ?? 'https://trackingapi.bosch-travis.com/';
        $setting->tracing_link = $attributes['tracing_link'] ?? 'https://api.wheelseye.com/';
        $setting->flee_link = $attributes['flee_link'] ?? 'https://api.fleetx.io/api/v1/';
        $setting->address = $attributes['address'] ?? 'wheelseye-token';
        $setting->access_token = $attributes['access_token'] ?? 'fleetx-token';
        $setting->save();

        return $setting;
    }

    private function tracking(array $attributes = []): Tracking
    {
        $tracking = new Tracking();
        $tracking->lspId = $attributes['lspId'] ?? 'LSP-TEST';
        $tracking->lrNumber = $attributes['lrNumber'] ?? 'LR-TEST';
        $tracking->lrStatus = $attributes['lrStatus'] ?? 'Shipment In Transit';
        $tracking->vehicleNo = $attributes['vehicleNo'] ?? 'KA01AB1234';
        $tracking->edd = $attributes['edd'] ?? date('Y-m-d H:i:s', strtotime('+1 day'));
        $tracking->actualWeight = $attributes['actualWeight'] ?? '10';
        $tracking->numberOfPackages = $attributes['numberOfPackages'] ?? '1';
        $tracking->length = $attributes['length'] ?? '1';
        $tracking->breadth = $attributes['breadth'] ?? '1';
        $tracking->height = $attributes['height'] ?? '1';
        $tracking->truckType = $attributes['truckType'] ?? 'LTL';
        $tracking->truckTonnage = $attributes['truckTonnage'] ?? '1 T';
        $tracking->status = $attributes['status'] ?? 0;
        $tracking->save();

        return $tracking;
    }

    private function marketVehicle(array $attributes = []): Vehicle
    {
        $vehicle = new Vehicle();
        $vehicle->vehicleNo = $attributes['vehicleNo'] ?? 'KA01MARKET1234';
        $vehicle->mobileNo = $attributes['mobileNo'] ?? '9999999999';
        $vehicle->simProvider = $attributes['simProvider'] ?? 'AIRTEL';
        $vehicle->expireDate = $attributes['expireDate'] ?? date('Y-m-d H:i:s', strtotime('+1 day'));
        $vehicle->vehicleStatus = $attributes['vehicleStatus'] ?? 1;
        $vehicle->statusStop = $attributes['statusStop'] ?? 0;
        $vehicle->save();

        return $vehicle;
    }

    private function weight(array $attributes = []): Weight
    {
        $weight = new Weight();
        $weight->lspId = $attributes['lspId'] ?? 'LSP-WEIGHT-TEST';
        $weight->lrNumber = $attributes['lrNumber'] ?? 'LR-WEIGHT-TEST';
        $weight->correctedWeight = $attributes['correctedWeight'] ?? '10';
        $weight->length = $attributes['length'] ?? '1';
        $weight->breadth = $attributes['breadth'] ?? '1';
        $weight->height = $attributes['height'] ?? '1';
        $weight->tracking_id = $attributes['tracking_id'] ?? null;
        $weight->save();

        return $weight;
    }

    private function epod(array $attributes = []): Epod
    {
        $directory = storage_path('app/epods');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = $attributes['epod'] ?? ('epod-test-' . uniqid() . '.jpg');
        file_put_contents($directory . DIRECTORY_SEPARATOR . $filename, 'test epod file');

        $epod = new Epod();
        $epod->lspId = $attributes['lspId'] ?? 'LSP-TEST';
        $epod->lrNumber = $attributes['lrNumber'] ?? 'LR-TEST';
        $epod->epod = $filename;
        $epod->status = $attributes['status'] ?? 0;
        $epod->save();

        return $epod;
    }

    private function logEntry(array $attributes = []): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $attributes['user_id'] ?? null,
            'type' => $attributes['type'] ?? 'info',
            'title' => $attributes['title'] ?? 'Test log',
            'description' => $attributes['description'] ?? 'Test log description',
            'uri' => $attributes['uri'] ?? '/v2/test',
            'ip' => $attributes['ip'] ?? '127.0.0.1',
            'is_api' => $attributes['is_api'] ?? 0,
            'request_info' => $attributes['request_info'] ?? '{}',
            'created_at' => $attributes['created_at'] ?? date('Y-m-d H:i:s'),
            'created_by' => $attributes['created_by'] ?? 'Test User',
        ]);
    }

    private function deleteEpodFile(Epod $epod): void
    {
        if (!$epod->epod) {
            return;
        }

        $path = storage_path('app/epods/' . basename((string) $epod->epod));
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
