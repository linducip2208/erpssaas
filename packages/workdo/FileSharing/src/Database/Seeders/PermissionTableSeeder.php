<?php

namespace Workdo\FileSharing\Database\Seeders;

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
            ['name' => 'manage-file-sharing', 'module' => 'file-sharing', 'label' => 'Manage File Sharing'],
            ['name' => 'manage-own-shared-folders', 'module' => 'file-sharing', 'label' => 'Manage Own Shared Folders'],
            ['name' => 'manage-any-shared-folders', 'module' => 'file-sharing', 'label' => 'Manage All Shared Folders'],
            ['name' => 'view-shared-folders', 'module' => 'file-sharing', 'label' => 'View Shared Folders'],
            ['name' => 'create-shared-folders', 'module' => 'file-sharing', 'label' => 'Create Shared Folders'],
            ['name' => 'edit-shared-folders', 'module' => 'file-sharing', 'label' => 'Edit Shared Folders'],
            ['name' => 'delete-shared-folders', 'module' => 'file-sharing', 'label' => 'Delete Shared Folders'],

            ['name' => 'manage-own-shared-files', 'module' => 'file-sharing', 'label' => 'Manage Own Shared Files'],
            ['name' => 'manage-any-shared-files', 'module' => 'file-sharing', 'label' => 'Manage All Shared Files'],
            ['name' => 'create-shared-files', 'module' => 'file-sharing', 'label' => 'Create Shared Files'],
            ['name' => 'delete-shared-files', 'module' => 'file-sharing', 'label' => 'Delete Shared Files'],
            ['name' => 'download-shared-files', 'module' => 'file-sharing', 'label' => 'Download Shared Files'],

            ['name' => 'manage-folder-permissions', 'module' => 'file-sharing', 'label' => 'Manage Folder Permissions'],
            ['name' => 'create-folder-permissions', 'module' => 'file-sharing', 'label' => 'Create Folder Permissions'],
            ['name' => 'delete-folder-permissions', 'module' => 'file-sharing', 'label' => 'Delete Folder Permissions'],

            ['name' => 'manage-shared-links', 'module' => 'file-sharing', 'label' => 'Manage Shared Links'],
            ['name' => 'create-shared-links', 'module' => 'file-sharing', 'label' => 'Create Shared Links'],
            ['name' => 'delete-shared-links', 'module' => 'file-sharing', 'label' => 'Delete Shared Links'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'FileSharing',
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
