<?php

namespace Workdo\AIDocument\Database\Seeders;

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
            ['name' => 'manage-ai-document', 'module' => 'aidocument', 'label' => 'Manage AI Document'],
            ['name' => 'create-ai-document', 'module' => 'aidocument', 'label' => 'Create AI Document'],
            ['name' => 'edit-ai-document', 'module' => 'aidocument', 'label' => 'Edit AI Document'],
            ['name' => 'delete-ai-document', 'module' => 'aidocument', 'label' => 'Delete AI Document'],
            ['name' => 'manage-ai-document-settings', 'module' => 'aidocument', 'label' => 'Manage AI Document Settings'],
        ];

        $companyRole = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module'     => $perm['module'],
                    'label'      => $perm['label'],
                    'add_on'     => 'AIDocument',
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
