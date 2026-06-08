<?php

namespace Workdo\ProjectTemplate\Database\Seeders;

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
            ['name' => 'manage-project-template', 'module' => 'project-template', 'label' => 'Manage Project Template'],
            ['name' => 'create-project-template', 'module' => 'project-template', 'label' => 'Create Project Template'],
            ['name' => 'edit-project-template', 'module' => 'project-template', 'label' => 'Edit Project Template'],
            ['name' => 'delete-project-template', 'module' => 'project-template', 'label' => 'Delete Project Template'],
            ['name' => 'duplicate-project-template', 'module' => 'project-template', 'label' => 'Duplicate Project Template'],
        ];

        $companyRole = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module'     => $perm['module'],
                    'label'      => $perm['label'],
                    'add_on'     => 'ProjectTemplate',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            if ($companyRole && !$companyRole->hasPermissionTo($permission_obj)) {
                $companyRole->givePermissionTo($permission_obj);
            }
        }
    }
}
