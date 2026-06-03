<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            // ── OEM Brands ──────────────────────────────────────────────────
            ['name' => 'Toyota',          'slug' => 'toyota',          'country' => 'Japan',   'tier' => 'OEM'],
            ['name' => 'Honda',           'slug' => 'honda',           'country' => 'Japan',   'tier' => 'OEM'],
            ['name' => 'Nissan',          'slug' => 'nissan',          'country' => 'Japan',   'tier' => 'OEM'],
            ['name' => 'Mazda',           'slug' => 'mazda',           'country' => 'Japan',   'tier' => 'OEM'],
            ['name' => 'Subaru',          'slug' => 'subaru',          'country' => 'Japan',   'tier' => 'OEM'],
            ['name' => 'BMW',             'slug' => 'bmw',             'country' => 'Germany', 'tier' => 'OEM'],
            ['name' => 'Mercedes-Benz',   'slug' => 'mercedes-benz',   'country' => 'Germany', 'tier' => 'OEM'],
            ['name' => 'Volkswagen',      'slug' => 'volkswagen',      'country' => 'Germany', 'tier' => 'OEM'],
            ['name' => 'Audi',            'slug' => 'audi',            'country' => 'Germany', 'tier' => 'OEM'],
            ['name' => 'Ford',            'slug' => 'ford',            'country' => 'USA',     'tier' => 'OEM'],
            ['name' => 'General Motors',  'slug' => 'general-motors',  'country' => 'USA',     'tier' => 'OEM'],
            ['name' => 'Chrysler',        'slug' => 'chrysler',        'country' => 'USA',     'tier' => 'OEM'],
            ['name' => 'Hyundai',         'slug' => 'hyundai',         'country' => 'Korea',   'tier' => 'OEM'],
            ['name' => 'Kia',             'slug' => 'kia',             'country' => 'Korea',   'tier' => 'OEM'],

            // ── Tier 1 Suppliers ─────────────────────────────────────────────
            ['name' => 'Bosch',           'slug' => 'bosch',           'country' => 'Germany', 'tier' => 'Tier1'],
            ['name' => 'Denso',           'slug' => 'denso',           'country' => 'Japan',   'tier' => 'Tier1'],
            ['name' => 'NGK',             'slug' => 'ngk',             'country' => 'Japan',   'tier' => 'Tier1'],
            ['name' => 'Continental',     'slug' => 'continental',     'country' => 'Germany', 'tier' => 'Tier1'],
            ['name' => 'Delphi',          'slug' => 'delphi',          'country' => 'USA',     'tier' => 'Tier1'],
            ['name' => 'Valeo',           'slug' => 'valeo',           'country' => 'France',  'tier' => 'Tier1'],
            ['name' => 'Aisin',           'slug' => 'aisin',           'country' => 'Japan',   'tier' => 'Tier1'],
            ['name' => 'Mahle',           'slug' => 'mahle',           'country' => 'Germany', 'tier' => 'Tier1'],
            ['name' => 'Hella',           'slug' => 'hella',           'country' => 'Germany', 'tier' => 'Tier1'],
            ['name' => 'ACDelco',         'slug' => 'acdelco',         'country' => 'USA',     'tier' => 'Tier1'],
            ['name' => 'Federated',       'slug' => 'federated',       'country' => 'USA',     'tier' => 'Tier1'],
            ['name' => 'Gates',           'slug' => 'gates',           'country' => 'USA',     'tier' => 'Tier1'],
            ['name' => 'SKF',             'slug' => 'skf',             'country' => 'Germany', 'tier' => 'Tier1'],
            ['name' => 'Sachs',           'slug' => 'sachs',           'country' => 'Germany', 'tier' => 'Tier1'],
            ['name' => 'Mann+Hummel',     'slug' => 'mann-hummel',     'country' => 'Germany', 'tier' => 'Tier1'],
            ['name' => 'Exide',           'slug' => 'exide',           'country' => 'USA',     'tier' => 'Tier1'],
            ['name' => 'Optima',          'slug' => 'optima',          'country' => 'USA',     'tier' => 'Tier1'],
            ['name' => 'Bendix',          'slug' => 'bendix',          'country' => 'Australia', 'tier' => 'Tier1'],
            ['name' => 'Ferodo',          'slug' => 'ferodo',          'country' => 'UK',      'tier' => 'Tier1'],
            ['name' => 'TRW',             'slug' => 'trw',             'country' => 'Germany', 'tier' => 'Tier1'],

            // ── Performance Brands ───────────────────────────────────────────
            ['name' => 'Brembo',          'slug' => 'brembo',          'country' => 'Italy',   'tier' => 'Performance'],
            ['name' => 'K&N',             'slug' => 'k-and-n',         'country' => 'USA',     'tier' => 'Performance'],
            ['name' => 'Bilstein',        'slug' => 'bilstein',        'country' => 'Germany', 'tier' => 'Performance'],
            ['name' => 'Eibach',          'slug' => 'eibach',          'country' => 'Germany', 'tier' => 'Performance'],
            ['name' => 'HKS',             'slug' => 'hks',             'country' => 'Japan',   'tier' => 'Performance'],
            ['name' => 'Spoon Sports',    'slug' => 'spoon-sports',    'country' => 'Japan',   'tier' => 'Performance'],
            ['name' => 'Hawk',            'slug' => 'hawk',            'country' => 'USA',     'tier' => 'Performance'],
            ['name' => 'StopTech',        'slug' => 'stoptech',        'country' => 'USA',     'tier' => 'Performance'],
            ['name' => 'Turbosmart',      'slug' => 'turbosmart',      'country' => 'Australia', 'tier' => 'Performance'],
            ['name' => 'Mishimoto',       'slug' => 'mishimoto',       'country' => 'USA',     'tier' => 'Performance'],

            // ── Oils & Fluids ────────────────────────────────────────────────
            ['name' => 'Mobil 1',         'slug' => 'mobil-1',         'country' => 'USA',     'tier' => 'Fluids'],
            ['name' => 'Castrol',         'slug' => 'castrol',         'country' => 'UK',      'tier' => 'Fluids'],
            ['name' => 'Shell Helix',     'slug' => 'shell-helix',     'country' => 'Netherlands', 'tier' => 'Fluids'],
            ['name' => 'Liqui Moly',      'slug' => 'liqui-moly',      'country' => 'Germany', 'tier' => 'Fluids'],
            ['name' => 'Valvoline',       'slug' => 'valvoline',       'country' => 'USA',     'tier' => 'Fluids'],
            ['name' => 'Penrite',         'slug' => 'penrite',         'country' => 'Australia', 'tier' => 'Fluids'],
            ['name' => 'Motul',           'slug' => 'motul',           'country' => 'France',  'tier' => 'Fluids'],
            ['name' => 'Fuchs',           'slug' => 'fuchs',           'country' => 'Germany', 'tier' => 'Fluids'],

            // ── Tyres ────────────────────────────────────────────────────────
            ['name' => 'Michelin',        'slug' => 'michelin',        'country' => 'France',  'tier' => 'Tyres'],
            ['name' => 'Bridgestone',     'slug' => 'bridgestone',     'country' => 'Japan',   'tier' => 'Tyres'],
            ['name' => 'Goodyear',        'slug' => 'goodyear',        'country' => 'USA',     'tier' => 'Tyres'],
            ['name' => 'Pirelli',         'slug' => 'pirelli',         'country' => 'Italy',   'tier' => 'Tyres'],
            ['name' => 'Hankook',         'slug' => 'hankook',         'country' => 'Korea',   'tier' => 'Tyres'],
            ['name' => 'Continental Tyre','slug' => 'continental-tyre','country' => 'Germany', 'tier' => 'Tyres'],
            ['name' => 'Yokohama',        'slug' => 'yokohama',        'country' => 'Japan',   'tier' => 'Tyres'],
            ['name' => 'Falken',          'slug' => 'falken',          'country' => 'Japan',   'tier' => 'Tyres'],
            ['name' => 'Toyo',            'slug' => 'toyo',            'country' => 'Japan',   'tier' => 'Tyres'],
            ['name' => 'Dunlop',          'slug' => 'dunlop',          'country' => 'Japan',   'tier' => 'Tyres'],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(['slug' => $brand['slug']], array_merge($brand, ['is_active' => true]));
        }
    }
}
