<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PerformanceSeeder extends Seeder
{
    public function run(): void
    {
        abort_if(app()->isProduction(), 403, 'Never seed performance data in production.');

        if (User::count() < 50) {
            User::factory()->count(50 - User::count())->create();
        }
        $userIds = User::pluck('id')->all();
        $now = now();

        foreach (array_chunk(range(1, 200_000), 1000) as $chunk) {
            $rows = [];
            foreach ($chunk as $i) {
                $rows[] = [
                    'user_id' => $userIds[array_rand($userIds)],
                    'title' => 'Perf post '.$i,
                    'slug' => 'perf-post-'.$i.'-'.Str::random(6),
                    'body' => Str::random(300),
                    'featured' => $i % 50 === 0,
                    'published' => $i % 5 !== 0,
                    'comments_count' => random_int(0, 20),
                    'created_at' => $now->copy()->subSeconds(random_int(0, 31_536_000)),
                    'updated_at' => $now,
                ];
            }
            DB::table('posts')->insert($rows);
        }
    }
}