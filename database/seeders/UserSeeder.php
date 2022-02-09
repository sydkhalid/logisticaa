<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            'name' => 'logisticaa',
            'email' => 'connect@logisticaa.co.in',
            'password' => \Hash::make('!Meenakshi1'),
            'remember_token' => NULL,
            'bearer_token' => NULL,
            'created_at' => '2022-02-01 00:40:21',
            'updated_at' => NULL,
        ]);
    }
}
