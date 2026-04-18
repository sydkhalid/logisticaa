<?php

namespace App\Services\V2;

use App\Models\Epod;
use App\Models\Setting;
use App\Models\Tracking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Weight;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ExternalLogisticsService
{
    private const FLEET_BASIC_AUTH = 'Basic ZmxlZXR4OnNlY3JldA==';
    private const FLEET_USERNAME = 'API_User_Dont_Delete_10087';
    private const FLEET_PASSWORD = 'sPQe45lW';
    private const SYSTEM_EMAIL = 'connect@logisticaa.co.in';
    private const SYSTEM_PASSWORD = '!Meenakshi1';

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

    public function syncSystemBocshToken(): ?string
    {
        $systemUser = User::query()->first();
        $email = $systemUser ? $systemUser->email : self::SYSTEM_EMAIL;
        $response = $this->loginToBocsh($email, self::SYSTEM_PASSWORD);
        $token = $response['token'] ?? null;

        if ($token && $systemUser) {
            $systemUser->bearer_token = $token;
            $systemUser->save();
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
                'Authorization' => self::FLEET_BASIC_AUTH,
            ],
            'form_params' => [
                'username' => self::FLEET_USERNAME,
                'password' => self::FLEET_PASSWORD,
                'grant_type' => 'password',
            ],
        ], false);

        $token = $response['access_token'] ?? null;
        $targetUser = $user ?: User::query()->first();

        if ($token && $targetUser) {
            $targetUser->access_token = $token;
            $targetUser->save();
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
                'message' => $exception->getMessage(),
            ]);

            return $this->fleetAnalyticsDefaults();
        }
    }

    public function findFleetVehicle(string $vehicleNo, ?User $user = null): ?array
    {
        $needle = strtoupper(trim($vehicleNo));
        $analytics = $this->getFleetAnalytics($user);

        foreach ($analytics['vehicles'] as $vehicle) {
            if (($vehicle['vehicleNumber'] ?? null) === $needle) {
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

        $client = new Client([
            'base_uri' => $this->normalizeBaseUri($settings->tracing_link),
            'verify' => false,
        ]);

        $response = $client->request('GET', 'currentLoc?accessToken=' . $settings->address);
        $payload = json_decode((string) $response->getBody(), true) ?: [];
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

    public function syncTracking(Tracking $tracking, ?User $user = null): array
    {
        $vehicle = Vehicle::query()->where('vehicleNo', $tracking->vehicleNo)->first();

        if ($vehicle) {
            $position = $this->locateVehicle($vehicle, $user);
            if ($position) {
                $tracking->latitude = $position['latitude'];
                $tracking->longitude = $position['longitude'];
                $tracking->location = $position['location'];
                $tracking->save();
            }
        }

        return $this->bocshRequest('POST', '/api/lr/tracking', [
            'lrTrackingDetails' => [
                'lspId' => $tracking->lspId,
                'lrNumber' => $tracking->lrNumber,
                'lrStatus' => $tracking->lrStatus,
                'latitude' => $tracking->latitude,
                'longitude' => $tracking->longitude,
                'location' => $tracking->location,
                'pickUpDate' => $tracking->pickUpDate,
                'lrDate' => $tracking->lrDate,
                'actualDeliveredDate' => $tracking->actualDeliveredDate,
                'edd' => $tracking->edd,
                'receiverName' => $tracking->receiverName,
                'deliveredToPerson' => $tracking->deliveredToPerson,
                'actualWeight' => $tracking->actualWeight,
                'numberOfPackages' => $tracking->numberOfPackages,
                'length' => $tracking->length,
                'breadth' => $tracking->breadth,
                'height' => $tracking->height,
                'truckType' => $tracking->truckType,
                'truckTonnage' => $tracking->truckTonnage,
                'vehicleNo' => $tracking->vehicleNo,
                'deliveryNotes' => $tracking->deliveryNotes,
            ],
        ], $user);
    }

    public function syncWeightCorrection(Weight $weight, bool $recorrection = false, ?User $user = null): array
    {
        $endpoint = $recorrection
            ? '/api/ilsp/weight-recorrection'
            : '/api/ilsp/weight-correction';

        return $this->bocshRequest('POST', $endpoint, [
            'lrNumber' => $weight->lrNumber,
            'lspId' => $weight->lspId,
            'correctedWeight' => $weight->correctedWeight,
            'numberOfPackages' => $weight->numberOfPackages ?? null,
            'length' => $weight->length,
            'breadth' => $weight->breadth,
            'height' => $weight->height,
        ], $user);
    }

    public function uploadEpod(Epod $epod, string $base64File, ?User $user = null): array
    {
        return $this->bocshRequest('POST', '/api/lr/epod', [
            'lspId' => $epod->lspId,
            'lrNumber' => $epod->lrNumber,
            'epod' => $base64File,
        ], $user);
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
            'verify' => false,
        ]);

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

            return json_decode((string) $response->getBody(), true) ?: [];
        } catch (RequestException $exception) {
            $status = $exception->getResponse() ? $exception->getResponse()->getStatusCode() : null;

            if ($authenticate && in_array($status, [401, 403], true)) {
                $freshToken = $this->refreshFleetToken($user);
                if ($freshToken) {
                    $options['headers']['Authorization'] = 'Bearer ' . $freshToken;
                    $retry = $client->request($method, $uri, $options);

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
            'verify' => false,
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
                $freshToken = $this->syncSystemBocshToken();
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

        return $exception->getMessage() ?: $fallback;
    }

    private function normalizeBaseUri(string $uri): string
    {
        return rtrim($uri, '/') . '/';
    }
}
