<?php

namespace Database\Seeders;

use App\Models\DeliveryArea;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeliveryAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('data/delivery_areas.json');

        if (! file_exists($path)) {
            $this->command?->error("Delivery area data file not found: {$path}");

            return;
        }

        $areas = json_decode(file_get_contents($path), true);

        if (! is_array($areas)) {
            $this->command?->error('Invalid delivery area data JSON.');

            return;
        }

        DB::table('delivery_areas')->delete();

        $rows = collect($areas)->map(function (array $area) {
            return [
                'id' => $area['id'],
                'name' => $area['name'],
                'district_id' => $area['district_id'],
                'district_name' => $area['district_name'],
                'hub_id' => $area['hub_id'] ?? null,
                'ps_type' => $area['ps_type'] ?? null,
                'big_parcel' => (bool) ($area['big_parcel'] ?? false),
                'post_code' => $area['post_code'] ?? null,
                'address' => $area['address'] ?? null,
                'search_tags' => $area['search_tags'] ?? null,
                'phone' => $area['phone'] ?? null,
                'admin_id' => $area['admin_id'] ?? null,
                'updated_by' => $area['updated_by'] ?? null,
                'status' => (bool) ($area['status'] ?? true),
                'created_at' => isset($area['created_at'])
                    ? Carbon::parse($area['created_at'])
                    : now(),
                'updated_at' => isset($area['updated_at'])
                    ? Carbon::parse($area['updated_at'])
                    : now(),
            ];
        });

        foreach ($rows->chunk(200) as $chunk) {
            DeliveryArea::query()->insert($chunk->values()->all());
        }

        $this->command?->info('Seeded '.$rows->count().' delivery areas.');
    }
}
