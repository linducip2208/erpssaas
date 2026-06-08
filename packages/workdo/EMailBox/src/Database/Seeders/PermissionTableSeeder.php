<?php

namespace Workdo\EMailBox\Database\Seeders;

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
            ['name' => 'manage-emailbox', 'module' => 'emailbox', 'label' => 'Manage Email Box'],
            ['name' => 'manage-mailboxes', 'module' => 'emailbox', 'label' => 'Manage Mailboxes'],
            ['name' => 'send-emails', 'module' => 'emailbox', 'label' => 'Send Emails'],
            ['name' => 'fetch-emails', 'module' => 'emailbox', 'label' => 'Fetch Emails'],
            ['name' => 'delete-emails', 'module' => 'emailbox', 'label' => 'Delete Emails'],
            ['name' => 'manage-emailbox-settings', 'module' => 'emailbox', 'label' => 'Manage Email Box Settings'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'EMailBox',
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
