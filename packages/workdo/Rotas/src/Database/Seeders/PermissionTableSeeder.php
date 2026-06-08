<?php

namespace Workdo\Rotas\Database\Seeders;

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
            ['name' => 'manage-rotas', 'module' => 'rotas', 'label' => 'Manage Rotas'],
            ['name' => 'manage-own-rotas', 'module' => 'rotas', 'label' => 'Manage Own Rotas'],
            ['name' => 'manage-any-rotas', 'module' => 'rotas', 'label' => 'Manage All Rotas'],
            ['name' => 'view-rotas', 'module' => 'rotas', 'label' => 'View Rotas'],
            ['name' => 'create-rotas', 'module' => 'rotas', 'label' => 'Create Rotas'],
            ['name' => 'edit-rotas', 'module' => 'rotas', 'label' => 'Edit Rotas'],
            ['name' => 'delete-rotas', 'module' => 'rotas', 'label' => 'Delete Rotas'],

            ['name' => 'manage-rota-templates', 'module' => 'rota-templates', 'label' => 'Manage Rota Templates'],
            ['name' => 'manage-own-rota-templates', 'module' => 'rota-templates', 'label' => 'Manage Own Rota Templates'],
            ['name' => 'manage-any-rota-templates', 'module' => 'rota-templates', 'label' => 'Manage All Rota Templates'],
            ['name' => 'create-rota-templates', 'module' => 'rota-templates', 'label' => 'Create Rota Templates'],
            ['name' => 'edit-rota-templates', 'module' => 'rota-templates', 'label' => 'Edit Rota Templates'],
            ['name' => 'delete-rota-templates', 'module' => 'rota-templates', 'label' => 'Delete Rota Templates'],

            ['name' => 'manage-rota-assignments', 'module' => 'rota-assignments', 'label' => 'Manage Rota Assignments'],
            ['name' => 'manage-own-rota-assignments', 'module' => 'rota-assignments', 'label' => 'Manage Own Assignments'],
            ['name' => 'manage-any-rota-assignments', 'module' => 'rota-assignments', 'label' => 'Manage All Assignments'],
            ['name' => 'create-rota-assignments', 'module' => 'rota-assignments', 'label' => 'Create Assignments'],
            ['name' => 'edit-rota-assignments', 'module' => 'rota-assignments', 'label' => 'Edit Assignments'],
            ['name' => 'delete-rota-assignments', 'module' => 'rota-assignments', 'label' => 'Delete Assignments'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Rotas',
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
