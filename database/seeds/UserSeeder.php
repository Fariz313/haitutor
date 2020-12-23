<?php

use App\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert(
            [
                [
                    'name'          => "Admin Haitutor",
                    'email'         => "haitutor.id@gmail.com",
                    'password'      => Hash::make("haitutor123"),
                    'birth_date'    => "01/01/2001",
                    'role'          => Role::ROLE["ADMIN"],
                    'contact'       => "085749420404",
                    'company_id'    => "",
                    'address'       => ""
                    ]
            ]
        );
    }
}
