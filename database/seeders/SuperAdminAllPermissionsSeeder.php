<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminAllPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Run all package permission seeders
        $packageSeeders = [
            \Workdo\Account\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\AIAssistant\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\BudgetPlanner\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Calendar\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Contract\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\DoubleEntry\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\FormBuilder\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Goal\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\GoogleCaptcha\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Hrm\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\LandingPage\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Lead\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Paypal\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Performance\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Pos\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\ProductService\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Quotation\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Recruitment\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Slack\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Stripe\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\SupportTicket\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Taskly\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Telegram\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Timesheet\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Training\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Twilio\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\Webhook\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\ZoomMeeting\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\CustomField\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\API\Database\Seeders\PermissionTableSeeder::class,
            \Workdo\SideMenuBuilder\Database\Seeders\PermissionTableSeeder::class,
        ];

        foreach ($packageSeeders as $seeder) {
            $this->call($seeder);
        }

        // Add new core module permissions
        $newPermissions = [
            ['name' => 'manage-sales-orders', 'module' => 'sales-orders', 'label' => 'Manage Sales Orders'],
            ['name' => 'manage-delivery-orders', 'module' => 'delivery-orders', 'label' => 'Manage Delivery Orders'],
            ['name' => 'manage-purchase-requisitions', 'module' => 'purchase-requisitions', 'label' => 'Manage Purchase Requisitions'],
            ['name' => 'manage-request-quotations', 'module' => 'request-quotations', 'label' => 'Manage Request Quotations'],
            ['name' => 'manage-purchase-orders', 'module' => 'purchase-orders', 'label' => 'Manage Purchase Orders'],
            ['name' => 'manage-goods-receipt-notes', 'module' => 'goods-receipt-notes', 'label' => 'Manage Goods Receipt Notes'],
            ['name' => 'manage-fixed-assets', 'module' => 'fixed-assets', 'label' => 'Manage Fixed Assets'],
            ['name' => 'manage-asset-categories', 'module' => 'asset-categories', 'label' => 'Manage Asset Categories'],
            ['name' => 'manage-asset-depreciations', 'module' => 'asset-depreciations', 'label' => 'Manage Asset Depreciations'],
            ['name' => 'manage-asset-disposals', 'module' => 'asset-disposals', 'label' => 'Manage Asset Disposals'],
            ['name' => 'manage-recurring-invoices', 'module' => 'recurring-invoices', 'label' => 'Manage Recurring Invoices'],
            ['name' => 'manage-gateways', 'module' => 'gateways', 'label' => 'Manage Payment Gateways'],
        ];

        foreach ($newPermissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'general',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Grant ALL permissions to superadmin role
        $superAdminRole = Role::where('name', 'superadmin')->where('guard_name', 'web')->first();
        if ($superAdminRole) {
            $allPermissions = Permission::where('guard_name', 'web')->get();
            $superAdminRole->syncPermissions($allPermissions);
        }

        // Also grant to company role for testing
        $companyRole = Role::where('name', 'company')->where('guard_name', 'web')->first();
        if ($companyRole) {
            $allPermissions = Permission::where('guard_name', 'web')->get();
            $companyRole->syncPermissions($allPermissions);
        }
    }
}
