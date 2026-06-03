<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CatalogSeeder — generates a production-grade automotive catalog.
 *
 * Scale: ~2,500 products, 1 variant each, 1 inventory record each.
 * Targets: Amazon Auto Parts / AutoZone / RockAuto catalog realism.
 */
class CatalogSeeder extends Seeder
{
    // ── Compatibility pool ───────────────────────────────────────────────────

    private array $compatPool = [
        'Toyota Corolla 2015-2020', 'Toyota Corolla 2010-2014', 'Toyota Corolla 2005-2009',
        'Toyota Camry 2018-2023',   'Toyota Camry 2012-2017',   'Toyota Camry 2007-2011',
        'Toyota RAV4 2019-2023',    'Toyota RAV4 2013-2018',
        'Toyota Hilux 2015-2022',   'Toyota Land Cruiser 2010-2020',
        'Honda Civic 2016-2021',    'Honda Civic 2012-2015',    'Honda Civic 2006-2011',
        'Honda Accord 2018-2023',   'Honda Accord 2013-2017',
        'Honda CR-V 2017-2022',     'Honda HR-V 2015-2021',
        'Nissan Almera 2013-2019',  'Nissan X-Trail 2014-2021', 'Nissan Navara 2015-2021',
        'Nissan Sentra 2013-2019',  'Nissan Murano 2015-2020',
        'Mazda 3 2014-2019',        'Mazda 3 2019-2023',        'Mazda CX-5 2017-2022',
        'Subaru Forester 2014-2019','Subaru Outback 2015-2020',
        'BMW 3 Series 2012-2018',   'BMW 3 Series 2019-2023',   'BMW 5 Series 2010-2016',
        'BMW X3 2011-2017',         'BMW X5 2014-2020',
        'Mercedes-Benz C-Class 2015-2021', 'Mercedes-Benz E-Class 2014-2020',
        'Mercedes-Benz GLC 2016-2022',
        'Volkswagen Golf 2013-2020','Volkswagen Passat 2015-2021',
        'Audi A4 2016-2021',        'Audi A6 2011-2018',        'Audi Q5 2017-2022',
        'Ford Ranger 2012-2021',    'Ford Focus 2011-2018',     'Ford F-150 2015-2020',
        'Ford Mustang 2015-2021',   'Ford Explorer 2011-2019',
        'Hyundai Tucson 2016-2021', 'Hyundai Elantra 2014-2020','Hyundai i30 2012-2018',
        'Kia Sportage 2017-2022',   'Kia Cerato 2014-2019',
        'Chevrolet Malibu 2013-2019','Chevrolet Silverado 2014-2019',
    ];

    // ── Made-in weighted distribution ────────────────────────────────────────

    private array $madeInPool = [
        'Japan', 'Japan', 'Japan', 'Japan', 'Japan',          // 25%
        'Germany', 'Germany', 'Germany', 'Germany',            // 20%
        'USA', 'USA', 'USA',                                   // 15%
        'France', 'France',                                    // 10%
        'Italy', 'Italy',                                      // 10%
        'Korea', 'Korea',                                      // 10%
        'China',                                               // 5%
        'Thailand',                                            // 5%
    ];

    // ── Category → Brand affinity + price range + name templates ─────────────

    private array $categoryConfig = [
        'spark-plugs' => [
            'brands'    => ['ngk', 'bosch', 'denso', 'toyota', 'honda', 'acdelco'],
            'price'     => [4, 28],
            'templates' => [
                '{brand} Iridium Spark Plug {sku} Standard Replacement',
                '{brand} Platinum Spark Plug {sku} OEM Grade',
                '{brand} Double Iridium Spark Plug {sku}',
                '{brand} Laser Iridium Spark Plug {sku} for {compat}',
                '{brand} Racing Spark Plug {sku} High Performance',
            ],
            'descs' => [
                'OEM-grade iridium spark plug for reliable ignition and long service life. Compatible with most Asian and European vehicles.',
                'Platinum-tipped spark plug with a focused ignition point for improved combustion efficiency. Direct OEM replacement.',
                'Double iridium construction for maximum durability and consistent spark across all RPM ranges.',
            ],
        ],
        'air-filters' => [
            'brands'    => ['k-and-n', 'mann-hummel', 'bosch', 'mahle', 'denso', 'aisin', 'toyota'],
            'price'     => [8, 55],
            'templates' => [
                '{brand} High-Flow Air Filter {sku} Drop-In Replacement',
                '{brand} Panel Air Filter {sku} OEM Spec',
                '{brand} Performance Cold Air Filter {sku}',
                '{brand} Engine Air Filter {sku} for {compat}',
                '{brand} Washable Reusable Air Filter {sku}',
            ],
            'descs' => [
                'High-flow air filter designed to maximise airflow while filtering contaminants. Easy drop-in fitment.',
                'OEM-specification panel air filter for reliable engine protection. Replaces the original filter with perfect fitment.',
                'Washable and reusable air filter that improves throttle response. Can be cleaned and re-oiled for extended service life.',
            ],
        ],
        'oil-filters' => [
            'brands'    => ['bosch', 'mann-hummel', 'mahle', 'denso', 'acdelco', 'toyota', 'honda'],
            'price'     => [6, 22],
            'templates' => [
                '{brand} Oil Filter {sku} Extended Life',
                '{brand} Premium Oil Filter {sku} Spin-On',
                '{brand} Oil Filter {sku} OEM Replacement',
                '{brand} Distance Plus Oil Filter {sku}',
            ],
            'descs' => [
                'Extended-life oil filter with anti-drain-back valve. Filters particles down to 15 microns for superior engine protection.',
                'Spin-on oil filter with a high-efficiency filter media. Maintains consistent oil flow pressure throughout the service interval.',
            ],
        ],
        'timing-belts-chains' => [
            'brands'    => ['gates', 'continental', 'bosch', 'aisin', 'toyota', 'honda'],
            'price'     => [18, 120],
            'templates' => [
                '{brand} Timing Belt Kit {sku} with Water Pump',
                '{brand} Timing Chain Kit {sku} Complete Set',
                '{brand} Timing Belt {sku} OEM Replacement',
                '{brand} Cam Belt Kit {sku} for {compat}',
            ],
            'descs' => [
                'Complete timing belt kit including timing belt, tensioner, idler pulleys, and water pump. Engineered to OEM tolerances.',
                'Heavy-duty timing chain kit for extended durability. Includes all guides, tensioners, and gaskets for a complete service.',
            ],
        ],
        'engine-gaskets' => [
            'brands'    => ['mahle', 'sachs', 'bosch', 'toyota', 'honda', 'nissan'],
            'price'     => [12, 90],
            'templates' => [
                '{brand} Head Gasket {sku} MLS Steel',
                '{brand} Valve Cover Gasket Set {sku}',
                '{brand} Full Engine Gasket Kit {sku}',
                '{brand} Intake Manifold Gasket {sku} for {compat}',
            ],
            'descs' => [
                'Multi-layer steel head gasket for superior sealing under high heat and pressure. Perfect OEM replacement.',
                'Complete valve cover gasket set with all mounting grommets. Stops oil leaks and restores engine cleanliness.',
            ],
        ],
        'fuel-pumps' => [
            'brands'    => ['bosch', 'delphi', 'denso', 'continental', 'aisin'],
            'price'     => [40, 220],
            'templates' => [
                '{brand} In-Tank Fuel Pump {sku} OEM Replacement',
                '{brand} Electric Fuel Pump Module {sku}',
                '{brand} High-Pressure Fuel Pump {sku} for {compat}',
                '{brand} Fuel Pump Assembly {sku} Complete Module',
            ],
            'descs' => [
                'Direct-fit in-tank fuel pump engineered to meet or exceed OEM specifications. Ensures consistent fuel pressure and flow.',
                'Complete fuel pump module with sender unit and strainer. Provides reliable delivery pressure across all operating conditions.',
            ],
        ],
        'fuel-injectors' => [
            'brands'    => ['bosch', 'denso', 'delphi', 'continental'],
            'price'     => [30, 180],
            'templates' => [
                '{brand} Fuel Injector {sku} Remanufactured',
                '{brand} DEKA Fuel Injector {sku} OEM Grade',
                '{brand} High-Flow Fuel Injector {sku} 440cc',
                '{brand} Fuel Injector Set {sku} for {compat}',
            ],
            'descs' => [
                'Remanufactured fuel injector tested to meet new OEM performance standards. Includes new O-rings and filter basket.',
                'High-flow performance fuel injector rated at 440cc/min. Ideal for modified engines requiring increased fuel delivery.',
            ],
        ],
        'fuel-filters' => [
            'brands'    => ['bosch', 'mann-hummel', 'mahle', 'denso', 'acdelco'],
            'price'     => [8, 35],
            'templates' => [
                '{brand} Fuel Filter {sku} Inline Canister',
                '{brand} Fuel Filter {sku} OEM Spec',
                '{brand} Fuel Filter {sku} for {compat}',
            ],
            'descs' => [
                'Inline fuel filter removes particulates and water contamination before they reach the injectors. Easy push-fit installation.',
                'OEM-specification fuel filter maintaining the correct flow rate and particle filtration rating. Direct replacement.',
            ],
        ],
        'brake-pads' => [
            'brands'    => ['brembo', 'bosch', 'ferodo', 'hawk', 'stoptech', 'akebono', 'bendix', 'trw'],
            'price'     => [22, 130],
            'templates' => [
                '{brand} Brake Pad Set {sku} Front Axle',
                '{brand} Brake Pad Set {sku} Rear Axle',
                '{brand} Ceramic Brake Pads {sku} Low-Dust',
                '{brand} Sport Brake Pads {sku} High-Performance',
                '{brand} OE Replacement Brake Pads {sku} for {compat}',
                '{brand} Ultimate Street Pads {sku} Zero Dust',
            ],
            'descs' => [
                'Low-dust ceramic brake pads with precision-shim noise suppression. OEM brake feel with significantly reduced wheel dust.',
                'High-performance brake pads with a performance carbon-fibre compound for short stopping distances under heavy braking.',
                'Street-performance brake pads providing excellent cold bite and modulation. A direct OEM upgrade for daily drivers.',
            ],
        ],
        'brake-rotors' => [
            'brands'    => ['brembo', 'stoptech', 'trw', 'bosch', 'ferodo'],
            'price'     => [45, 220],
            'templates' => [
                '{brand} Brake Disc Rotor {sku} Vented Front {dim}mm',
                '{brand} Slotted Brake Rotor {sku} Front {dim}mm',
                '{brand} Drilled & Slotted Rotor {sku} {dim}mm',
                '{brand} XTRA Rotor {sku} Front Axle {dim}mm',
                '{brand} Brake Disc {sku} Rear Axle {dim}mm',
            ],
            'descs' => [
                'Vented brake disc rotor machined from G3000 premium grey iron. OEM replacement with precision-balanced design for smooth braking.',
                'Slotted and cross-drilled performance rotor for improved heat dissipation. Eliminates brake fade under heavy use.',
                'Geomet-coated rotor prevents surface rust without affecting braking performance. Quiet and consistent bite from first use.',
            ],
        ],
        'brake-calipers' => [
            'brands'    => ['brembo', 'bosch', 'trw', 'ferodo', 'acdelco'],
            'price'     => [55, 280],
            'templates' => [
                '{brand} Brake Caliper {sku} Remanufactured Front Left',
                '{brand} Brake Caliper {sku} Front Right Loaded',
                '{brand} Rear Brake Caliper {sku} OEM Replacement',
                '{brand} 4-Piston Caliper {sku} Upgrade Kit',
            ],
            'descs' => [
                'Remanufactured brake caliper restored to OEM tolerances. Fully loaded with new pads, hardware, and bleeder screws.',
                'High-performance four-piston brake caliper providing superior clamping force and pad life for upgraded brake systems.',
            ],
        ],
        'brake-fluid' => [
            'brands'    => ['castrol', 'motul', 'mobil-1', 'liqui-moly', 'penrite'],
            'price'     => [8, 30],
            'templates' => [
                '{brand} Brake Fluid DOT 4 500ml',
                '{brand} Brake Fluid DOT 3 500ml',
                '{brand} Racing Brake Fluid DOT 5.1 500ml',
                '{brand} High-Performance Brake Fluid DOT 4',
            ],
            'descs' => [
                'DOT 4 brake fluid with a high dry boiling point of 270°C. Provides reliable pedal feel and prevents vapour lock.',
                'DOT 5.1 fully synthetic racing brake fluid rated for extreme temperatures. For motorsport and track-day use.',
            ],
        ],
        'shock-absorbers' => [
            'brands'    => ['bilstein', 'sachs', 'bosch', 'eibach', 'trw', 'koni'],
            'price'     => [40, 250],
            'templates' => [
                '{brand} Shock Absorber {sku} Front Left OEM',
                '{brand} Shock Absorber {sku} Rear Right OEM',
                '{brand} Sport Shock Absorber {sku} Lowered Spring',
                '{brand} Heavy-Duty Shock {sku} for {compat}',
                '{brand} Monotube Shock Absorber {sku}',
            ],
            'descs' => [
                'OEM-replacement monotube shock absorber for precise vehicle control and a comfortable ride. Fully assembled and ready to install.',
                'Sport-tuned shock absorber matched to lowered spring kits. Provides firm handling response with acceptable daily-drive comfort.',
                'Heavy-duty gas-charged shock absorber ideal for loaded vehicles and off-road use. Extended service life with steel body.',
            ],
        ],
        'control-arms' => [
            'brands'    => ['trw', 'sachs', 'skf', 'continental', 'bosch'],
            'price'     => [45, 180],
            'templates' => [
                '{brand} Control Arm {sku} Front Lower Left',
                '{brand} Control Arm {sku} Front Upper Right',
                '{brand} Wishbone {sku} Complete with Ball Joint',
                '{brand} Control Arm Kit {sku} for {compat}',
            ],
            'descs' => [
                'Complete front lower control arm with pre-installed ball joint and bushing. Ready-to-fit direct OEM replacement.',
                'Heavy-duty forged steel upper control arm with improved geometry for lifted applications.',
            ],
        ],
        'struts' => [
            'brands'    => ['bilstein', 'sachs', 'bosch', 'eibach', 'trw'],
            'price'     => [60, 300],
            'templates' => [
                '{brand} Strut Assembly {sku} Front Left Complete',
                '{brand} Strut Assembly {sku} Front Right Quick-Strut',
                '{brand} Coilover Strut {sku} Sport Adjustable',
                '{brand} Strut Mount {sku} Upper Bearing Kit',
            ],
            'descs' => [
                'Complete strut assembly pre-assembled with spring, mount, and bearing. No spring compressor needed for installation.',
                'Adjustable sport coilover strut allowing ride-height adjustment of 30–50mm. Retains OEM comfort while improving handling.',
            ],
        ],
        'steering-racks' => [
            'brands'    => ['trw', 'sachs', 'bosch', 'continental', 'acdelco'],
            'price'     => [80, 350],
            'templates' => [
                '{brand} Steering Rack {sku} Remanufactured Power Assist',
                '{brand} Rack and Pinion {sku} Complete Assembly',
                '{brand} Electric Power Steering Rack {sku}',
                '{brand} Steering Rack End {sku} OEM Replacement',
            ],
            'descs' => [
                'Remanufactured power-assisted steering rack tested to OEM flow-rate and pressure specifications. Includes all boots and tie-rod ends.',
                'Complete rack-and-pinion assembly for electric power steering systems. Plug-and-play replacement with OEM connector.',
            ],
        ],
        'batteries' => [
            'brands'    => ['exide', 'optima', 'bosch', 'acdelco', 'hella'],
            'price'     => [80, 280],
            'templates' => [
                '{brand} Car Battery {sku} 60Ah 550CCA',
                '{brand} Car Battery {sku} 70Ah 640CCA',
                '{brand} Car Battery {sku} 80Ah 720CCA AGM',
                '{brand} RedTop Battery {sku} 12V High-Cranking',
                '{brand} AGM Stop-Start Battery {sku} 95Ah',
                '{brand} Deep Cycle Battery {sku} EFB Technology',
            ],
            'descs' => [
                '60Ah lead-acid battery with 550 cold cranking amps (CCA). Reliable starting power in all weather conditions.',
                'AGM (Absorbent Glass Mat) stop-start battery for vehicles with Start-Stop and regenerative braking technology.',
                'High-performance AGM battery providing superior power delivery for vehicles with heavy electrical loads.',
            ],
        ],
        'alternators' => [
            'brands'    => ['bosch', 'denso', 'valeo', 'acdelco', 'hella'],
            'price'     => [90, 350],
            'templates' => [
                '{brand} Alternator {sku} 120A Remanufactured',
                '{brand} Alternator {sku} 140A OEM Replacement',
                '{brand} Alternator {sku} 180A High-Output for {compat}',
            ],
            'descs' => [
                'Remanufactured 120A alternator tested to OEM output specifications. Includes new rectifier, regulator, and brushes.',
                'High-output 180A alternator for vehicles with upgraded audio systems or auxiliary lighting requirements.',
            ],
        ],
        'starters' => [
            'brands'    => ['bosch', 'denso', 'valeo', 'acdelco'],
            'price'     => [65, 220],
            'templates' => [
                '{brand} Starter Motor {sku} Remanufactured 1.4kW',
                '{brand} Starter Motor {sku} OEM Replacement',
                '{brand} Starter Motor {sku} for {compat}',
            ],
            'descs' => [
                'Remanufactured starter motor to OEM torque and current specifications. Direct-fit for a quick, hassle-free installation.',
                'New-production starter motor with a copper-wound armature and sealed solenoid for reliable start cycles.',
            ],
        ],
        'sensors-switches' => [
            'brands'    => ['bosch', 'denso', 'continental', 'delphi', 'ngk'],
            'price'     => [12, 95],
            'templates' => [
                '{brand} O2 Oxygen Sensor {sku} Upstream',
                '{brand} O2 Oxygen Sensor {sku} Downstream',
                '{brand} Mass Air Flow Sensor {sku} MAF',
                '{brand} Crankshaft Position Sensor {sku}',
                '{brand} Camshaft Position Sensor {sku}',
                '{brand} Coolant Temperature Sensor {sku}',
                '{brand} Throttle Position Sensor {sku}',
                '{brand} ABS Wheel Speed Sensor {sku} Front',
                '{brand} MAP Sensor {sku} Manifold Pressure',
                '{brand} Knock Sensor {sku} for {compat}',
            ],
            'descs' => [
                'Wideband upstream oxygen sensor with titanium element for accurate air-fuel ratio monitoring. OEM connector fits direct.',
                'Mass air flow sensor with a heated film element for precise measurement of intake air mass. Factory calibrated.',
                'OEM-grade crankshaft position sensor for accurate timing signal. Resolves misfire and no-start conditions.',
            ],
        ],
        'ignition-coils' => [
            'brands'    => ['bosch', 'denso', 'ngk', 'delphi', 'hella'],
            'price'     => [18, 85],
            'templates' => [
                '{brand} Ignition Coil {sku} COP Direct Replacement',
                '{brand} Ignition Coil Pack {sku} for {compat}',
                '{brand} Individual Ignition Coil {sku}',
            ],
            'descs' => [
                'Coil-on-plug ignition coil with enhanced winding insulation for consistent spark delivery. Direct OEM replacement.',
                'Complete ignition coil pack tested to high-voltage output specifications. Resolves misfires and rough idle.',
            ],
        ],
        'headlights-bulbs' => [
            'brands'    => ['bosch', 'hella', 'philips', 'osram', 'valeo'],
            'price'     => [8, 180],
            'templates' => [
                '{brand} H4 Halogen Bulb {sku} 60/55W Pair',
                '{brand} H7 Halogen Bulb {sku} 55W Pair',
                '{brand} H11 Headlight Bulb {sku} Performance',
                '{brand} LED Headlight Bulb {sku} H7 6000K',
                '{brand} HID Xenon Bulb {sku} D2S 4300K',
                '{brand} Headlight Assembly {sku} Left for {compat}',
                '{brand} Fog Light Bulb {sku} H8 35W',
            ],
            'descs' => [
                'H4 halogen bulb producing up to 30% more light than standard bulbs. ECE-approved for road legal use in most markets.',
                'H7 performance halogen with improved colour temperature of 3700K. Brighter white light for improved road visibility.',
                'LED headlight conversion bulb in 6000K cool white. Plug-and-play fitment with no modification required.',
            ],
        ],
        'engine-oil' => [
            'brands'    => ['mobil-1', 'castrol', 'shell-helix', 'liqui-moly', 'valvoline', 'motul', 'penrite', 'fuchs'],
            'price'     => [22, 130],
            'templates' => [
                '{brand} 5W-30 Fully Synthetic Engine Oil 4L',
                '{brand} 5W-40 Fully Synthetic Engine Oil 5L',
                '{brand} 10W-40 Semi-Synthetic Engine Oil 4L',
                '{brand} 0W-20 Fully Synthetic Engine Oil 4L',
                '{brand} 0W-40 Full Synthetic Engine Oil 5L',
                '{brand} 5W-30 Longlife Engine Oil 1L',
                '{brand} Racing 15W-50 Full Synthetic 4L',
            ],
            'descs' => [
                'Fully synthetic 5W-30 engine oil meeting ACEA A3/B4, API SN Plus standards. Extended drain interval of up to 15,000km.',
                '0W-20 ultra-low viscosity synthetic oil for improved fuel economy in modern downsized engines. Approved for Toyota, Honda.',
                'High-performance racing oil with a 15W-50 viscosity grade. Designed for naturally aspirated and forced-induction engines.',
            ],
        ],
        'transmission-fluid' => [
            'brands'    => ['castrol', 'mobil-1', 'liqui-moly', 'valvoline', 'shell-helix'],
            'price'     => [18, 75],
            'templates' => [
                '{brand} ATF Automatic Transmission Fluid 1L',
                '{brand} CVT Transmission Fluid 1L',
                '{brand} Manual Gearbox Oil GL-4 75W-85 1L',
                '{brand} ATF SP IV Fluid for {compat}',
                '{brand} DSG/DCT Fluid 1L',
            ],
            'descs' => [
                'Full-synthetic ATF fluid compatible with Toyota WS, Honda DW-1, and Aisin specifications. Reduces shift harshness.',
                'CVT fluid engineered for continuously variable transmissions. Protects steel push-belt components and maintains shift ratios.',
            ],
        ],
        'coolant-antifreeze' => [
            'brands'    => ['castrol', 'mobil-1', 'prestone', 'penrite', 'liqui-moly'],
            'price'     => [12, 50],
            'templates' => [
                '{brand} Long Life Coolant Premixed 4L',
                '{brand} Antifreeze Concentrate G12 1.5L',
                '{brand} OAT Coolant Premixed Red 5L',
                '{brand} Blue Antifreeze G11 Premixed 4L',
            ],
            'descs' => [
                'Premixed OAT coolant providing freeze protection to -37°C and boil-over protection to +129°C. 5-year service life.',
                'G12+ antifreeze concentrate with organic acid technology (OAT). Compatible with aluminium and alloy engine components.',
            ],
        ],
        'power-steering-fluid' => [
            'brands'    => ['castrol', 'penrite', 'prestone', 'liqui-moly', 'valvoline'],
            'price'     => [8, 28],
            'templates' => [
                '{brand} Power Steering Fluid 500ml',
                '{brand} Power Steering Fluid ATF-Based 1L',
                '{brand} Synthetic Power Steering Fluid 500ml',
            ],
            'descs' => [
                'Universal power steering fluid compatible with most Japanese and European vehicles. Stops squeaks and improves steering feel.',
                'ATF-based power steering fluid for Subaru, Mazda, and Mitsubishi power steering systems.',
            ],
        ],
        'ac-compressors' => [
            'brands'    => ['denso', 'valeo', 'sanden', 'bosch', 'aisin'],
            'price'     => [120, 450],
            'templates' => [
                '{brand} AC Compressor {sku} Remanufactured with Clutch',
                '{brand} AC Compressor {sku} New OEM Replacement',
                '{brand} AC Compressor Clutch {sku} Assembly',
                '{brand} AC Compressor {sku} for {compat}',
            ],
            'descs' => [
                'Remanufactured A/C compressor rebuilt to new specifications. Pre-charged with correct PAG oil. Includes new clutch plate and pulley.',
                'New OEM-specification air conditioning compressor with variable displacement technology for improved fuel efficiency.',
            ],
        ],
        'cabin-air-filters' => [
            'brands'    => ['bosch', 'mann-hummel', 'mahle', 'denso', 'valeo', 'k-and-n'],
            'price'     => [12, 45],
            'templates' => [
                '{brand} Cabin Air Filter {sku} Pollen Filter',
                '{brand} Cabin Air Filter {sku} Activated Carbon',
                '{brand} Cabin Filter {sku} for {compat}',
                '{brand} Hepa Cabin Air Filter {sku} Anti-Allergy',
            ],
            'descs' => [
                'Activated carbon cabin air filter removes pollen, dust, bacteria, and odours. Significantly improves in-cabin air quality.',
                'HEPA-rated cabin filter capturing 99.97% of particulates down to 0.3 microns. Ideal for allergy sufferers.',
            ],
        ],
        'radiators' => [
            'brands'    => ['denso', 'valeo', 'mishimoto', 'aisin', 'hella'],
            'price'     => [80, 350],
            'templates' => [
                '{brand} Radiator {sku} All-Aluminium Core',
                '{brand} Radiator {sku} OEM Replacement',
                '{brand} Performance Radiator {sku} 2-Row for {compat}',
                '{brand} Radiator {sku} Plastic Tank Aluminium Core',
            ],
            'descs' => [
                'All-aluminium radiator core with a brazed fin design for maximum heat rejection. Direct OEM replacement for plastic-tank failures.',
                'Performance dual-row aluminium radiator with 50% more cooling capacity than stock. Ideal for turbo and high-horsepower builds.',
            ],
        ],
        'thermostats' => [
            'brands'    => ['gates', 'continental', 'bosch', 'aisin', 'denso'],
            'price'     => [14, 55],
            'templates' => [
                '{brand} Engine Thermostat {sku} 82°C with Gasket',
                '{brand} Engine Thermostat {sku} 88°C OEM',
                '{brand} Thermostat Housing {sku} Complete Assembly',
            ],
            'descs' => [
                'OEM-replacement thermostat opening at 82°C for optimal engine warm-up and fuel economy. Includes new housing gasket.',
                'Complete thermostat housing assembly with integrated thermostat. Single-part replacement eliminates leak-prone separate housing.',
            ],
        ],
        'all-season-tyres' => [
            'brands'    => ['michelin', 'bridgestone', 'goodyear', 'continental-tyre', 'hankook', 'dunlop', 'yokohama'],
            'price'     => [65, 320],
            'templates' => [
                '{brand} Primacy 4 {size} All-Season Tyre',
                '{brand} Turanza {size} Touring Tyre',
                '{brand} Assurance {size} All-Season Tyre',
                '{brand} PremiumContact {size} Comfort Tyre',
                '{brand} Kinergy PT {size} Touring Tyre',
                '{brand} Sport Maxx {size} Ultra-High Performance',
                '{brand} BluEarth-GT {size} Eco Tyre',
            ],
            'descs' => [
                'Premium all-season touring tyre delivering outstanding wet and dry grip, low road noise, and extended tread life.',
                'Eco-performance tyre with optimised rolling resistance for improved fuel economy. A+ rated in EU tyre label.',
            ],
        ],
        'performance-tyres' => [
            'brands'    => ['michelin', 'pirelli', 'bridgestone', 'goodyear', 'toyo', 'yokohama', 'falken'],
            'price'     => [90, 450],
            'templates' => [
                '{brand} Pilot Sport 5 {size} Ultra High Performance',
                '{brand} P Zero {size} Sports Car Tyre',
                '{brand} Potenza Sport {size} High Performance',
                '{brand} Eagle F1 Asymmetric 5 {size}',
                '{brand} Proxes Sport {size} Maximum Grip',
                '{brand} ADVAN Sport V105 {size}',
                '{brand} Azenis FK510 {size} High-Performance',
            ],
            'descs' => [
                'Ultra-high-performance tyre delivering exceptional dry grip and precise steering response at the limit.',
                'Sports car tyre with an asymmetric tread for maximum cornering stability and short wet braking distance.',
            ],
        ],
        'winter-tyres' => [
            'brands'    => ['michelin', 'bridgestone', 'goodyear', 'pirelli', 'continental-tyre', 'dunlop'],
            'price'     => [75, 380],
            'templates' => [
                '{brand} Alpin 6 {size} Winter Tyre',
                '{brand} Blizzak LM005 {size} Winter',
                '{brand} UltraGrip Performance+ {size}',
                '{brand} Winter Sottozero 3 {size}',
                '{brand} WinterContact TS 860 {size}',
                '{brand} Winter Sport 5 {size}',
            ],
            'descs' => [
                'Premium winter tyre with a silica-enhanced compound for grip below 7°C. High-density siping for ice traction.',
                'Winter performance tyre combining snow and ice traction with low road noise for comfortable winter driving.',
            ],
        ],
        'wheel-bearings' => [
            'brands'    => ['skf', 'sachs', 'bosch', 'trw', 'continental'],
            'price'     => [35, 160],
            'templates' => [
                '{brand} Wheel Bearing {sku} Front Hub Assembly',
                '{brand} Wheel Bearing {sku} Rear Axle',
                '{brand} Wheel Hub Bearing {sku} with ABS Ring',
                '{brand} Wheel Bearing Kit {sku} for {compat}',
            ],
            'descs' => [
                'Complete front hub bearing assembly with integrated ABS sensor ring. Ready-to-install press-fit or bolt-on fitment.',
                'Sealed wheel bearing with optimised preload for smooth running and long service life. No re-greasing required.',
            ],
        ],
        'clutch-kits' => [
            'brands'    => ['sachs', 'valeo', 'bosch', 'exedy', 'aisin'],
            'price'     => [80, 450],
            'templates' => [
                '{brand} Clutch Kit {sku} 3-Piece OEM',
                '{brand} Sport Clutch Kit {sku} Heavy Duty',
                '{brand} Clutch Kit {sku} with Dual Mass Flywheel',
                '{brand} Clutch Kit {sku} for {compat}',
            ],
            'descs' => [
                'Complete 3-piece clutch kit including pressure plate, friction disc, and release bearing. Engineered to OEM specifications.',
                'Heavy-duty clutch kit with a sprung-hub disc for increased torque capacity. Suitable for modified or high-torque engines.',
            ],
        ],
        'cv-joints-axles' => [
            'brands'    => ['trw', 'sachs', 'skf', 'continental', 'acdelco'],
            'price'     => [55, 220],
            'templates' => [
                '{brand} CV Axle Shaft {sku} Front Left Remanufactured',
                '{brand} CV Axle Shaft {sku} Front Right OEM',
                '{brand} CV Boot Kit {sku} Inner & Outer',
                '{brand} CV Joint {sku} Complete with Grease',
            ],
            'descs' => [
                'Remanufactured CV axle shaft cleaned, re-greased, and fitted with new CV joints. Eliminates clicking and vibration on turns.',
                'CV axle boot kit including CV joint, boot, clamps, and grease sachet. Allows DIY joint rebuild without full axle replacement.',
            ],
        ],
        'differentials' => [
            'brands'    => ['aisin', 'trw', 'sachs', 'skf'],
            'price'     => [180, 800],
            'templates' => [
                '{brand} Rear Differential {sku} Remanufactured',
                '{brand} LSD Limited-Slip Differential {sku}',
                '{brand} Differential Bearing Kit {sku}',
            ],
            'descs' => [
                'Remanufactured rear differential restored to OEM specification. Tested for noise, vibration, and harshness before dispatch.',
                'Torsen-type limited-slip differential providing automatic torque biasing for improved traction in wet and off-road conditions.',
            ],
        ],
        'wiper-blades' => [
            'brands'    => ['bosch', 'valeo', 'continental', 'denso', 'hella'],
            'price'     => [8, 42],
            'templates' => [
                '{brand} Wiper Blade {sku} 600mm Aerovantage',
                '{brand} Twin Wiper Blade Set {sku} for {compat}',
                '{brand} Rear Wiper Blade {sku} 350mm',
                '{brand} Flat Beam Wiper Blade {sku} 650mm',
                '{brand} All-Season Wiper Blade {sku}',
            ],
            'descs' => [
                'Flat-blade wiper with aerodynamic design for improved contact pressure at highway speeds. Reduces streaking and noise.',
                'OEM-specification twin wiper blade set for reliable all-weather performance. Includes all adapters for universal fitment.',
            ],
        ],
        'side-mirrors' => [
            'brands'    => ['hella', 'valeo', 'bosch', 'denso'],
            'price'     => [35, 180],
            'templates' => [
                '{brand} Side Mirror {sku} Powered Left OEM',
                '{brand} Side Mirror {sku} Powered Right with Indicator',
                '{brand} Wing Mirror Glass {sku} Convex Replacement',
                '{brand} Door Mirror Assembly {sku} for {compat}',
            ],
            'descs' => [
                'Power-folding side mirror assembly with integrated LED indicator. Plug-and-play OEM replacement for the specified vehicle.',
                'Replacement convex wing mirror glass with adhesive backing. Restores visibility without replacing the complete housing.',
            ],
        ],
        'catalytic-converters' => [
            'brands'    => ['bosch', 'continental', 'delphi', 'mahle'],
            'price'     => [120, 600],
            'templates' => [
                '{brand} Catalytic Converter {sku} Euro 5 Compliant',
                '{brand} Catalytic Converter {sku} Direct-Fit for {compat}',
                '{brand} Three-Way Catalyst {sku} Pre-Cat',
                '{brand} Diesel Particulate Filter {sku} DPF',
            ],
            'descs' => [
                'Euro 5/6 compliant catalytic converter with a high-cell-density substrate for rapid light-off and low back-pressure.',
                'Direct-fit catalytic converter with pre-welded O2 sensor bungs. No cutting or fabrication required.',
            ],
        ],
        'mufflers' => [
            'brands'    => ['bosch', 'magnaflow', 'flowmaster', 'hks', 'akrapovic'],
            'price'     => [45, 350],
            'templates' => [
                '{brand} Rear Muffler {sku} Stainless Steel OEM',
                '{brand} Performance Muffler {sku} 2.5" Inlet',
                '{brand} Sport Exhaust Muffler {sku} Deep Tone',
                '{brand} Slip-On Muffler {sku} Titanium for {compat}',
            ],
            'descs' => [
                'T304 stainless steel rear muffler providing OEM-equivalent sound levels and durability. Resists corrosion and discolouration.',
                'Performance muffler with a free-flow internal design for reduced back-pressure and a deep exhaust note.',
            ],
        ],
    ];

    // ── Tyre sizes pool ──────────────────────────────────────────────────────

    private array $tyreSizes = [
        '185/65R15', '195/65R15', '205/55R16', '215/55R17', '225/45R17',
        '225/50R17', '235/45R18', '245/40R18', '255/35R19', '265/30R19',
        '195/60R15', '205/60R16', '215/60R16', '225/60R17', '235/60R18',
        '205/65R16', '215/65R16', '225/65R17', '235/65R17', '245/65R17',
        '175/65R14', '185/60R15', '195/55R16', '205/50R17', '225/40R18',
    ];

    // ── Rotor sizes pool ─────────────────────────────────────────────────────

    private array $rotorSizes = ['280', '296', '312', '320', '330', '340', '345', '355'];

    public function run(): void
    {
        // Load all brands and categories into memory (keyed by slug)
        $brands     = Brand::all()->keyBy('slug');
        $categories = Category::all()->keyBy('slug');

        $targetProductsPerCategory = 50; // ~50 × 50 categories = 2,500 products
        $skuCounter = 10000;

        DB::transaction(function () use ($brands, $categories, &$skuCounter, $targetProductsPerCategory) {
            foreach ($this->categoryConfig as $categorySlug => $config) {
                $category = $categories->get($categorySlug);
                if (!$category) {
                    continue;
                }

                $brandSlugs = $config['brands'];
                $priceMin   = $config['price'][0];
                $priceMax   = $config['price'][1];
                $templates  = $config['templates'];
                $descs      = $config['descs'];

                $usedNames = [];

                for ($i = 0; $i < $targetProductsPerCategory; $i++) {
                    $skuCounter++;

                    $brandSlug = $brandSlugs[array_rand($brandSlugs)];
                    $brand     = $brands->get($brandSlug);
                    if (!$brand) continue;

                    $template = $templates[array_rand($templates)];
                    $sku      = $brand->slug . '-' . strtoupper(base_convert($skuCounter, 10, 36));
                    $madeIn   = $this->madeInPool[array_rand($this->madeInPool)];

                    // Build product name from template
                    $name = $this->buildName($template, $brand->name, $sku, $categorySlug);

                    // Deduplicate names within this category run
                    $suffix = 0;
                    $baseName = $name;
                    while (in_array($name, $usedNames)) {
                        $suffix++;
                        $name = $baseName . ' v' . $suffix;
                    }
                    $usedNames[] = $name;

                    $slug = Str::slug($name);

                    // Generate realistic price
                    $basePrice = round($this->randomPrice($priceMin, $priceMax), 2);

                    // Random compatibility (1–4 vehicles, 60% of products have it)
                    $compat = null;
                    if (rand(1, 10) <= 6) {
                        $pool = array_rand(array_flip($this->compatPool), min(rand(2, 4), count($this->compatPool)));
                        $compat = is_array($pool) ? $pool : [$pool];
                    }

                    // Description
                    $description = $descs[array_rand($descs)];

                    // Insert product (skip if slug already exists)
                    $existing = Product::where('slug', $slug)->first();
                    if ($existing) {
                        $slug = $slug . '-' . $skuCounter;
                    }

                    $product = Product::create([
                        'name'          => $name,
                        'slug'          => $slug,
                        'description'   => $description,
                        'category_id'   => $category->id,
                        'brand_id'      => $brand->id,
                        'made_in'       => $madeIn,
                        'compatibility' => $compat,
                        'base_price'    => $basePrice,
                        'images'        => null,
                        'is_active'     => true,
                    ]);

                    // Variant SKU: BRAND-CATEGORY-NUMBER
                    $variantSku = strtoupper(
                        substr($brand->slug, 0, 4) . '-' .
                        substr($categorySlug, 0, 6) . '-' .
                        $skuCounter
                    );

                    $variant = ProductVariant::create([
                        'product_id'     => $product->id,
                        'sku'            => $variantSku,
                        'name'           => 'Standard',
                        'price_override' => null,
                        'is_active'      => true,
                    ]);

                    Inventory::create([
                        'variant_id'         => $variant->id,
                        'stock_quantity'     => rand(0, 200),
                        'reserved_quantity'  => rand(0, 15),
                        'low_stock_threshold'=> rand(3, 10),
                    ]);
                }
            }
        });
    }

    private function buildName(string $template, string $brandName, string $sku, string $categorySlug): string
    {
        $compat = $this->compatPool[array_rand($this->compatPool)];
        $size   = $this->tyreSizes[array_rand($this->tyreSizes)];
        $dim    = $this->rotorSizes[array_rand($this->rotorSizes)];

        $name = str_replace(
            ['{brand}', '{sku}', '{compat}', '{size}', '{dim}'],
            [$brandName, strtoupper(base_convert(rand(1000, 9999), 10, 36)), $compat, $size, $dim],
            $template
        );

        return $name;
    }

    private function randomPrice(float $min, float $max): float
    {
        $base  = $min + ($max - $min) * (rand(0, 100) / 100);
        $jitter = 1 + (rand(-15, 15) / 100); // ±15% jitter
        return max($min, $base * $jitter);
    }
}
