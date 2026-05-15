<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_v2_login()
    {
        $this->get('/')->assertRedirect(route('v2.login'));
    }

    public function test_legacy_login_redirects_to_v2_login()
    {
        $this->get('/login')->assertRedirect(route('v2.login'));
    }

    public function test_log_viewer_requires_authentication()
    {
        $this->get('/log-viewer')->assertRedirect(route('v2.login'));
    }

    public function test_system_logs_clear_requires_authentication()
    {
        $this->post(route('v2.logs.clear'))->assertRedirect(route('v2.login'));
    }
}
