<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Engine System
            ['name' => 'Engine System',          'slug' => 'engine-system'],
            ['name' => 'Spark Plugs',            'slug' => 'spark-plugs'],
            ['name' => 'Air Filters',            'slug' => 'air-filters'],
            ['name' => 'Oil Filters',            'slug' => 'oil-filters'],
            ['name' => 'Timing Belts & Chains',  'slug' => 'timing-belts-chains'],
            ['name' => 'Engine Gaskets',         'slug' => 'engine-gaskets'],

            // Fuel System
            ['name' => 'Fuel System',            'slug' => 'fuel-system'],
            ['name' => 'Fuel Pumps',             'slug' => 'fuel-pumps'],
            ['name' => 'Fuel Injectors',         'slug' => 'fuel-injectors'],
            ['name' => 'Fuel Filters',           'slug' => 'fuel-filters'],

            // Brake System
            ['name' => 'Brake System',           'slug' => 'brake-system'],
            ['name' => 'Brake Pads',             'slug' => 'brake-pads'],
            ['name' => 'Brake Rotors',           'slug' => 'brake-rotors'],
            ['name' => 'Brake Calipers',         'slug' => 'brake-calipers'],
            ['name' => 'Brake Fluid',            'slug' => 'brake-fluid'],

            // Suspension & Steering
            ['name' => 'Suspension & Steering',  'slug' => 'suspension-steering'],
            ['name' => 'Shock Absorbers',        'slug' => 'shock-absorbers'],
            ['name' => 'Control Arms',           'slug' => 'control-arms'],
            ['name' => 'Struts',                 'slug' => 'struts'],
            ['name' => 'Steering Racks',         'slug' => 'steering-racks'],

            // Electrical
            ['name' => 'Electrical',             'slug' => 'electrical'],
            ['name' => 'Batteries',              'slug' => 'batteries'],
            ['name' => 'Alternators',            'slug' => 'alternators'],
            ['name' => 'Starters',               'slug' => 'starters'],
            ['name' => 'Sensors & Switches',     'slug' => 'sensors-switches'],
            ['name' => 'Ignition Coils',         'slug' => 'ignition-coils'],
            ['name' => 'Headlights & Bulbs',     'slug' => 'headlights-bulbs'],

            // Fluids & Oils
            ['name' => 'Fluids & Oils',          'slug' => 'fluids-oils'],
            ['name' => 'Engine Oil',             'slug' => 'engine-oil'],
            ['name' => 'Transmission Fluid',     'slug' => 'transmission-fluid'],
            ['name' => 'Coolant & Antifreeze',   'slug' => 'coolant-antifreeze'],
            ['name' => 'Power Steering Fluid',   'slug' => 'power-steering-fluid'],

            // HVAC
            ['name' => 'HVAC',                   'slug' => 'hvac'],
            ['name' => 'AC Compressors',         'slug' => 'ac-compressors'],
            ['name' => 'Cabin Air Filters',      'slug' => 'cabin-air-filters'],
            ['name' => 'Radiators',              'slug' => 'radiators'],
            ['name' => 'Thermostats',            'slug' => 'thermostats'],

            // Tyres & Wheels
            ['name' => 'Tyres & Wheels',         'slug' => 'tyres-wheels'],
            ['name' => 'All-Season Tyres',       'slug' => 'all-season-tyres'],
            ['name' => 'Performance Tyres',      'slug' => 'performance-tyres'],
            ['name' => 'Winter Tyres',           'slug' => 'winter-tyres'],
            ['name' => 'Wheel Bearings',         'slug' => 'wheel-bearings'],

            // Transmission & Drivetrain
            ['name' => 'Transmission & Drivetrain', 'slug' => 'transmission-drivetrain'],
            ['name' => 'Clutch Kits',            'slug' => 'clutch-kits'],
            ['name' => 'CV Joints & Axles',      'slug' => 'cv-joints-axles'],
            ['name' => 'Differentials',          'slug' => 'differentials'],

            // Body & Exterior
            ['name' => 'Body & Exterior',        'slug' => 'body-exterior'],
            ['name' => 'Wiper Blades',           'slug' => 'wiper-blades'],
            ['name' => 'Side Mirrors',           'slug' => 'side-mirrors'],

            // Exhaust
            ['name' => 'Exhaust System',         'slug' => 'exhaust-system'],
            ['name' => 'Catalytic Converters',   'slug' => 'catalytic-converters'],
            ['name' => 'Mufflers',               'slug' => 'mufflers'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => $cat['slug']],
                array_merge($cat, ['is_active' => true])
            );
        }
    }
}