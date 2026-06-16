<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\District;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FakeDataSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();

        // ── 1. Attributes ──────────────────────────────────────────────────
        $attrs = $this->seedAttributes($store);

        // ── 2. Categories ──────────────────────────────────────────────────
        $categories = $this->seedCategories($store, $attrs);

        // ── 3. Products ────────────────────────────────────────────────────
        $this->seedProducts($store, $categories, $attrs);

        // ── 4. Customers ───────────────────────────────────────────────────
        $customers = $this->seedCustomers($store);

        // ── 5. Orders ──────────────────────────────────────────────────────
        $this->seedOrders($store, $customers);

        $this->command->info('Fake data seeded successfully!');
    }

    // ────────────────────────────────────────────────────────────────────────
    private function seedAttributes(Store $store): array
    {
        $defs = [
            ['name' => 'Color', 'slug' => 'color', 'type' => 'select', 'is_filterable' => true, 'is_variant' => true,
                'options' => [
                    ['label' => 'Red', 'value' => 'Red'],
                    ['label' => 'Blue', 'value' => 'Blue'],
                    ['label' => 'Green', 'value' => 'Green'],
                    ['label' => 'Black', 'value' => 'Black'],
                    ['label' => 'White', 'value' => 'White'],
                    ['label' => 'Yellow', 'value' => 'Yellow'],
                ]],
            ['name' => 'Size', 'slug' => 'size', 'type' => 'select', 'is_filterable' => true, 'is_variant' => true,
                'options' => [
                    ['label' => 'XS', 'value' => 'XS'],
                    ['label' => 'S', 'value' => 'S'],
                    ['label' => 'M', 'value' => 'M'],
                    ['label' => 'L', 'value' => 'L'],
                    ['label' => 'XL', 'value' => 'XL'],
                    ['label' => 'XXL', 'value' => 'XXL'],
                ]],
            ['name' => 'Weight', 'slug' => 'weight', 'type' => 'select', 'is_filterable' => true, 'is_variant' => true,
                'options' => [
                    ['label' => '250g', 'value' => '250g'],
                    ['label' => '500g', 'value' => '500g'],
                    ['label' => '1kg', 'value' => '1kg'],
                    ['label' => '2kg', 'value' => '2kg'],
                    ['label' => '5kg', 'value' => '5kg'],
                ]],
            ['name' => 'Brand', 'slug' => 'brand', 'type' => 'text', 'is_filterable' => true, 'is_variant' => false, 'options' => null],
            ['name' => 'Material', 'slug' => 'material', 'type' => 'text', 'is_filterable' => false, 'is_variant' => false, 'options' => null],
            ['name' => 'Warranty', 'slug' => 'warranty', 'type' => 'select', 'is_filterable' => false, 'is_variant' => false,
                'options' => [
                    ['label' => '6 Months', 'value' => '6 months'],
                    ['label' => '1 Year', 'value' => '1 year'],
                    ['label' => '2 Years', 'value' => '2 years'],
                ]],
            ['name' => 'Storage', 'slug' => 'storage', 'type' => 'select', 'is_filterable' => true, 'is_variant' => true,
                'options' => [
                    ['label' => '64GB', 'value' => '64GB'],
                    ['label' => '128GB', 'value' => '128GB'],
                    ['label' => '256GB', 'value' => '256GB'],
                ]],
            ['name' => 'RAM', 'slug' => 'ram', 'type' => 'select', 'is_filterable' => true, 'is_variant' => true,
                'options' => [
                    ['label' => '4GB', 'value' => '4GB'],
                    ['label' => '6GB', 'value' => '6GB'],
                    ['label' => '8GB', 'value' => '8GB'],
                    ['label' => '12GB', 'value' => '12GB'],
                ]],
            ['name' => 'Expiry', 'slug' => 'expiry', 'type' => 'text', 'is_filterable' => false, 'is_variant' => false, 'options' => null],
            ['name' => 'Origin', 'slug' => 'origin', 'type' => 'text', 'is_filterable' => true, 'is_variant' => false, 'options' => null],
        ];

        $created = [];
        foreach ($defs as $i => $def) {
            $created[$def['slug']] = Attribute::create(array_merge($def, [
                'store_id' => $store->id,
                'sort_order' => $i,
            ]));
        }
        return $created;
    }

    // ────────────────────────────────────────────────────────────────────────
    private function seedCategories(Store $store, array $attrs): array
    {
        $rootDefs = [
            ['name' => 'Clothing & Fashion', 'slug' => 'clothing-fashion', 'attrs' => ['color', 'size', 'material', 'brand'],
                'children' => [
                    ['name' => 'Men\'s Clothing', 'slug' => 'mens-clothing', 'attrs' => ['color', 'size', 'material', 'brand']],
                    ['name' => 'Women\'s Clothing', 'slug' => 'womens-clothing', 'attrs' => ['color', 'size', 'material', 'brand']],
                    ['name' => 'Kids\' Clothing', 'slug' => 'kids-clothing', 'attrs' => ['color', 'size', 'material']],
                ]],
            ['name' => 'Electronics', 'slug' => 'electronics', 'attrs' => ['brand', 'warranty'],
                'children' => [
                    ['name' => 'Smartphones', 'slug' => 'smartphones', 'attrs' => ['brand', 'storage', 'ram', 'color', 'warranty']],
                    ['name' => 'Laptops', 'slug' => 'laptops', 'attrs' => ['brand', 'storage', 'ram', 'warranty']],
                    ['name' => 'Accessories', 'slug' => 'electronics-accessories', 'attrs' => ['brand', 'color']],
                ]],
            ['name' => 'Food & Grocery', 'slug' => 'food-grocery', 'attrs' => ['weight', 'expiry', 'origin'],
                'children' => [
                    ['name' => 'Rice & Grains', 'slug' => 'rice-grains', 'attrs' => ['weight', 'origin', 'expiry']],
                    ['name' => 'Spices & Condiments', 'slug' => 'spices-condiments', 'attrs' => ['weight', 'brand', 'expiry']],
                    ['name' => 'Snacks & Drinks', 'slug' => 'snacks-drinks', 'attrs' => ['weight', 'brand', 'expiry']],
                ]],
            ['name' => 'Home & Living', 'slug' => 'home-living', 'attrs' => ['color', 'material', 'brand'],
                'children' => [
                    ['name' => 'Kitchen', 'slug' => 'kitchen', 'attrs' => ['color', 'material', 'brand']],
                    ['name' => 'Bedding', 'slug' => 'bedding', 'attrs' => ['color', 'size', 'material']],
                ]],
        ];

        $categories = [];
        foreach ($rootDefs as $i => $def) {
            $root = Category::create([
                'store_id' => $store->id,
                'name' => $def['name'],
                'slug' => $def['slug'],
                'is_active' => true,
                'sort_order' => $i,
            ]);
            $this->attachAttributes($root, $def['attrs'], $attrs);
            $categories[$def['slug']] = $root;

            foreach ($def['children'] as $j => $child) {
                $cat = Category::create([
                    'store_id' => $store->id,
                    'parent_id' => $root->id,
                    'name' => $child['name'],
                    'slug' => $child['slug'],
                    'is_active' => true,
                    'sort_order' => $j,
                ]);
                $this->attachAttributes($cat, $child['attrs'], $attrs);
                $categories[$child['slug']] = $cat;
            }
        }
        return $categories;
    }

    private function attachAttributes(Category $category, array $slugs, array $attrs): void
    {
        foreach ($slugs as $i => $slug) {
            if (isset($attrs[$slug])) {
                $category->attributes()->attach($attrs[$slug]->id, ['sort_order' => $i, 'is_required' => false]);
            }
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    private function seedProducts(Store $store, array $categories, array $attrs): void
    {
        $products = [
            // Clothing
            ['name' => 'Classic Cotton T-Shirt', 'cat' => 'mens-clothing', 'price' => 350, 'sale' => 280,
                'unit' => 'piece', 'status' => 'active', 'featured' => true, 'stock' => 120,
                'desc' => 'Comfortable 100% cotton t-shirt, perfect for everyday wear. Available in multiple colors and sizes.',
                'attrs' => ['brand' => 'FashionBD', 'material' => '100% Cotton'],
                'variants' => [
                    ['color' => 'Red', 'size' => 'S', 'stock' => 15, 'price' => null],
                    ['color' => 'Red', 'size' => 'M', 'stock' => 20, 'price' => null],
                    ['color' => 'Red', 'size' => 'L', 'stock' => 18, 'price' => null],
                    ['color' => 'Blue', 'size' => 'M', 'stock' => 25, 'price' => null],
                    ['color' => 'Blue', 'size' => 'L', 'stock' => 20, 'price' => null],
                    ['color' => 'Black', 'size' => 'M', 'stock' => 30, 'price' => null],
                    ['color' => 'Black', 'size' => 'L', 'stock' => 12, 'price' => null],
                ]],
            ['name' => 'Women\'s Floral Kameez', 'cat' => 'womens-clothing', 'price' => 850, 'sale' => 699,
                'unit' => 'piece', 'status' => 'active', 'featured' => true, 'stock' => 60,
                'desc' => 'Beautiful floral printed kameez with embroidery work. Made from high-quality georgette fabric.',
                'attrs' => ['brand' => 'DhakaStyle', 'material' => 'Georgette'],
                'variants' => [
                    ['color' => 'Red', 'size' => 'S', 'stock' => 10, 'price' => null],
                    ['color' => 'Red', 'size' => 'M', 'stock' => 15, 'price' => null],
                    ['color' => 'Green', 'size' => 'M', 'stock' => 12, 'price' => null],
                    ['color' => 'Green', 'size' => 'L', 'stock' => 8, 'price' => null],
                    ['color' => 'Blue', 'size' => 'L', 'stock' => 15, 'price' => null],
                ]],
            ['name' => 'Men\'s Slim Fit Jeans', 'cat' => 'mens-clothing', 'price' => 1200, 'sale' => null,
                'unit' => 'piece', 'status' => 'active', 'featured' => false, 'stock' => 80,
                'desc' => 'Premium slim fit denim jeans with stretch comfort. Suitable for casual and semi-formal occasions.',
                'attrs' => ['brand' => 'DenimCo BD', 'material' => '98% Cotton 2% Elastane'],
                'variants' => [
                    ['color' => 'Blue', 'size' => 'S', 'stock' => 10, 'price' => null],
                    ['color' => 'Blue', 'size' => 'M', 'stock' => 20, 'price' => null],
                    ['color' => 'Blue', 'size' => 'L', 'stock' => 20, 'price' => null],
                    ['color' => 'Black', 'size' => 'M', 'stock' => 15, 'price' => null],
                    ['color' => 'Black', 'size' => 'L', 'stock' => 15, 'price' => null],
                ]],

            // Electronics
            ['name' => 'Samsung Galaxy A55 5G', 'cat' => 'smartphones', 'price' => 52999, 'sale' => 49999,
                'unit' => 'piece', 'status' => 'active', 'featured' => true, 'stock' => 25,
                'desc' => 'Samsung Galaxy A55 5G with 6.6" Super AMOLED display, 50MP camera, 5000mAh battery. Official warranty.',
                'attrs' => ['brand' => 'Samsung', 'warranty' => '1 year'],
                'variants' => [
                    ['storage' => '128GB', 'ram' => '8GB', 'color' => 'Blue', 'stock' => 8, 'price' => 49999],
                    ['storage' => '256GB', 'ram' => '8GB', 'color' => 'Blue', 'stock' => 5, 'price' => 54999],
                    ['storage' => '256GB', 'ram' => '12GB', 'color' => 'Black', 'stock' => 7, 'price' => 57999],
                    ['storage' => '128GB', 'ram' => '8GB', 'color' => 'Black', 'stock' => 5, 'price' => 49999],
                ]],
            ['name' => 'Xiaomi Redmi Note 13', 'cat' => 'smartphones', 'price' => 22999, 'sale' => 20999,
                'unit' => 'piece', 'status' => 'active', 'featured' => false, 'stock' => 40,
                'desc' => 'Redmi Note 13 with 108MP camera, 5000mAh battery, 33W fast charging. Great value for money.',
                'attrs' => ['brand' => 'Xiaomi', 'warranty' => '1 year'],
                'variants' => [
                    ['storage' => '128GB', 'ram' => '6GB', 'color' => 'Black', 'stock' => 15, 'price' => 20999],
                    ['storage' => '256GB', 'ram' => '8GB', 'color' => 'Black', 'stock' => 10, 'price' => 23999],
                    ['storage' => '128GB', 'ram' => '6GB', 'color' => 'Blue', 'stock' => 15, 'price' => 20999],
                ]],
            ['name' => 'Bluetooth Earbuds TWS', 'cat' => 'electronics-accessories', 'price' => 1499, 'sale' => 999,
                'unit' => 'piece', 'status' => 'active', 'featured' => true, 'stock' => 150,
                'desc' => 'True wireless stereo earbuds with 30hr battery life, IPX5 waterproof, noise cancellation.',
                'attrs' => ['brand' => 'SoundMax', 'warranty' => '6 months'],
                'variants' => [
                    ['color' => 'Black', 'stock' => 60, 'price' => null],
                    ['color' => 'White', 'stock' => 50, 'price' => null],
                    ['color' => 'Blue', 'stock' => 40, 'price' => null],
                ]],

            // Food
            ['name' => 'Miniket Premium Rice', 'cat' => 'rice-grains', 'price' => 75, 'sale' => null,
                'unit' => 'kg', 'status' => 'active', 'featured' => false, 'stock' => 500,
                'desc' => 'Premium quality Miniket rice from Dinajpur. Fragrant, long grain, and perfect for everyday cooking.',
                'attrs' => ['origin' => 'Dinajpur, Bangladesh', 'expiry' => '12 months from packing'],
                'variants' => [
                    ['weight' => '1kg', 'stock' => 200, 'price' => 75],
                    ['weight' => '2kg', 'stock' => 150, 'price' => 145],
                    ['weight' => '5kg', 'stock' => 150, 'price' => 355],
                ]],
            ['name' => 'Pran Mixed Spice Pack', 'cat' => 'spices-condiments', 'price' => 120, 'sale' => null,
                'unit' => 'pack', 'status' => 'active', 'featured' => false, 'stock' => 200,
                'desc' => 'PRAN mixed spice combo including turmeric, cumin, coriander, and chili powder. All-natural.',
                'attrs' => ['brand' => 'PRAN', 'expiry' => '18 months'],
                'variants' => [
                    ['weight' => '250g', 'stock' => 100, 'price' => 120],
                    ['weight' => '500g', 'stock' => 100, 'price' => 225],
                ]],
            ['name' => 'Mango Juice (Frooto)', 'cat' => 'snacks-drinks', 'price' => 35, 'sale' => null,
                'unit' => 'piece', 'status' => 'active', 'featured' => false, 'stock' => 3, // low stock
                'desc' => 'Refreshing Frooto mango juice made from real Bangladeshi mangoes. No artificial colors.',
                'attrs' => ['brand' => 'PRAN', 'expiry' => '6 months'],
                'variants' => []],

            // Home
            ['name' => 'Non-Stick Frying Pan 28cm', 'cat' => 'kitchen', 'price' => 850, 'sale' => 699,
                'unit' => 'piece', 'status' => 'active', 'featured' => false, 'stock' => 45,
                'desc' => 'Premium non-stick aluminum frying pan with heat-resistant handle. Suitable for all cooktops.',
                'attrs' => ['brand' => 'HomeChef BD', 'material' => 'Aluminum with Teflon coating'],
                'variants' => [
                    ['color' => 'Black', 'stock' => 30, 'price' => null],
                    ['color' => 'Red', 'stock' => 15, 'price' => null],
                ]],
            ['name' => 'Cotton Bed Sheet Set (King)', 'cat' => 'bedding', 'price' => 1800, 'sale' => 1500,
                'unit' => 'set', 'status' => 'active', 'featured' => true, 'stock' => 30,
                'desc' => 'Soft 100% cotton king-size bed sheet set including 1 flat sheet, 1 fitted sheet, and 2 pillow covers.',
                'attrs' => ['brand' => 'SleepWell BD', 'material' => '100% Cotton 180TC'],
                'variants' => [
                    ['color' => 'White', 'size' => 'XL', 'stock' => 10, 'price' => null],
                    ['color' => 'Blue', 'size' => 'XL', 'stock' => 10, 'price' => null],
                    ['color' => 'Green', 'size' => 'XL', 'stock' => 10, 'price' => null],
                ]],
        ];

        foreach ($products as $i => $def) {
            $cat = $categories[$def['cat']] ?? null;

            $product = Product::create([
                'store_id' => $store->id,
                'category_id' => $cat?->id,
                'name' => $def['name'],
                'slug' => Str::slug($def['name']),
                'short_description' => Str::limit($def['desc'], 120),
                'description' => '<p>' . $def['desc'] . '</p><p>Order now and get fast delivery across Bangladesh. Cash on delivery available.</p>',
                'status' => $def['status'],
                'is_featured' => $def['featured'],
                'unit_of_sale' => $def['unit'],
                'base_price' => $def['price'],
                'sale_price' => $def['sale'],
                'cost_price' => round($def['price'] * 0.6),
                'sku' => 'SKU-' . strtoupper(Str::random(6)),
                'stock_quantity' => $def['stock'],
                'low_stock_threshold' => 5,
                'track_inventory' => true,
                'sort_order' => $i,
            ]);

            // Attribute values (non-variant)
            foreach ($def['attrs'] as $attrSlug => $value) {
                if (isset($attrs[$attrSlug])) {
                    ProductAttributeValue::create([
                        'product_id' => $product->id,
                        'attribute_id' => $attrs[$attrSlug]->id,
                        'value' => $value,
                    ]);
                }
            }

            // Variants
            foreach ($def['variants'] as $j => $varDef) {
                $attrValues = array_diff_key($varDef, array_flip(['stock', 'price']));
                $label = implode(' / ', array_values($attrValues));

                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => 'VAR-' . strtoupper(Str::random(5)),
                    'attribute_values' => $attrValues,
                    'variant_label' => $label,
                    'price' => $varDef['price'],
                    'stock_quantity' => $varDef['stock'],
                    'is_active' => true,
                    'sort_order' => $j,
                ]);
            }

            // Reviews (2-4 per product)
            $reviewCount = rand(2, 5);
            $reviewTexts = [
                ['title' => 'Great product!', 'body' => 'Very satisfied with this purchase. Fast delivery and good quality.'],
                ['title' => 'Good value', 'body' => 'Product matches the description. Delivery was quick. Will buy again.'],
                ['title' => 'Excellent quality', 'body' => 'Much better than expected. Packaging was also good.'],
                ['title' => 'Recommended', 'body' => 'Ordered twice already. Consistent quality and great price.'],
                ['title' => 'Satisfied', 'body' => 'Delivery in 2 days to Dhaka. Product is exactly as shown.'],
            ];
            for ($r = 0; $r < $reviewCount; $r++) {
                $text = $reviewTexts[$r % count($reviewTexts)];
                Review::create([
                    'product_id' => $product->id,
                    'rating' => rand(4, 5),
                    'title' => $text['title'],
                    'body' => $text['body'],
                    'is_approved' => true,
                ]);
            }
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    private function seedCustomers(Store $store): array
    {
        $customerDefs = [
            ['name' => 'Rahim Uddin', 'phone' => '01711000001', 'district' => 'Dhaka'],
            ['name' => 'Sumaiya Begum', 'phone' => '01812000002', 'district' => 'Chittagong'],
            ['name' => 'Karim Mia', 'phone' => '01913000003', 'district' => 'Gazipur'],
            ['name' => 'Nasrin Akter', 'phone' => '01614000004', 'district' => 'Dhaka'],
            ['name' => 'Jamal Hossain', 'phone' => '01515000005', 'district' => 'Rajshahi'],
            ['name' => 'Ruma Khatun', 'phone' => '01716000006', 'district' => 'Dhaka'],
            ['name' => 'Arif Khan', 'phone' => '01817000007', 'district' => 'Sylhet'],
        ];

        $customers = [];
        foreach ($customerDefs as $def) {
            $customer = Customer::create([
                'store_id' => $store->id,
                'name' => $def['name'],
                'phone' => $def['phone'],
                'phone_verified_at' => now()->subDays(rand(1, 30)),
                'is_active' => true,
            ]);

            $district = District::where('name', $def['district'])->first();
            if ($district) {
                $thana = $district->thanas()->inRandomOrder()->first();
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'label' => 'Home',
                    'name' => $def['name'],
                    'phone' => $def['phone'],
                    'division_id' => $district->division_id,
                    'district_id' => $district->id,
                    'thana_id' => $thana?->id ?? $district->thanas()->first()?->id,
                    'area' => 'Main Road',
                    'address_line' => 'House ' . rand(1, 99) . ', Road ' . rand(1, 20),
                    'is_default' => true,
                ]);
            }

            $customers[] = $customer;
        }
        return $customers;
    }

    // ────────────────────────────────────────────────────────────────────────
    private function seedOrders(Store $store, array $customers): void
    {
        $products = Product::where('store_id', $store->id)->where('status', 'active')->with('variants')->get();

        $statusSets = [
            ['pending'],
            ['pending', 'confirmed'],
            ['pending', 'confirmed', 'processing'],
            ['pending', 'confirmed', 'processing', 'shipped'],
            ['pending', 'confirmed', 'processing', 'shipped', 'delivered'],
            ['pending', 'cancelled'],
        ];

        $paymentMethods = ['cod', 'cod', 'cod', 'cod']; // COD-heavy like real BD

        foreach ($customers as $customerIdx => $customer) {
            $address = $customer->defaultAddress();
            if (! $address) continue;

            $orderCount = rand(1, 4);
            for ($o = 0; $o < $orderCount; $o++) {
                $statuses = $statusSets[array_rand($statusSets)];
                $finalStatus = end($statuses);
                $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

                $itemCount = rand(1, 3);
                $subtotal = 0;
                $orderItems = [];

                for ($i = 0; $i < $itemCount; $i++) {
                    $product = $products->random();
                    $variant = $product->variants->isNotEmpty() ? $product->variants->random() : null;
                    $price = $variant?->effective_price ?? $product->effective_price;
                    $qty = rand(1, 3);
                    $itemSubtotal = $price * $qty;
                    $subtotal += $itemSubtotal;

                    $orderItems[] = [
                        'product' => $product,
                        'variant' => $variant,
                        'price' => $price,
                        'qty' => $qty,
                        'subtotal' => $itemSubtotal,
                    ];
                }

                $deliveryCharge = ($address->district?->name === 'Dhaka') ? 60 : 120;
                $total = $subtotal + $deliveryCharge;

                $createdAt = now()->subDays(rand(0, 60))->subHours(rand(0, 23));

                $order = Order::create([
                    'store_id' => $store->id,
                    'customer_id' => $customer->id,
                    'status' => $finalStatus,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $finalStatus === 'delivered' ? 'paid' : 'unpaid',
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'delivery_charge' => $deliveryCharge,
                    'total' => $total,
                    'customer_name' => $customer->name,
                    'customer_phone' => $customer->phone,
                    'division_id' => $address->division_id,
                    'district_id' => $address->district_id,
                    'thana_id' => $address->thana_id,
                    'division_name' => $address->division?->name,
                    'district_name' => $address->district?->name,
                    'thana_name' => $address->thana?->name,
                    'area' => $address->area,
                    'address_line' => $address->address_line,
                    'cod_confirmed_at' => in_array($finalStatus, ['confirmed', 'processing', 'shipped', 'delivered'])
                        ? $createdAt->copy()->addHours(2)
                        : null,
                    'cod_confirmed_by' => in_array($finalStatus, ['confirmed', 'processing', 'shipped', 'delivered'])
                        ? 'Support Agent'
                        : null,
                    'courier_name' => in_array($finalStatus, ['shipped', 'delivered']) ? collect(['Pathao', 'Steadfast', 'RedX'])->random() : null,
                    'courier_tracking_id' => in_array($finalStatus, ['shipped', 'delivered']) ? 'TRK' . strtoupper(Str::random(8)) : null,
                    'shipped_at' => in_array($finalStatus, ['shipped', 'delivered']) ? $createdAt->copy()->addDays(2) : null,
                    'delivered_at' => $finalStatus === 'delivered' ? $createdAt->copy()->addDays(4) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                foreach ($orderItems as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'variant_id' => $item['variant']?->id,
                        'product_name' => $item['product']->name,
                        'variant_label' => $item['variant']?->variant_label,
                        'sku' => $item['variant']?->sku ?? $item['product']->sku,
                        'unit_price' => $item['price'],
                        'quantity' => $item['qty'],
                        'subtotal' => $item['subtotal'],
                        'options' => $item['variant']?->attribute_values,
                    ]);
                }

                // Status history
                $historyTime = $createdAt->copy();
                foreach ($statuses as $status) {
                    OrderStatusHistory::create([
                        'order_id' => $order->id,
                        'status' => $status,
                        'note' => match($status) {
                            'pending' => 'Order placed by customer',
                            'confirmed' => 'COD confirmed by support agent',
                            'processing' => 'Order being prepared',
                            'shipped' => 'Handed to ' . ($order->courier_name ?? 'courier'),
                            'delivered' => 'Delivered to customer',
                            'cancelled' => 'Cancelled by customer',
                            default => '',
                        },
                        'created_by' => $status === 'pending' ? $customer->name : 'Admin',
                        'created_at' => $historyTime,
                        'updated_at' => $historyTime,
                    ]);
                    $historyTime->addHours(rand(4, 24));
                }
            }
        }
    }
}
