<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $users = [
            [
                'name' => 'shahzaib',
                'email' => 'shahzaib@ims.com',
                'password' => Hash::make('shahzaib123'),
            ],
            [
                'name' => 'staff',
                'email' => 'staff@ims.com',
                'password' => Hash::make('staff123'),
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
