<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use App\Models\District;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::create([
            'name' => 'My Store',
            'slug' => 'my-store',
            'description' => 'Welcome to our online store.',
            'tagline' => 'Quality products at your doorstep',
            'is_active' => true,
        ]);

        // Default store settings
        $settings = [
            ['key' => 'currency', 'value' => 'BDT', 'type' => 'string', 'group' => 'general'],
            ['key' => 'currency_symbol', 'value' => '৳', 'type' => 'string', 'group' => 'general'],
            ['key' => 'currency_position', 'value' => 'before', 'type' => 'string', 'group' => 'general'],
            ['key' => 'default_language', 'value' => 'en', 'type' => 'string', 'group' => 'general'],
            ['key' => 'support_phone', 'value' => '01700-000000', 'type' => 'string', 'group' => 'general'],
            ['key' => 'support_email', 'value' => '', 'type' => 'string', 'group' => 'general'],

            // Theme
            ['key' => 'primary_color', 'value' => '#16a34a', 'type' => 'string', 'group' => 'appearance'],
            ['key' => 'theme', 'value' => 'default', 'type' => 'string', 'group' => 'appearance'],

            // Payment methods
            ['key' => 'payment_cod_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'payment'],
            ['key' => 'payment_sslcommerz_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'payment'],
            ['key' => 'payment_bkash_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'payment'],
            ['key' => 'sslcommerz_store_id', 'value' => '', 'type' => 'string', 'group' => 'payment'],
            ['key' => 'sslcommerz_store_password', 'value' => '', 'type' => 'string', 'group' => 'payment'],
            ['key' => 'bkash_app_key', 'value' => '', 'type' => 'string', 'group' => 'payment'],

            // COD specific
            ['key' => 'cod_confirmation_required', 'value' => '1', 'type' => 'boolean', 'group' => 'payment'],
            ['key' => 'cod_confirmation_message', 'value' => 'আপনার অর্ডার নিশ্চিত করতে আমাদের এজেন্ট শীঘ্রই কল করবে।', 'type' => 'string', 'group' => 'payment'],

            // Delivery
            ['key' => 'delivery_inside_dhaka', 'value' => '60', 'type' => 'integer', 'group' => 'delivery'],
            ['key' => 'delivery_outside_dhaka', 'value' => '120', 'type' => 'integer', 'group' => 'delivery'],
            ['key' => 'free_delivery_above', 'value' => '0', 'type' => 'integer', 'group' => 'delivery'],

            // SEO
            ['key' => 'meta_title', 'value' => 'My Store - Quality Products Online', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'meta_description', 'value' => 'Shop quality products online with fast delivery across Bangladesh.', 'type' => 'string', 'group' => 'seo'],
        ];

        foreach ($settings as $setting) {
            $store->settings()->create($setting);
        }

        // Create default delivery zones
        $dhakaDistrict = District::where('name', 'Dhaka')->first();

        if ($dhakaDistrict) {
            DeliveryZone::create([
                'store_id' => $store->id,
                'name' => 'Inside Dhaka',
                'type' => 'district',
                'location_ids' => [$dhakaDistrict->id],
                'delivery_charge' => 60.00,
                'free_delivery_above' => 1000.00,
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
                'is_active' => true,
                'sort_order' => 1,
            ]);
        }

        DeliveryZone::create([
            'store_id' => $store->id,
            'name' => 'Outside Dhaka',
            'type' => 'nationwide',
            'location_ids' => [],
            'delivery_charge' => 120.00,
            'free_delivery_above' => 2000.00,
            'estimated_days_min' => 3,
            'estimated_days_max' => 5,
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }
}
