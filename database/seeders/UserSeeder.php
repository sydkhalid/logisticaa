<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $email = trim((string) env('ADMIN_USER_EMAIL', config('integrations.travis.system_email', 'connect@logisticaa.co.in')));
        $name = trim((string) env('ADMIN_USER_NAME', 'Logisticaa Admin'));
        $password = (string) env('ADMIN_USER_PASSWORD', '');

        $user = User::query()->where('email', $email)->first() ?: new User();
        $user->name = $name !== '' ? $name : 'Logisticaa Admin';
        $user->email = $email;
        $user->is_admin = true;
        $user->remember_token = null;

        if ($password !== '') {
            $user->password = Hash::make($password);
        } elseif (!$user->exists) {
            $user->password = Hash::make(Str::password(32));

            if ($this->command) {
                $this->command->warn('ADMIN_USER_PASSWORD is not set. A random admin password was generated; reset it before login.');
            }
        }

        $user->save();
    }
}
