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
            ['email' => 'ghoroyamart@gmail.com'],
            [
                'name' => 'Ghoroya Mart',
                'username' => 'ghoroyamart',
                'phone' => '00000000000',
                'email' => 'ghoroyamart@gmail.com',
                'user_type' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('GIM$Ge$59Whl'),
            ],
        );

        $this->call([
            PermissionSeeder::class,
            AboutPageSettingSeeder::class,
            CustomPageSeeder::class,
            UnitSeeder::class,
            //SliderSeeder::class,
            //CategorySeeder::class,
            //BedSheetProductSeeder::class,
            //KathaProductSeeder::class,
            //WatchProductSeeder::class,
            // ProductSeeder::class,
            DeliveryAreaSeeder::class,
        ]);
    }
}
