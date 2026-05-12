<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['slug' => 'user'], ['name' => 'Usuario']);
        Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agente']);
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador']);
    }
}
