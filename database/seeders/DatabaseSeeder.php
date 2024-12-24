<?php

namespace Database\Seeders;

use App\Models\Utility;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (\Request::route()->getName() != 'LaravelUpdater::database') {
            $this->call(UsersTableSeeder::class);
            $this->call(NotificationSeeder::class);
            $this->call(AiTemplateSeeder::class);
            Artisan::call('module:migrate LandingPage');
            Artisan::call('module:seed LandingPage');
        } else {
            Utility::languagecreate();
        }
    }
}
