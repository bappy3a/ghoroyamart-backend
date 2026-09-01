<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'bappy@dev.local'],
            [
                'name' => 'Ahmed Bappy',
                'username' => 'bappy',
                'phone' => '01586363179',
                'email' => 'bappy@dev.local',
                'user_type' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        $this->call([
            PermissionSeeder::class,
            AboutPageSettingSeeder::class,
            UnitSeeder::class,
            SliderSeeder::class,
            CategorySeeder::class,
            BedSheetProductSeeder::class,
            KathaProductSeeder::class,
            WatchProductSeeder::class,
            // ProductSeeder::class,
            DeliveryAreaSeeder::class,
        ]);
    }
}
