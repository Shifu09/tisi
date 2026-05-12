<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::firstOrCreate(['slug' => 'hardware'], [
            'name' => 'Hardware',
            'description' => 'Problemas con equipos físicos, computadoras, impresoras, etc.',
            'color' => '#ef4444'
        ]);

        Category::firstOrCreate(['slug' => 'software'], [
            'name' => 'Software',
            'description' => 'Problemas con aplicaciones, programas, sistema operativo.',
            'color' => '#3b82f6'
        ]);

        Category::firstOrCreate(['slug' => 'red'], [
            'name' => 'Red',
            'description' => 'Problemas de conexión, WiFi, acceso a internet.',
            'color' => '#10b981'
        ]);

        Category::firstOrCreate(['slug' => 'cuentas'], [
            'name' => 'Cuentas y Acceso',
            'description' => 'Problemas con credenciales, permisos, acceso a sistemas.',
            'color' => '#f59e0b'
        ]);
    }
}
