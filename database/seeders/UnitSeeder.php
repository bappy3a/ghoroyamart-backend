<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Seed the application's units.
     */
    public function run(): void
    {
        $units = [
            'Pcs',
            'Kg',
            'Gram',
            'Liter',
            'Ml',
            'Meter',
            'Cm',
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['name' => $unit]);
        }
    }
}
