<?php

namespace Database\Seeders;

use App\Models\ShiftTemplate;
use Illuminate\Database\Seeder;

class ShiftTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Morning',
                'start_time' => '08:00',
                'end_time' => '16:00',
                'is_overnight' => false,
                'required_employee_count' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Afternoon',
                'start_time' => '14:00',
                'end_time' => '22:00',
                'is_overnight' => false,
                'required_employee_count' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Night',
                'start_time' => '22:00',
                'end_time' => '06:00',
                'is_overnight' => true,
                'required_employee_count' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Rest Day',
                'start_time' => null,
                'end_time' => null,
                'is_overnight' => false,
                'required_employee_count' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            ShiftTemplate::firstOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}
