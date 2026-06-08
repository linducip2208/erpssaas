<?php

namespace Workdo\WhatsApp\Database\Seeders;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

class PermissionTableSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        Artisan::call('cache:clear');

        $permission = [
            ['name' => 'manage-whatsapp', 'module' => 'whatsapp', 'label' => 'Manage WhatsApp'],
            ['name' => 'send-whatsapp', 'module' => 'whatsapp', 'label' => 'Send WhatsApp Messages'],
            ['name' => 'manage-whatsapp-templates', 'module' => 'whatsapp', 'label' => 'Manage WhatsApp Templates'],
            ['name' => 'manage-whatsapp-logs', 'module' => 'whatsapp', 'label' => 'View WhatsApp Logs'],
            ['name' => 'manage-whatsapp-settings', 'module' => 'whatsapp', 'label' => 'Manage WhatsApp Settings'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'WhatsApp',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            if ($company_role && !$company_role->hasPermissionTo($permission_obj)) {
                $company_role->givePermissionTo($permission_obj);
            }
        }
    }
}
