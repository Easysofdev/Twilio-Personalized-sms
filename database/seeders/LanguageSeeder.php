<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Language;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {

        $get_language = [
            [
                'name' => 'English',
                'code' => 'en',
                'iso_code' => 'us'
            ]
        ];
        foreach ($get_language as $lan) {
            Language::create($lan);
        }
    }
}
