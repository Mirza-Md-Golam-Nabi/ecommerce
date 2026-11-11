<?php

namespace Database\Seeders;

use App\Enums\SaleType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Demo grocery catalog: a 3-level Bangla category tree with realistic products.
 * Idempotent (matches by slug), so it can be re-run safely.
 */
class CatalogSeeder extends Seeder
{
    /**
     * Tree definition: slug => [name, icon, show_on_home, children | products].
     * Leaf categories hold `products`: [slug, name, unit_label, price taka, cost taka, weighted grams|null, compare_at taka|null, keywords].
     *
     * @return array<string, array<string, mixed>>
     */
    private function tree(): array
    {
        return [
            'food' => ['name' => 'খাবার', 'icon' => '🍲', 'children' => [
                'fruits-vegetables' => ['name' => 'ফল ও সবজি', 'icon' => '🥦', 'home' => true, 'children' => [
                    'fresh-vegetables' => ['name' => 'তাজা সবজি', 'icon' => '🥕', 'products' => [
                        ['deshi-peyaj', 'দেশি পেঁয়াজ', '১ কেজি (± ৫০ গ্রাম)', 80, 68, 1000, null, 'onion peyaj piaj'],
                        ['alu-diamond', 'আলু (ডায়মন্ড)', '১ কেজি (± ৫০ গ্রাম)', 45, 38, 1000, null, 'potato alu'],
                        ['kacha-morich', 'কাঁচা মরিচ', '২৫০ গ্রাম (± ২০ গ্রাম)', 40, 32, 250, null, 'green chili kacha morich'],
                        ['tomato', 'টমেটো', '৫০০ গ্রাম (± ৫০ গ্রাম)', 60, 50, 500, null, 'tomato'],
                        ['rosun-deshi', 'দেশি রসুন', '৫০০ গ্রাম (± ২৫ গ্রাম)', 110, 95, 500, null, 'garlic rosun'],
                    ]],
                    'fresh-fruits' => ['name' => 'তাজা ফল', 'icon' => '🍎', 'products' => [
                        ['sagor-kola', 'সাগর কলা', '১২ পিস', 120, 100, null, null, 'banana kola'],
                        ['apple-fuji', 'আপেল (ফুজি)', '১ কেজি (± ৫০ গ্রাম)', 320, 280, 1000, 350, 'apple'],
                        ['malta', 'মাল্টা', '১ কেজি (± ৫০ গ্রাম)', 260, 225, 1000, null, 'malta orange'],
                    ]],
                ]],
                'meat-fish' => ['name' => 'মাছ ও মাংস', 'icon' => '🐟', 'home' => true, 'children' => [
                    'fish' => ['name' => 'মাছ', 'icon' => '🐟', 'products' => [
                        ['rui-mach', 'রুই মাছ', '১ কেজি (± ১০০ গ্রাম)', 380, 330, 1000, null, 'rui fish'],
                        ['ilish-mach', 'ইলিশ মাছ (মাঝারি)', '১ পিস (৭০০-৮০০ গ্রাম)', 1250, 1100, 750, null, 'hilsa ilish'],
                        ['pangas-mach', 'পাঙ্গাস মাছ', '১ কেজি (± ১০০ গ্রাম)', 220, 185, 1000, null, 'pangas fish'],
                    ]],
                    'meat' => ['name' => 'মাংস', 'icon' => '🍗', 'products' => [
                        ['broiler-murgi', 'ব্রয়লার মুরগি', '১ কেজি (± ১০০ গ্রাম)', 210, 185, 1000, null, 'chicken broiler murgi'],
                        ['gorur-mangsho', 'গরুর মাংস', '১ কেজি (± ৫০ গ্রাম)', 780, 720, 1000, null, 'beef gorur mangsho'],
                    ]],
                ]],
                'cooking' => ['name' => 'রান্না', 'icon' => '🍳', 'home' => true, 'children' => [
                    'spices' => ['name' => 'মসলা', 'icon' => '🌶️', 'products' => [
                        ['radhuni-holud-gura-200g', 'রাঁধুনি হলুদ গুঁড়া', '২০০ গ্রাম', 85, 72, null, 95, 'turmeric holud radhuni'],
                        ['radhuni-morich-gura-200g', 'রাঁধুনি মরিচ গুঁড়া', '২০০ গ্রাম', 110, 94, null, null, 'chili powder morich radhuni'],
                        ['radhuni-dhonia-gura-200g', 'রাঁধুনি ধনিয়া গুঁড়া', '২০০ গ্রাম', 70, 60, null, null, 'coriander dhonia radhuni'],
                        ['pran-jeera-gura-100g', 'প্রাণ জিরা গুঁড়া', '১০০ গ্রাম', 95, 82, null, null, 'cumin jeera pran'],
                    ]],
                    'rice' => ['name' => 'চাল', 'icon' => '🍚', 'products' => [
                        ['miniket-chal-5kg', 'মিনিকেট চাল', '৫ কেজি', 390, 355, null, 420, 'rice miniket chal'],
                        ['nazirshail-chal-5kg', 'নাজিরশাইল চাল', '৫ কেজি', 450, 410, null, null, 'rice nazirshail chal'],
                        ['chinigura-chal-1kg', 'চিনিগুঁড়া চাল', '১ কেজি', 150, 132, null, null, 'rice chinigura polao'],
                    ]],
                    'dal-lentil' => ['name' => 'ডাল', 'icon' => '🫘', 'products' => [
                        ['moshur-dal-deshi-1kg', 'দেশি মসুর ডাল', '১ কেজি', 140, 125, null, null, 'lentil moshur dal'],
                        ['mug-dal-1kg', 'মুগ ডাল', '১ কেজি', 160, 142, null, null, 'mung dal mug'],
                    ]],
                    'oil' => ['name' => 'তেল', 'icon' => '🫗', 'products' => [
                        ['teer-soybean-tel-5l', 'তীর সয়াবিন তেল', '৫ লিটার', 850, 805, null, 880, 'soybean oil teer'],
                        ['rupchanda-soybean-tel-1l', 'রূপচাঁদা সয়াবিন তেল', '১ লিটার', 175, 165, null, null, 'soybean oil rupchanda'],
                        ['radhuni-sorishar-tel-500ml', 'রাঁধুনি সরিষার তেল', '৫০০ মিলি', 160, 145, null, null, 'mustard oil sorisha'],
                    ]],
                    'salt-sugar' => ['name' => 'লবণ ও চিনি', 'icon' => '🧂', 'products' => [
                        ['molla-lobon-1kg', 'মোল্লা লবণ', '১ কেজি', 42, 36, null, null, 'salt lobon molla'],
                        ['chini-1kg', 'চিনি', '১ কেজি', 135, 124, null, null, 'sugar chini'],
                    ]],
                ]],
                'beverages' => ['name' => 'পানীয়', 'icon' => '🥤', 'home' => true, 'children' => [
                    'tea-coffee' => ['name' => 'চা ও কফি', 'icon' => '☕', 'products' => [
                        ['ispahani-mirzapore-cha-400g', 'ইস্পাহানি মির্জাপুর চা', '৪০০ গ্রাম', 230, 205, null, null, 'tea cha ispahani'],
                        ['nescafe-classic-50g', 'নেসক্যাফে ক্লাসিক', '৫০ গ্রাম', 290, 260, null, 310, 'coffee nescafe'],
                    ]],
                    'juice' => ['name' => 'জুস', 'icon' => '🧃', 'products' => [
                        ['pran-mango-juice-1l', 'প্রাণ ম্যাঙ্গো জুস', '১ লিটার', 120, 104, null, null, 'mango juice pran'],
                    ]],
                ]],
                'snacks' => ['name' => 'নাস্তা', 'icon' => '🍪', 'home' => true, 'children' => [
                    'biscuits' => ['name' => 'বিস্কুট', 'icon' => '🍪', 'products' => [
                        ['olympic-energy-plus-biscuit', 'অলিম্পিক এনার্জি প্লাস বিস্কুট', '২৪০ গ্রাম', 60, 51, null, null, 'biscuit olympic'],
                        ['danish-lexus-biscuit', 'ড্যানিশ লেক্সাস বিস্কুট', '২৪০ গ্রাম', 70, 60, null, null, 'biscuit danish lexus'],
                    ]],
                    'chips-chanachur' => ['name' => 'চানাচুর ও চিপস', 'icon' => '🥨', 'products' => [
                        ['bombay-chanachur-500g', 'বোম্বে চানাচুর', '৫০০ গ্রাম', 160, 140, null, null, 'chanachur bombay'],
                    ]],
                ]],
                'dairy-eggs' => ['name' => 'দুধ ও ডিম', 'icon' => '🥚', 'home' => true, 'children' => [
                    'milk' => ['name' => 'দুধ', 'icon' => '🥛', 'products' => [
                        ['milk-vita-1l', 'মিল্ক ভিটা দুধ', '১ লিটার', 100, 90, null, null, 'milk dudh milk vita'],
                        ['diploma-milk-powder-500g', 'ডিপ্লোমা গুঁড়া দুধ', '৫০০ গ্রাম', 450, 415, null, null, 'milk powder diploma'],
                    ]],
                    'eggs' => ['name' => 'ডিম', 'icon' => '🥚', 'products' => [
                        ['farm-dim-12', 'ফার্মের লাল ডিম', '১২ পিস', 155, 138, null, null, 'egg dim'],
                        ['hasher-dim-12', 'হাঁসের ডিম', '১২ পিস', 240, 215, null, null, 'duck egg hasher dim'],
                    ]],
                ]],
            ]],
            'cleaning' => ['name' => 'পরিষ্কার-পরিচ্ছন্নতা', 'icon' => '🧼', 'home' => true, 'children' => [
                'laundry' => ['name' => 'কাপড় কাচা', 'icon' => '👕', 'products' => [
                    ['wheel-powder-1kg', 'হুইল ওয়াশিং পাউডার', '১ কেজি', 140, 124, null, null, 'detergent wheel powder'],
                    ['rin-powder-1kg', 'রিন ওয়াশিং পাউডার', '১ কেজি', 175, 156, null, 190, 'detergent rin'],
                ]],
                'dishwashing' => ['name' => 'থালা-বাসন ধোয়া', 'icon' => '🍽️', 'products' => [
                    ['vim-liquid-500ml', 'ভিম লিকুইড', '৫০০ মিলি', 125, 110, null, null, 'dish wash vim liquid'],
                    ['vim-bar-300g', 'ভিম বার', '৩০০ গ্রাম', 40, 34, null, null, 'dish wash vim bar'],
                ]],
            ]],
            'personal-care' => ['name' => 'ব্যক্তিগত যত্ন', 'icon' => '🧴', 'home' => true, 'children' => [
                'soap-bodywash' => ['name' => 'সাবান ও বডিওয়াশ', 'icon' => '🧼', 'products' => [
                    ['lux-soap-100g', 'লাক্স সাবান', '১০০ গ্রাম', 65, 56, null, null, 'soap lux'],
                    ['dettol-soap-125g', 'ডেটল সাবান', '১২৫ গ্রাম', 80, 70, null, null, 'soap dettol'],
                ]],
                'shampoo' => ['name' => 'শ্যাম্পু', 'icon' => '🧴', 'products' => [
                    ['sunsilk-shampoo-375ml', 'সানসিল্ক শ্যাম্পু', '৩৭৫ মিলি', 420, 380, null, null, 'shampoo sunsilk'],
                    ['clear-shampoo-180ml', 'ক্লিয়ার শ্যাম্পু', '১৮০ মিলি', 330, 298, null, null, 'shampoo clear'],
                ]],
            ]],
        ];
    }

    public function run(): void
    {
        $homeOrder = 0;

        $this->seedLevel($this->tree(), null, $homeOrder);
    }

    /**
     * @param  array<string, array<string, mixed>>  $nodes
     */
    private function seedLevel(array $nodes, ?Category $parent, int &$homeOrder): void
    {
        foreach ($nodes as $slug => $node) {
            $showOnHome = (bool) ($node['home'] ?? false);

            $category = Category::withTrashed()->updateOrCreate(['slug' => $slug], [
                'parent_id' => $parent?->id,
                'name' => $node['name'],
                'icon' => $node['icon'] ?? null,
                'is_active' => true,
                'show_on_home' => $showOnHome,
                'home_sort_order' => $showOnHome ? ++$homeOrder : 0,
            ]);

            if (isset($node['children'])) {
                $this->seedLevel($node['children'], $category, $homeOrder);
            }

            foreach ($node['products'] ?? [] as [$productSlug, $name, $unitLabel, $price, $cost, $weightGrams, $compareAt, $keywords]) {
                Product::withTrashed()->updateOrCreate(['slug' => $productSlug], [
                    'category_id' => $category->id,
                    'name' => $name,
                    'search_keywords' => $keywords,
                    'sale_type' => $weightGrams ? SaleType::Weighted : SaleType::Fixed,
                    'unit_label' => $unitLabel,
                    'nominal_weight_grams' => $weightGrams,
                    'price' => $price * 100,
                    'compare_at_price' => $compareAt ? $compareAt * 100 : null,
                    'estimated_cost' => $cost * 100,
                    'is_active' => true,
                    'is_available' => true,
                ]);
            }
        }
    }
}
