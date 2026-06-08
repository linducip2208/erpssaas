<?php

namespace Workdo\SideMenuBuilder\Database\Seeders;

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
            ['name' => 'manage-side-menu-builder', 'module' => 'side-menu-builder', 'label' => 'Manage SideMenuBuilder'],

            ['name' => 'manage-custom-menus', 'module' => 'custom-menus', 'label' => 'Manage CustomMenus'],
            ['name' => 'manage-any-custom-menus', 'module' => 'custom-menus', 'label' => 'Manage All CustomMenus'],
            ['name' => 'manage-own-custom-menus', 'module' => 'custom-menus', 'label' => 'Manage Own CustomMenus'],
            ['name' => 'create-custom-menus', 'module' => 'custom-menus', 'label' => 'Create CustomMenus'],
            ['name' => 'edit-custom-menus', 'module' => 'custom-menus', 'label' => 'Edit CustomMenus'],
            ['name' => 'delete-custom-menus', 'module' => 'custom-menus', 'label' => 'Delete CustomMenus'],
            ['name' => 'reorder-custom-menus', 'module' => 'custom-menus', 'label' => 'Reorder CustomMenus'],
            ['name' => 'view-custom-menus', 'module' => 'custom-menus', 'label' => 'View CustomMenus'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'SideMenuBuilder',
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
