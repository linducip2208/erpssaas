<?php

namespace Workdo\FileSharing\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FileSharingUtility extends Model
{
    public static function defaultdata($company_id = null)
    {
    }

    public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {
        $staff_permission = [
            'manage-file-sharing',
            'manage-own-shared-folders',
            'view-shared-folders',
            'manage-own-shared-files',
            'create-shared-files',
            'delete-shared-files',
            'manage-shared-links',
        ];

        $client_permission = [
            'manage-file-sharing',
            'manage-own-shared-folders',
            'view-shared-folders',
            'manage-own-shared-files',
            'create-shared-files',
            'delete-shared-files',
            'manage-shared-links',
        ];

        if ($rolename == 'company') {
            $roles_v = Role::where('name', 'company')->where('id', $role_id)->first();
            if ($roles_v) {
                $all_permissions = Permission::where('add_on', 'FileSharing')->get();
                foreach ($all_permissions as $permission) {
                    if (!$roles_v->hasPermissionTo($permission->name)) {
                        $roles_v->givePermissionTo($permission);
                    }
                }
            }
        }

        if ($rolename == 'staff') {
            $roles_v = Role::where('name', 'staff')->where('id', $role_id)->first();
            if ($roles_v) {
                foreach ($staff_permission as $permission_v) {
                    $permission = Permission::where('name', $permission_v)->first();
                    if (!empty($permission)) {
                        if (!$roles_v->hasPermissionTo($permission_v)) {
                            $roles_v->givePermissionTo($permission);
                        }
                    }
                }
            }
        }

        if ($rolename == 'client') {
            $roles_v = Role::where('name', 'client')->where('id', $role_id)->first();
            if ($roles_v) {
                foreach ($client_permission as $permission_v) {
                    $permission = Permission::where('name', $permission_v)->first();
                    if (!empty($permission)) {
                        if (!$roles_v->hasPermissionTo($permission_v)) {
                            $roles_v->givePermissionTo($permission);
                        }
                    }
                }
            }
        }
    }
}
