<?php

use App\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('role')->insert(
            [
                ['name' => Role::ROLE_NAME["ADMIN"]],
                ['name' => Role::ROLE_NAME["TUTOR"]],
                ['name' => Role::ROLE_NAME["STUDENT"]],
                ['name' => Role::ROLE_NAME["PUBLISHER"]],
                ['name' => Role::ROLE_NAME["SCHOOL"]],
                ['name' => Role::ROLE_NAME["MARKETING"]],
                ['name' => Role::ROLE_NAME["COMPANY"]]
            ]
        );
    }
}
