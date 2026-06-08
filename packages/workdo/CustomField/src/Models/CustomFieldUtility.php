<?php

namespace Workdo\CustomField\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CustomFieldUtility extends Model
{
    public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {
        $staff_permission = [
            'manage-custom-fields',
            'manage-own-custom-field-groups',
            'view-custom-fields',
        ];

        $client_permission = [
            'manage-custom-fields',
            'manage-own-custom-field-groups',
            'create-custom-field-groups',
            'edit-custom-field-groups',
            'view-custom-fields',
        ];

        if ($rolename == 'company') {
            $roles_v = Role::where('name', 'company')->where('id', $role_id)->first();
            if ($roles_v) {
                $all_permissions = Permission::where('add_on', 'CustomField')->get();
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
