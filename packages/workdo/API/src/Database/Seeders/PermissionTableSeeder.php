<?php

namespace Workdo\API\Database\Seeders;

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
            ['name' => 'manage-api', 'module' => 'api', 'label' => 'Manage API'],

            ['name' => 'manage-api-tokens', 'module' => 'api-tokens', 'label' => 'Manage API Tokens'],
            ['name' => 'manage-any-api-tokens', 'module' => 'api-tokens', 'label' => 'Manage All API Tokens'],
            ['name' => 'manage-own-api-tokens', 'module' => 'api-tokens', 'label' => 'Manage Own API Tokens'],
            ['name' => 'create-api-tokens', 'module' => 'api-tokens', 'label' => 'Create API Tokens'],
            ['name' => 'edit-api-tokens', 'module' => 'api-tokens', 'label' => 'Edit API Tokens'],
            ['name' => 'delete-api-tokens', 'module' => 'api-tokens', 'label' => 'Delete API Tokens'],
            ['name' => 'regenerate-api-tokens', 'module' => 'api-tokens', 'label' => 'Regenerate API Tokens'],
            ['name' => 'revoke-api-tokens', 'module' => 'api-tokens', 'label' => 'Revoke API Tokens'],

            ['name' => 'manage-api-logs', 'module' => 'api-logs', 'label' => 'Manage API Logs'],
            ['name' => 'view-api-logs', 'module' => 'api-logs', 'label' => 'View API Logs'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'API',
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
