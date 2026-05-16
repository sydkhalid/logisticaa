<?php

namespace Tests\Unit;

use App\Http\Controllers\AuthController;
use App\Services\V2\ExternalLogisticsService;
use Illuminate\Support\Facades\Config;
use ReflectionClass;
use Tests\TestCase;

class TravisTlsVerificationTest extends TestCase
{
    public function test_v2_travis_tls_falls_back_to_system_ca_when_configured_bundle_is_missing()
    {
        Config::set('integrations.travis.verify_tls', true);
        Config::set('integrations.travis.ca_bundle', base_path('missing-ca-bundle.crt'));

        $this->assertTrue($this->callVerifyTls(new ExternalLogisticsService()));
    }

    public function test_legacy_travis_tls_falls_back_to_system_ca_when_configured_bundle_is_missing()
    {
        Config::set('integrations.travis.verify_tls', true);
        Config::set('integrations.travis.ca_bundle', base_path('missing-ca-bundle.crt'));

        $this->assertTrue($this->callVerifyTls(new AuthController()));
    }

    private function callVerifyTls(object $object)
    {
        $method = (new ReflectionClass($object))->getMethod('verifyTls');
        $method->setAccessible(true);

        return $method->invoke($object);
    }
}
