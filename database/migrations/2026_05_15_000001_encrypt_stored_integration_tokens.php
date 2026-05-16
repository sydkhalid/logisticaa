<?php

use App\Services\StoredTokenService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EncryptStoredIntegrationTokens extends Migration
{
    public function up()
    {
        $this->widenTokenColumns();

        foreach (DB::table('users')->select('id', 'access_token', 'bearer_token')->get() as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'access_token' => StoredTokenService::encrypt($user->access_token),
                    'bearer_token' => StoredTokenService::encrypt($user->bearer_token),
                ]);
        }

        foreach (DB::table('settings')->select('id', 'address', 'access_token')->get() as $setting) {
            DB::table('settings')
                ->where('id', $setting->id)
                ->update([
                    'address' => StoredTokenService::encrypt($setting->address),
                    'access_token' => StoredTokenService::encrypt($setting->access_token),
                ]);
        }
    }

    public function down()
    {
        foreach (DB::table('users')->select('id', 'access_token', 'bearer_token')->get() as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'access_token' => StoredTokenService::decrypt($user->access_token),
                    'bearer_token' => StoredTokenService::decrypt($user->bearer_token),
                ]);
        }

        foreach (DB::table('settings')->select('id', 'address', 'access_token')->get() as $setting) {
            DB::table('settings')
                ->where('id', $setting->id)
                ->update([
                    'address' => StoredTokenService::decrypt($setting->address),
                    'access_token' => StoredTokenService::decrypt($setting->access_token),
            ]);
        }
    }

    private function widenTokenColumns(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasColumn('users', 'access_token')) {
            DB::statement('ALTER TABLE `users` MODIFY `access_token` TEXT NULL');
        }

        if (Schema::hasColumn('settings', 'address')) {
            DB::statement('ALTER TABLE `settings` MODIFY `address` TEXT NULL');
        }

        if (Schema::hasColumn('settings', 'access_token')) {
            DB::statement('ALTER TABLE `settings` MODIFY `access_token` TEXT NULL');
        }
    }
}
