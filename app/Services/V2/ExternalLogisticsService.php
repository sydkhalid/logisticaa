<?php

namespace App\Services\V2;

use App\Models\Epod;
use App\Models\IntegrationMonitor;
use App\Models\Setting;
use App\Models\Tracking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Weight;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ExternalLogisticsService
{
    private const SYSTEM_EMAIL = 'connect@logisticaa.co.in';
    private const DEFAULT_TIMEOUT = 20;
    private const DEFAULT_CONNECT_TIMEOUT = 10;

    public static function lrStatuses(): array
    {
        return [
            'Shipment In Transit',
            'Hub-Delivered',
            'Out-For-Delivery',
            'Delay',
            'Customer Appointment Delivery',
            'Shipment Delivered',
        ];
    }

    public static function truckTypes(): array
    {
        return ['LTL', 'FTL'];
    }

    public static function truckTonnages(): array
    {
        return [
            '1 E',
            '1 T',
            '2 E',
            '2.5 E',
            '2.5 T',
            '3.5 T',
            '3.5 E',
            '5.5 T',
            '9 E',
            '9 T Single axle',
            '9 T Multi axle',
            '14 T',
            '16 T',
            '18 T',
            '22 T',
            '28 T',
        ];
    }

    public function getSettings(): ?Setting
    {
        return Setting::query()->first();
    }

    public function fleetAnalyticsDefaults(): array
    {
        return [
            'totalVehicles' => 0,
            'runningVehicles' => 0,
            'parkedVehicles' => 0,
            'idleVehicles' => 0,
            'inshopVehicles' => 0,
            'disconnectedVehicles' => 0,
            'unreachableVehicles' => 0,
            'immobilisedVehicles' => 0,
            'standbyVehicles' => 0,
            'batteryDischargedVehicles' => 0,
            'nopowerVehicles' => 0,
            'utilization' => 0,
            'alarms' => 0,
            'vehicles' => [],
        ];
    }

    public function loginToBocsh(string $email, string $password): array
    {
        return $this->bocshRequest('POST', '/api/auth/login', [
            'userDetails' => [
                'emailId' => $email,
                'password' => $password,
            ],
        ], null, false);
    }

    public function syncSystemBocshToken(?User $user = null): ?string
    {
        $systemUser = User::query()->first();
        $email = $this->systemBocshEmail($systemUser);

        $response = $this->loginToBocsh($email, $this->requiredEnv('TRAVIS_SYSTEM_PASSWORD', 'Travis system password'));
        $token = $response['token'] ?? null;

        if ($token && $systemUser) {
            $systemUser->bearer_token = $token;
            $systemUser->save();
        }

        if ($token && $user) {
            $user->bearer_token = $token;
            $user->save();
        }

        if ($token) {
            $this->recordTokenRefresh('travis');
        }

        return $token;
    }

    public function refreshFleetToken(?User $user = null): ?string
    {
        $settings = $this->getSettings();
        if (!$settings || !$settings->flee_link) {
            throw new RuntimeException('FleetX link is not configured.');
        }

        $response = $this->fleetRequest('POST', 'login', [
            'headers' => [
                'Authorization' => $this->requiredEnv('FLEETX_BASIC_AUTH', 'FleetX basic authorization header'),
            ],
            'form_params' => [
                'username' => $this->requiredEnv('FLEETX_API_USERNAME', 'FleetX API username'),
                'password' => $this->requiredEnv('FLEETX_API_PASSWORD', 'FleetX API password'),
                'grant_type' => 'password',
            ],
        ], false);

        $token = $response['access_token'] ?? null;
        $targetUser = $user ?: User::query()->first();

        if ($token) {
            $settings->access_token = $token;
            $settings->save();
        }

        if ($token && $targetUser) {
            $targetUser->access_token = $token;
            $targetUser->save();
        }

        if ($token) {
            $this->recordTokenRefresh('fleetx');
        }

        return $token;
    }

    public function getFleetAnalytics(?User $user = null): array
    {
        try {
            $payload = $this->fleetRequest('GET', 'analytics/live?', [], true, $user);

            return array_merge($this->fleetAnalyticsDefaults(), $payload);
        } catch (\Throwable $exception) {
            Log::warning('FleetX analytics unavailable', [
                'message' => $this->maskSensitiveText($exception->getMessage()),
            ]);

            return $this->fleetAnalyticsDefaults();
        }
    }

    public function cachedFleetAnalytics(): array
    {
        $cached = Cache::get('integrations.fleetx.analytics');

        return array_merge($this->fleetAnalyticsDefaults(), is_array($cached) ? $cached : []);
    }

    public function refreshFleetAnalyticsCache(?User $user = null): array
    {
        $analytics = $this->getFleetAnalytics($user);

        Cache::put('integrations.fleetx.analytics', $analytics, now()->addMinutes(5));

        return $analytics;
    }

    public function findFleetVehicle(string $vehicleNo, ?User $user = null): ?array
    {
        $needle = $this->normalizeVehicleNumber($vehicleNo);
        if (!$needle) {
            return null;
        }

        try {
            $vehicle = $this->fleetRequest('GET', 'analytics/live/byNumber/' . rawurlencode($needle), [], true, $user);
            if ($this->normalizeVehicleNumber($vehicle['vehicleNumber'] ?? null) === $needle) {
                return $vehicle;
            }
        } catch (\Throwable $exception) {
            // Fall back to the full live list when FleetX cannot resolve a single vehicle.
        }

        $analytics = $this->getFleetAnalytics($user);

        foreach ($analytics['vehicles'] as $vehicle) {
            if ($this->normalizeVehicleNumber($vehicle['vehicleNumber'] ?? null) === $needle) {
                return $vehicle;
            }
        }

        return null;
    }

    public function registerSimTracking(array $payload, ?User $user = null): array
    {
        return $this->fleetRequest('POST', 'tp/tracking/sim', [
            'json' => $payload,
        ], true, $user);
    }

    public function stopSimTracking(string $mobileNumber, string $simProvider, ?User $user = null): array
    {
        return $this->fleetRequest('DELETE', 'devices/sim/', [
            'json' => [
                'mobileNumber' => $mobileNumber,
                'simProvider' => $simProvider,
            ],
        ], true, $user);
    }

    public function findWheelsEyeVehicle(string $vehicleNo): ?array
    {
        $settings = $this->getSettings();
        if (!$settings || !$settings->tracing_link || !$settings->address) {
            throw new RuntimeException('WheelsEye tracking configuration is incomplete.');
        }

        $payload = $this->wheelsEyeSnapshot($settings);
        $needle = strtoupper(trim($vehicleNo));

        foreach (($payload['data']['list'] ?? []) as $vehicle) {
            if (($vehicle['vehicleNumber'] ?? null) === $needle) {
                return $vehicle;
            }
        }

        return null;
    }

    public function locateVehicle(Vehicle $vehicle, ?User $user = null): ?array
    {
        if ((int) $vehicle->vehicleStatus === 1) {
            $raw = $this->findFleetVehicle($vehicle->vehicleNo, $user);
            if (!$raw) {
                return null;
            }

            return [
                'source' => 'fleetx',
                'latitude' => $raw['latitude'] ?? null,
                'longitude' => $raw['longitude'] ?? null,
                'location' => $raw['address'] ?? null,
                'raw' => $raw,
            ];
        }

        $raw = $this->findWheelsEyeVehicle($vehicle->vehicleNo);
        if (!$raw) {
            return null;
        }

        return [
            'source' => 'wheelseye',
            'latitude' => $raw['latitude'] ?? null,
            'longitude' => $raw['longitude'] ?? null,
            'location' => $raw['location'] ?? null,
            'raw' => $raw,
        ];
    }

    public function integrationHealth(?User $user = null): array
    {
        return [
            'fleetx' => $this->fleetHealth($user),
            'wheelseye' => $this->wheelsEyeHealth(),
            'travis' => $this->travisHealth($user),
        ];
    }

    public function fleetHealth(?User $user = null): array
    {
        $startedAt = microtime(true);
        $settings = $this->getSettings();
        $localVehicles = $this->localVehicleNumbers(1);
        $health = [
            'label' => 'FleetX',
            'status' => 'offline',
            'configured' => (bool) ($settings && $settings->flee_link),
            'base_url' => $settings ? $settings->flee_link : null,
            'message' => 'FleetX link is not configured.',
            'token_source' => $this->fleetTokenSource($user),
            'stored_token' => $this->maskSecret($this->existingFleetToken($user)),
            'token_refreshed' => false,
            'local_vehicle_count' => count($localVehicles),
            'remote_vehicle_count' => 0,
            'matched_vehicle_count' => 0,
            'coverage_percent' => 0,
            'running_vehicle_count' => 0,
            'sample_remote' => null,
            'sample_matches' => [],
            'issues' => [],
        ];

        if (!$health['configured']) {
            $this->recordIntegrationError('fleetx', $health['message']);

            return $this->attachMonitor($health, 'fleetx');
        }

        try {
            $client = $this->fleetClient($settings);
            $token = $this->existingFleetToken($user);

            if (!$token) {
                $token = $this->refreshFleetToken($user);
                $health['token_refreshed'] = !empty($token);
                if ($health['token_refreshed']) {
                    $health['issues'][] = 'FleetX token was missing and has been refreshed from the API login.';
                }
            }

            if (!$token) {
                throw new RuntimeException('FleetX access token is not available.');
            }

            try {
                $payload = $this->fleetAnalyticsRequest($client, $token);
            } catch (RequestException $exception) {
                $status = $exception->getResponse() ? $exception->getResponse()->getStatusCode() : null;

                if (!in_array($status, [401, 403], true)) {
                    throw $exception;
                }

                $token = $this->refreshFleetToken($user);
                if (!$token) {
                    throw new RuntimeException('FleetX access token refresh failed.');
                }

                $health['token_refreshed'] = true;
                $health['issues'][] = 'Stored FleetX token was rejected and replaced with a fresh token.';
                $payload = $this->fleetAnalyticsRequest($client, $token);
            }

            $remoteVehicles = $this->extractVehicleNumbers($payload['vehicles'] ?? []);
            $matches = array_values(array_intersect($localVehicles, $remoteVehicles));

            $health['token_source'] = $this->fleetTokenSource($user);
            $health['stored_token'] = $this->maskSecret($this->existingFleetToken($user));
            $health['remote_vehicle_count'] = count($remoteVehicles);
            $health['matched_vehicle_count'] = count($matches);
            $health['coverage_percent'] = $this->coveragePercent($health['matched_vehicle_count'], $health['local_vehicle_count']);
            $health['running_vehicle_count'] = (int) ($payload['runningVehicles'] ?? 0);
            $health['sample_remote'] = $remoteVehicles ? $remoteVehicles[0] : null;
            $health['sample_matches'] = array_slice($matches, 0, 6);
            $this->syncFleetTokenToSettings($token);

            if ($health['remote_vehicle_count'] === 0) {
                $health['status'] = 'warning';
                $health['message'] = 'FleetX responded, but no live vehicles were returned.';
            } elseif ($health['local_vehicle_count'] === 0) {
                $health['status'] = 'online';
                $health['message'] = 'FleetX analytics is reachable. No local market vehicles are stored yet.';
            } elseif ($health['matched_vehicle_count'] === 0) {
                $health['status'] = 'warning';
                $health['message'] = 'FleetX is reachable, but none of the local market vehicles matched the live analytics feed.';
            } elseif ($health['matched_vehicle_count'] < $health['local_vehicle_count']) {
                $health['status'] = 'warning';
                $health['message'] = 'FleetX is reachable with partial coverage of local market vehicles.';
            } else {
                $health['status'] = 'online';
                $health['message'] = 'FleetX analytics and token flow are healthy.';
            }

            $this->recordIntegrationSuccess('fleetx', $this->elapsedMs($startedAt));
        } catch (RequestException $exception) {
            $health['message'] = $this->extractErrorMessage($exception, 'FleetX request failed.');
            $this->recordIntegrationError('fleetx', $health['message'], $this->elapsedMs($startedAt));
        } catch (\Throwable $exception) {
            $health['message'] = $this->maskSensitiveText($exception->getMessage());
            $this->recordIntegrationError('fleetx', $health['message'], $this->elapsedMs($startedAt));
        }

        return $this->attachMonitor($health, 'fleetx');
    }

    public function wheelsEyeHealth(): array
    {
        $startedAt = microtime(true);
        $settings = $this->getSettings();
        $localVehicles = $this->localVehicleNumbers(0);
        $health = [
            'label' => 'WheelsEye',
            'status' => 'offline',
            'configured' => (bool) ($settings && $settings->tracing_link && $settings->address),
            'base_url' => $settings ? $settings->tracing_link : null,
            'message' => 'WheelsEye tracking configuration is incomplete.',
            'token_source' => 'Settings',
            'stored_token' => $this->maskSecret($settings ? $settings->address : null),
            'local_vehicle_count' => count($localVehicles),
            'remote_vehicle_count' => 0,
            'matched_vehicle_count' => 0,
            'coverage_percent' => 0,
            'sample_remote' => null,
            'sample_matches' => [],
            'issues' => [],
        ];

        if (!$health['configured']) {
            $this->recordIntegrationError('wheelseye', $health['message']);

            return $this->attachMonitor($health, 'wheelseye');
        }

        try {
            $payload = $this->wheelsEyeSnapshot($settings);
            $remoteVehicles = $this->extractVehicleNumbers($payload['data']['list'] ?? []);
            $matches = array_values(array_intersect($localVehicles, $remoteVehicles));

            $health['remote_vehicle_count'] = count($remoteVehicles);
            $health['matched_vehicle_count'] = count($matches);
            $health['coverage_percent'] = $this->coveragePercent($health['matched_vehicle_count'], $health['local_vehicle_count']);
            $health['sample_remote'] = $remoteVehicles ? $remoteVehicles[0] : null;
            $health['sample_matches'] = array_slice($matches, 0, 6);

            if ($health['remote_vehicle_count'] === 0) {
                $health['status'] = 'warning';
                $health['message'] = 'WheelsEye responded, but the token did not return any vehicles.';
            } elseif ($health['local_vehicle_count'] === 0) {
                $health['status'] = 'online';
                $health['message'] = 'WheelsEye tracking is reachable. No local own vehicles are stored yet.';
            } elseif ($health['matched_vehicle_count'] === 0) {
                $health['status'] = 'warning';
                $health['message'] = 'WheelsEye is reachable, but none of the local own vehicles matched the returned list.';
            } elseif ($health['matched_vehicle_count'] < $health['local_vehicle_count']) {
                $health['status'] = 'warning';
                $health['message'] = 'WheelsEye is reachable with partial coverage of local own vehicles.';
            } else {
                $health['status'] = 'online';
                $health['message'] = 'WheelsEye tracking is healthy for the current own-vehicle set.';
            }

            $this->recordIntegrationSuccess('wheelseye', $this->elapsedMs($startedAt));
        } catch (RequestException $exception) {
            $health['message'] = $this->extractErrorMessage($exception, 'WheelsEye request failed.');
            $this->recordIntegrationError('wheelseye', $health['message'], $this->elapsedMs($startedAt));
        } catch (\Throwable $exception) {
            $health['message'] = $this->maskSensitiveText($exception->getMessage());
            $this->recordIntegrationError('wheelseye', $health['message'], $this->elapsedMs($startedAt));
        }

        return $this->attachMonitor($health, 'wheelseye');
    }

    public function travisHealth(?User $user = null): array
    {
        $startedAt = microtime(true);
        $settings = $this->getSettings();
        $systemUser = User::query()->first();
        $email = $this->systemBocshEmail($systemUser);
        $storedToken = $this->existingBocshToken($user);
        $health = [
            'label' => 'Travis',
            'status' => 'offline',
            'configured' => (bool) ($settings && $settings->bocsh_link),
            'base_url' => $settings ? $settings->bocsh_link : null,
            'message' => 'Travis link is not configured.',
            'token_source' => $this->bocshTokenSource($user),
            'stored_token' => $this->maskSecret($storedToken),
            'system_email' => $email,
            'issued_token' => '-',
            'active_tracking_count' => Tracking::query()->where('status', 0)->count(),
            'completed_tracking_count' => Tracking::query()->whereIn('status', [1, 3])->count(),
            'issues' => [],
        ];

        if (!$health['configured']) {
            $this->recordIntegrationError('travis', $health['message']);

            return $this->attachMonitor($health, 'travis');
        }

        if (!$this->envIsConfigured('TRAVIS_SYSTEM_PASSWORD')) {
            $health['status'] = $storedToken ? 'warning' : 'offline';
            $health['message'] = $storedToken
                ? 'Travis stored token is available, but fresh login is disabled because the system password is not configured.'
                : 'Travis system password is not configured and no stored token is available.';
            $health['issues'][] = 'Set TRAVIS_SYSTEM_PASSWORD in .env to enable token refresh.';

            $this->recordIntegrationError('travis', $health['message']);

            return $this->attachMonitor($health, 'travis');
        }

        try {
            $response = $this->loginToBocsh($email, $this->requiredEnv('TRAVIS_SYSTEM_PASSWORD', 'Travis system password'));
            if (!$this->loginSucceeded($response)) {
                throw new RuntimeException($response['message'] ?? 'Travis authentication failed.');
            }

            $health['status'] = 'online';
            $health['message'] = 'Travis authentication succeeded and the LR API is reachable.';
            $issuedToken = $response['token'] ?? null;
            $health['issued_token'] = $this->maskSecret($issuedToken);
            if ($issuedToken) {
                $this->recordTokenRefresh('travis');
            }
            $this->recordIntegrationSuccess('travis', $this->elapsedMs($startedAt));
        } catch (RequestException $exception) {
            $health['message'] = $this->extractErrorMessage($exception, 'Travis request failed.');
            $this->recordIntegrationError('travis', $health['message'], $this->elapsedMs($startedAt));
        } catch (\Throwable $exception) {
            $health['message'] = $this->maskSensitiveText($exception->getMessage());
            $this->recordIntegrationError('travis', $health['message'], $this->elapsedMs($startedAt));
        }

        return $this->attachMonitor($health, 'travis');
    }

    public function syncTracking(Tracking $tracking, ?User $user = null): array
    {
        $this->refreshTrackingLocation($tracking, $user);

        return $this->pushTrackingToTravis($tracking, $user);
    }

    public function refreshTrackingLocation(Tracking $tracking, ?User $user = null): ?array
    {
        $vehicle = Vehicle::query()->where('vehicleNo', $tracking->vehicleNo)->first();

        if ($vehicle) {
            $position = $this->locateVehicle($vehicle, $user);
            if ($position) {
                $tracking->latitude = $position['latitude'];
                $tracking->longitude = $position['longitude'];
                $tracking->location = $position['location'];
                $tracking->save();

                return $position;
            }
        }

        return null;
    }

    public function pushTrackingToTravis(Tracking $tracking, ?User $user = null): array
    {
        $response = $this->bocshRequest('POST', '/api/lr/tracking', $this->trackingPayload($tracking), $user);

        return $this->ensureBocshSuccess($response, 'Tracking sync failed.', 'insertFlag');
    }

    public function syncFleetLiveLocations(?User $user = null): int
    {
        $analytics = $this->refreshFleetAnalyticsCache($user);
        $vehicles = $this->indexFleetVehicles($analytics['vehicles'] ?? []);
        $updated = 0;

        foreach ($this->activeTrackingsForVehicleStatus(1)->get() as $tracking) {
            $normalized = $this->normalizeVehicleNumber($tracking->vehicleNo);
            if (!$normalized || !isset($vehicles[$normalized])) {
                continue;
            }

            $raw = $vehicles[$normalized];
            $tracking->latitude = $raw['latitude'] ?? null;
            $tracking->longitude = $raw['longitude'] ?? null;
            $tracking->location = $raw['address'] ?? null;
            $tracking->save();
            $updated++;
        }

        return $updated;
    }

    public function syncWheelsEyeLocations(): int
    {
        $settings = $this->getSettings();
        if (!$settings || !$settings->tracing_link || !$settings->address) {
            throw new RuntimeException('WheelsEye tracking configuration is incomplete.');
        }

        $payload = $this->wheelsEyeSnapshot($settings);
        $vehicles = $this->indexWheelsEyeVehicles($payload['data']['list'] ?? []);
        $updated = 0;

        foreach ($this->activeTrackingsForVehicleStatus(0)->get() as $tracking) {
            $normalized = $this->normalizeVehicleNumber($tracking->vehicleNo);
            if (!$normalized || !isset($vehicles[$normalized])) {
                continue;
            }

            $raw = $vehicles[$normalized];
            $tracking->latitude = $raw['latitude'] ?? null;
            $tracking->longitude = $raw['longitude'] ?? null;
            $tracking->location = $raw['location'] ?? null;
            $tracking->save();
            $updated++;
        }

        return $updated;
    }

    public function syncWeightCorrection(Weight $weight, bool $recorrection = false, ?User $user = null): array
    {
        $endpoint = $recorrection
            ? '/api/ilsp/weight-recorrection'
            : '/api/ilsp/weight-correction';

        $response = $this->bocshRequest('POST', $endpoint, [
            'lrNumber' => $weight->lrNumber,
            'lspId' => $weight->lspId,
            'correctedWeight' => $weight->correctedWeight,
            'length' => $this->normalizeDecimal($weight->length),
            'breadth' => $this->normalizeDecimal($weight->breadth),
            'height' => $this->normalizeDecimal($weight->height),
        ], $user);

        return $this->ensureBocshSuccess($response, $recorrection ? 'Weight re-correction failed.' : 'Weight correction failed.');
    }

    public function uploadEpod(Epod $epod, string $base64File, ?User $user = null): array
    {
        $response = $this->bocshRequest('POST', '/api/lr/epod', [
            'lspId' => $epod->lspId,
            'lrNumber' => $epod->lrNumber,
            'epod' => $base64File,
        ], $user);

        return $this->ensureBocshSuccess($response, 'EPOD upload failed.', 'uploadFlag');
    }

    public function reuploadEpod(Epod $epod, string $base64File, ?User $user = null): array
    {
        $response = $this->bocshRequest('POST', '/api/lr/epod-reupload', [
            'lspId' => $epod->lspId,
            'lrNumber' => $epod->lrNumber,
            'epod' => $base64File,
        ], $user);

        return $this->ensureBocshSuccess($response, 'EPOD re-upload failed.', 'uploadFlag');
    }

    public function loginSucceeded(array $response): bool
    {
        return $this->isTruthy($response['success'] ?? true) && !empty($response['token']);
    }

    private function getBocshToken(?User $user = null): ?string
    {
        if ($user && $user->bearer_token) {
            return $user->bearer_token;
        }

        $systemUser = User::query()->first();
        if ($systemUser && $systemUser->bearer_token) {
            return $systemUser->bearer_token;
        }

        return $this->syncSystemBocshToken();
    }

    private function getFleetToken(?User $user = null): ?string
    {
        if ($user && $user->access_token) {
            return $user->access_token;
        }

        $settings = $this->getSettings();
        if ($settings && $settings->access_token) {
            return $settings->access_token;
        }

        $systemUser = User::query()->first();
        if ($systemUser && $systemUser->access_token) {
            return $systemUser->access_token;
        }

        return $this->refreshFleetToken($user);
    }

    private function fleetRequest(
        string $method,
        string $uri,
        array $options = [],
        bool $authenticate = true,
        ?User $user = null
    ): array {
        $settings = $this->getSettings();
        if (!$settings || !$settings->flee_link) {
            throw new RuntimeException('FleetX link is not configured.');
        }

        $client = new Client([
            'base_uri' => $this->normalizeBaseUri($settings->flee_link),
            'verify' => $this->verifyTls(),
            'timeout' => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::DEFAULT_CONNECT_TIMEOUT,
        ]);
        $token = null;

        if ($authenticate) {
            $token = $this->getFleetToken($user);
            if (!$token) {
                throw new RuntimeException('FleetX access token is not available.');
            }

            $options['headers']['Authorization'] = 'Bearer ' . $token;
            $options['headers']['Content-Type'] = $options['headers']['Content-Type'] ?? 'application/json';
        }

        try {
            $response = $client->request($method, $uri, $options);
            $this->syncFleetTokenToSettings($token ?? null);

            return json_decode((string) $response->getBody(), true) ?: [];
        } catch (RequestException $exception) {
            $status = $exception->getResponse() ? $exception->getResponse()->getStatusCode() : null;

            if ($authenticate && in_array($status, [401, 403], true)) {
                $freshToken = $this->refreshFleetToken($user);
                if ($freshToken) {
                    $options['headers']['Authorization'] = 'Bearer ' . $freshToken;
                    $retry = $client->request($method, $uri, $options);
                    $this->syncFleetTokenToSettings($freshToken);

                    return json_decode((string) $retry->getBody(), true) ?: [];
                }
            }

            throw new RuntimeException($this->extractErrorMessage($exception, 'FleetX request failed.'));
        }
    }

    private function bocshRequest(
        string $method,
        string $uri,
        array $payload,
        ?User $user = null,
        bool $authenticate = true
    ): array {
        $settings = $this->getSettings();
        if (!$settings || !$settings->bocsh_link) {
            throw new RuntimeException('BOCSH link is not configured.');
        }

        $client = new Client([
            'base_uri' => $this->normalizeBaseUri($settings->bocsh_link),
            'verify' => $this->verifyTls(),
            'timeout' => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::DEFAULT_CONNECT_TIMEOUT,
        ]);

        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ];

        if ($authenticate) {
            $token = $this->getBocshToken($user);
            if (!$token) {
                throw new RuntimeException('BOCSH bearer token is not available.');
            }

            $options['headers']['Authorization'] = 'Bearer ' . $token;
        }

        try {
            $response = $client->request($method, ltrim($uri, '/'), $options);

            return json_decode((string) $response->getBody(), true) ?: [];
        } catch (RequestException $exception) {
            $status = $exception->getResponse() ? $exception->getResponse()->getStatusCode() : null;

            if ($authenticate && in_array($status, [401, 403], true)) {
                $freshToken = $this->syncSystemBocshToken($user);
                if ($freshToken) {
                    $options['headers']['Authorization'] = 'Bearer ' . $freshToken;
                    $retry = $client->request($method, ltrim($uri, '/'), $options);

                    return json_decode((string) $retry->getBody(), true) ?: [];
                }
            }

            throw new RuntimeException($this->extractErrorMessage($exception, 'BOCSH request failed.'));
        }
    }

    private function extractErrorMessage(RequestException $exception, string $fallback): string
    {
        if ($exception->getResponse()) {
            $payload = json_decode((string) $exception->getResponse()->getBody(), true) ?: [];

            if (!empty($payload['message'])) {
                return (string) $payload['message'];
            }
        }

        return $this->maskSensitiveText($exception->getMessage() ?: $fallback);
    }

    private function attachMonitor(array $health, string $provider): array
    {
        return array_merge($health, $this->monitorSnapshot($provider));
    }

    private function monitorSnapshot(string $provider): array
    {
        $defaults = [
            'last_success_at' => '-',
            'last_error_at' => '-',
            'last_error' => '-',
            'token_refreshed_at' => '-',
            'response_time_ms' => null,
        ];

        try {
            $monitor = IntegrationMonitor::query()->firstOrCreate(['provider' => $provider]);

            return [
                'last_success_at' => $this->formatMonitorDate($monitor->last_success_at),
                'last_error_at' => $this->formatMonitorDate($monitor->last_error_at),
                'last_error' => $monitor->last_error ?: '-',
                'token_refreshed_at' => $this->formatMonitorDate($monitor->token_refreshed_at),
                'response_time_ms' => $monitor->response_time_ms,
            ];
        } catch (\Throwable $exception) {
            return $defaults;
        }
    }

    private function recordIntegrationSuccess(string $provider, ?int $responseMs = null): void
    {
        try {
            $values = [
                'last_success_at' => now(),
            ];

            if ($responseMs !== null) {
                $values['response_time_ms'] = $responseMs;
            }

            IntegrationMonitor::query()->updateOrCreate(['provider' => $provider], $values);
        } catch (\Throwable $exception) {
            // Monitoring must never block the integration health check itself.
        }
    }

    private function recordIntegrationError(string $provider, string $message, ?int $responseMs = null): void
    {
        try {
            $values = [
                'last_error_at' => now(),
                'last_error' => substr($this->maskSensitiveText($message), 0, 2000),
                'response_time_ms' => $responseMs,
            ];

            IntegrationMonitor::query()->updateOrCreate(['provider' => $provider], $values);
        } catch (\Throwable $exception) {
            // Monitoring must never block the integration health check itself.
        }
    }

    private function recordTokenRefresh(string $provider): void
    {
        try {
            IntegrationMonitor::query()->updateOrCreate([
                'provider' => $provider,
            ], [
                'token_refreshed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            // Monitoring must never block token refresh.
        }
    }

    private function elapsedMs(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }

    private function formatMonitorDate($value): string
    {
        if (!$value) {
            return '-';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d M Y, h:i A');
        }

        $timestamp = strtotime((string) $value);

        return $timestamp ? date('d M Y, h:i A', $timestamp) : '-';
    }

    private function normalizeBaseUri(string $uri): string
    {
        return rtrim($uri, '/') . '/';
    }

    private function fleetClient(Setting $settings): Client
    {
        return new Client([
            'base_uri' => $this->normalizeBaseUri($settings->flee_link),
            'verify' => $this->verifyTls(),
            'timeout' => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::DEFAULT_CONNECT_TIMEOUT,
        ]);
    }

    private function wheelsEyeClient(Setting $settings): Client
    {
        return new Client([
            'base_uri' => $this->normalizeBaseUri($settings->tracing_link),
            'verify' => $this->verifyTls(),
            'timeout' => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::DEFAULT_CONNECT_TIMEOUT,
        ]);
    }

    private function fleetAnalyticsRequest(Client $client, string $token): array
    {
        $response = $client->request('GET', 'analytics/live?', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode((string) $response->getBody(), true) ?: [];
    }

    private function wheelsEyeSnapshot(Setting $settings): array
    {
        $client = $this->wheelsEyeClient($settings);
        $response = $client->request('GET', 'currentLoc?accessToken=' . $settings->address, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode((string) $response->getBody(), true) ?: [];
    }

    private function activeTrackingsForVehicleStatus(int $vehicleStatus)
    {
        $query = Tracking::query()->where('status', 0);

        if (Schema::hasColumn('trackings', 'vehicle_status')) {
            return $query->where('vehicle_status', $vehicleStatus);
        }

        $vehicleNumbers = Vehicle::query()
            ->where('vehicleStatus', $vehicleStatus)
            ->pluck('vehicleNo');

        return $query->whereIn('vehicleNo', $vehicleNumbers);
    }

    private function indexFleetVehicles(array $vehicles): array
    {
        $indexed = [];

        foreach ($vehicles as $vehicle) {
            $normalized = $this->normalizeVehicleNumber($vehicle['vehicleNumber'] ?? null);
            if ($normalized) {
                $indexed[$normalized] = $vehicle;
            }
        }

        return $indexed;
    }

    private function indexWheelsEyeVehicles(array $vehicles): array
    {
        $indexed = [];

        foreach ($vehicles as $vehicle) {
            $normalized = $this->normalizeVehicleNumber($vehicle['vehicleNumber'] ?? null);
            if ($normalized) {
                $indexed[$normalized] = $vehicle;
            }
        }

        return $indexed;
    }

    private function localVehicleNumbers(int $vehicleStatus): array
    {
        $rows = Vehicle::query()
            ->where('vehicleStatus', $vehicleStatus)
            ->pluck('vehicleNo');

        $numbers = [];

        foreach ($rows as $vehicleNo) {
            $normalized = $this->normalizeVehicleNumber($vehicleNo);
            if ($normalized) {
                $numbers[$normalized] = $normalized;
            }
        }

        return array_values($numbers);
    }

    private function extractVehicleNumbers(array $vehicles): array
    {
        $numbers = [];

        foreach ($vehicles as $vehicle) {
            $normalized = $this->normalizeVehicleNumber($vehicle['vehicleNumber'] ?? null);
            if ($normalized) {
                $numbers[$normalized] = $normalized;
            }
        }

        return array_values($numbers);
    }

    private function normalizeVehicleNumber($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    private function coveragePercent(int $matched, int $local): float
    {
        if ($local < 1) {
            return 0;
        }

        return round(($matched * 100) / $local, 1);
    }

    private function existingFleetToken(?User $user = null): ?string
    {
        if ($user && $user->access_token) {
            return $user->access_token;
        }

        $settings = $this->getSettings();
        if ($settings && $settings->access_token) {
            return $settings->access_token;
        }

        $systemUser = User::query()->first();
        if ($systemUser && $systemUser->access_token) {
            return $systemUser->access_token;
        }

        return null;
    }

    private function existingBocshToken(?User $user = null): ?string
    {
        if ($user && $user->bearer_token) {
            return $user->bearer_token;
        }

        $systemUser = User::query()->first();
        if ($systemUser && $systemUser->bearer_token) {
            return $systemUser->bearer_token;
        }

        return null;
    }

    private function fleetTokenSource(?User $user = null): string
    {
        if ($user && $user->access_token) {
            return 'User';
        }

        $settings = $this->getSettings();
        if ($settings && $settings->access_token) {
            return 'Settings';
        }

        $systemUser = User::query()->first();
        if ($systemUser && $systemUser->access_token) {
            return 'System User';
        }

        return 'Missing';
    }

    private function bocshTokenSource(?User $user = null): string
    {
        if ($user && $user->bearer_token) {
            return 'User';
        }

        $systemUser = User::query()->first();
        if ($systemUser && $systemUser->bearer_token) {
            return 'System User';
        }

        return 'Missing';
    }

    private function maskSecret(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        $length = strlen($value);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 4) . str_repeat('*', $length - 8) . substr($value, -4);
    }

    private function syncFleetTokenToSettings(?string $token): void
    {
        if (!$token) {
            return;
        }

        $settings = $this->getSettings();
        if (!$settings) {
            return;
        }

        if ($settings->access_token === $token) {
            return;
        }

        try {
            $settings->access_token = $token;
            $settings->save();
        } catch (\Throwable $exception) {
            Log::warning('Unable to persist FleetX token to settings', [
                'message' => $this->maskSensitiveText($exception->getMessage()),
            ]);
        }
    }

    private function trackingPayload(Tracking $tracking): array
    {
        $payload = [
            'lrTrackingDetails' => [
                'lspId' => $this->nullableString($tracking->lspId),
                'lrNumber' => $this->nullableString($tracking->lrNumber),
                'lrStatus' => $this->nullableString($tracking->lrStatus),
                'latitude' => $this->nullableString($tracking->latitude),
                'longitude' => $this->nullableString($tracking->longitude),
                'location' => $this->nullableString($tracking->location),
                'pickUpDate' => $this->nullableString($tracking->pickUpDate),
                'lrDate' => $this->nullableString($tracking->lrDate),
                'actualDeliveredDate' => $tracking->lrStatus === 'Shipment Delivered'
                    ? $this->nullableString($tracking->actualDeliveredDate)
                    : '',
                'edd' => $this->nullableString($tracking->edd),
                'receiverName' => $this->nullableString($tracking->receiverName),
                'deliveredToPerson' => $this->nullableString($tracking->deliveredToPerson),
                'actualWeight' => $this->nullableString($tracking->actualWeight),
                'numberOfPackages' => $this->nullableString($tracking->numberOfPackages),
                'length' => $this->normalizeDecimal($tracking->length),
                'breadth' => $this->normalizeDecimal($tracking->breadth),
                'height' => $this->normalizeDecimal($tracking->height),
                'truckType' => $this->nullableString($tracking->truckType),
                'truckTonnage' => $this->nullableString($tracking->truckTonnage),
                'vehicleNo' => $this->nullableString($tracking->vehicleNo),
                'deliveryNotes' => $this->nullableString($tracking->deliveryNotes),
            ],
        ];

        $this->validateTrackingPayload($payload['lrTrackingDetails']);

        return $payload;
    }

    private function validateTrackingPayload(array $payload): void
    {
        $requiredFields = [
            'lspId' => 'LSP ID',
            'lrNumber' => 'LR Number',
            'lrStatus' => 'LR Status',
            'location' => 'Location',
            'edd' => 'EDD',
            'actualWeight' => 'Actual Weight',
            'numberOfPackages' => 'Number Of Packages',
            'length' => 'Length',
            'breadth' => 'Breadth',
            'height' => 'Height',
            'truckType' => 'Truck Type',
            'truckTonnage' => 'Truck Tonnage',
            'vehicleNo' => 'Vehicle Number',
        ];

        foreach ($requiredFields as $field => $label) {
            if (!isset($payload[$field]) || $payload[$field] === '') {
                throw new RuntimeException($label . ' is required by the Travis LR tracking API.');
            }
        }

        if (!in_array($payload['lrStatus'], self::lrStatuses(), true)) {
            throw new RuntimeException('LR Status is not valid for the Travis LR tracking API.');
        }

        if (!in_array($payload['truckType'], self::truckTypes(), true)) {
            throw new RuntimeException('Truck Type must be one of the Travis-supported values.');
        }

        if (!in_array($payload['truckTonnage'], self::truckTonnages(), true)) {
            throw new RuntimeException('Truck Tonnage must match the Travis-supported list.');
        }

        if ($payload['lrStatus'] === 'Shipment Delivered' && empty($payload['actualDeliveredDate'])) {
            throw new RuntimeException('Actual Delivered Date is required when LR Status is Shipment Delivered.');
        }
    }

    private function ensureBocshSuccess(array $response, string $fallback, ?string $flagKey = null): array
    {
        if (empty($response)) {
            throw new RuntimeException($fallback);
        }

        $success = !array_key_exists('success', $response) || $this->isTruthy($response['success']);
        $flagOkay = true;

        if ($flagKey && array_key_exists($flagKey, $response)) {
            $flagOkay = $this->isTruthy($response[$flagKey]);
        }

        if (!$success || !$flagOkay) {
            throw new RuntimeException($response['message'] ?? $fallback);
        }

        return $response;
    }

    private function normalizeDecimal($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 3, '.', '');
    }

    private function nullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function isTruthy($value): bool
    {
        if (is_bool($value) || is_int($value)) {
            return $value === true || $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return $normalized === 'true' || preg_match('/^1(\b|[^0-9])/', $normalized) === 1;
        }

        return false;
    }

    private function verifyTls()
    {
        if (!filter_var(env('TRAVIS_VERIFY_TLS', true), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $caBundle = trim((string) env('TRAVIS_CA_BUNDLE', ''));

        if ($caBundle === '') {
            return true;
        }

        if (!is_file($caBundle)) {
            throw new RuntimeException('Configured Travis CA bundle was not found.');
        }

        return $caBundle;
    }

    private function requiredEnv(string $key, string $label): string
    {
        $value = trim((string) env($key, ''));

        if ($value === '') {
            throw new RuntimeException($label . ' is not configured.');
        }

        return $value;
    }

    private function envIsConfigured(string $key): bool
    {
        return trim((string) env($key, '')) !== '';
    }

    private function systemBocshEmail(?User $systemUser = null): string
    {
        $email = trim((string) env('TRAVIS_SYSTEM_EMAIL', ''));

        if ($email !== '') {
            return $email;
        }

        if ($systemUser && $systemUser->email) {
            return $systemUser->email;
        }

        return self::SYSTEM_EMAIL;
    }

    private function maskSensitiveText(string $value): string
    {
        return preg_replace_callback(
            '/([?&][^=&]*(?:password|token|secret|authorization|bearer|access|epod)[^=]*)=([^&\\s]*)/i',
            function (array $matches) {
                return $matches[1] . '=[masked]';
            },
            $value
        );
    }
}
