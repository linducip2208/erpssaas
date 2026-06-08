<?php

namespace Workdo\CustomField\Database\Seeders;

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
            ['name' => 'manage-custom-fields', 'module' => 'custom-fields', 'label' => 'Manage CustomFields'],

            ['name' => 'manage-custom-field-groups', 'module' => 'custom-field-groups', 'label' => 'Manage CustomField Groups'],
            ['name' => 'manage-any-custom-field-groups', 'module' => 'custom-field-groups', 'label' => 'Manage All CustomField Groups'],
            ['name' => 'manage-own-custom-field-groups', 'module' => 'custom-field-groups', 'label' => 'Manage Own CustomField Groups'],
            ['name' => 'create-custom-field-groups', 'module' => 'custom-field-groups', 'label' => 'Create CustomField Groups'],
            ['name' => 'edit-custom-field-groups', 'module' => 'custom-field-groups', 'label' => 'Edit CustomField Groups'],
            ['name' => 'delete-custom-field-groups', 'module' => 'custom-field-groups', 'label' => 'Delete CustomField Groups'],
            ['name' => 'view-custom-fields', 'module' => 'custom-fields', 'label' => 'View CustomFields'],

            ['name' => 'manage-custom-field-definitions', 'module' => 'custom-field-definitions', 'label' => 'Manage CustomField Definitions'],
            ['name' => 'manage-any-custom-field-definitions', 'module' => 'custom-field-definitions', 'label' => 'Manage All CustomField Definitions'],
            ['name' => 'manage-own-custom-field-definitions', 'module' => 'custom-field-definitions', 'label' => 'Manage Own CustomField Definitions'],
            ['name' => 'create-custom-field-definitions', 'module' => 'custom-field-definitions', 'label' => 'Create CustomField Definitions'],
            ['name' => 'edit-custom-field-definitions', 'module' => 'custom-field-definitions', 'label' => 'Edit CustomField Definitions'],
            ['name' => 'delete-custom-field-definitions', 'module' => 'custom-field-definitions', 'label' => 'Delete CustomField Definitions'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'CustomField',
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
