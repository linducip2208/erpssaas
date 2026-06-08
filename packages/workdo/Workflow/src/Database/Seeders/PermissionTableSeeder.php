<?php

namespace Workdo\Workflow\Database\Seeders;

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
            ['name' => 'manage-workflows', 'module' => 'workflow', 'label' => 'Manage Workflows'],
            ['name' => 'create-workflows', 'module' => 'workflow', 'label' => 'Create Workflows'],
            ['name' => 'edit-workflows', 'module' => 'workflow', 'label' => 'Edit Workflows'],
            ['name' => 'delete-workflows', 'module' => 'workflow', 'label' => 'Delete Workflows'],
            ['name' => 'execute-workflows', 'module' => 'workflow', 'label' => 'Execute Workflows'],
            ['name' => 'view-workflow-logs', 'module' => 'workflow', 'label' => 'View Workflow Logs'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Workflow',
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
