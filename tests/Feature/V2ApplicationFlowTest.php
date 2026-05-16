<?php

namespace Tests\Feature;

use App\Jobs\RefreshLrTrackingJob;
use App\Models\Epod;
use App\Models\Setting;
use App\Models\Tracking;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\V2\ExternalLogisticsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
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
        $user = $this->user();
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
        $user = $this->user();
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
        $this->assertSame(3, (int) $tracking->fresh()->status);

        if ($epod && $epod->epod) {
            @unlink(storage_path('app/epods/' . $epod->epod));
        }
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

    public function test_vehicle_crud_flow()
    {
        $user = $this->user();
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
        ];

        foreach ($protectedRoutes as [$method, $url]) {
            $this->call($method, $url)->assertRedirect(route('v2.login'));
        }
    }

    private function mockIntegrations(?Setting $setting = null)
    {
        $mock = Mockery::mock(ExternalLogisticsService::class);
        $mock->shouldReceive('getSettings')->andReturn($setting)->byDefault();
        $this->app->instance(ExternalLogisticsService::class, $mock);

        return $mock;
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'v2-test-' . uniqid() . '@example.com',
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
}
