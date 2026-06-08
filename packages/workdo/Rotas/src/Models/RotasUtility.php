<?php

namespace Workdo\Rotas\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RotasUtility extends Model
{
    public static function defaultdata($company_id = null)
    {
    }

    public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {
        $staff_permission = [
            'manage-rotas',
            'manage-own-rotas',
            'view-rotas',
            'create-rotas',
            'edit-rotas',
            'delete-rotas',
            'manage-rota-templates',
            'manage-rota-assignments',
        ];

        $client_permission = [
            'manage-rotas',
            'manage-own-rotas',
            'view-rotas',
            'manage-rota-templates',
            'manage-rota-assignments',
        ];

        if ($rolename == 'company') {
            $roles_v = Role::where('name', 'company')->where('id', $role_id)->first();
            if ($roles_v) {
                $all_permissions = Permission::where('add_on', 'Rotas')->get();
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
