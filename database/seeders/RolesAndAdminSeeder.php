<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_store_settings', 'edit_store_settings',
            'view_products', 'create_products', 'edit_products', 'delete_products',
            'view_categories', 'create_categories', 'edit_categories', 'delete_categories',
            'view_orders', 'edit_orders', 'cancel_orders',
            'view_customers', 'edit_customers',
            'view_coupons', 'create_coupons', 'edit_coupons',
            'view_reports',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'store_manager']);
        $manager->syncPermissions([
            'view_products', 'create_products', 'edit_products',
            'view_categories',
            'view_orders', 'edit_orders',
            'view_customers',
            'view_reports',
        ]);

        // Create super admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@store.test'],
            [
                'name' => 'Store Admin',
                'password' => bcrypt('password'),
                'is_super_admin' => true,
            ]
        );

        $user->assignRole('admin');
    }
}
