<?php

namespace Workdo\ProjectTemplate\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProjectTemplate extends Model
{
    protected $table = 'project_templates';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tasks()
    {
        return $this->hasMany(ProjectTemplateTask::class, 'template_id')->orderBy('order');
    }

    public function milestones()
    {
        return $this->hasMany(ProjectTemplateMilestone::class, 'template_id')->orderBy('order');
    }

    public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {
        $staff_permission = [
            'manage-project-template',
            'create-project-template',
            'edit-project-template',
        ];

        $client_permission = [
            'manage-project-template',
        ];

        if ($rolename == 'staff') {
            $roles_v = Role::where('name', 'staff')->where('id', $role_id)->first();
            foreach ($staff_permission as $permission_v) {
                $permission = Permission::where('name', $permission_v)->first();
                if (!empty($permission) && $roles_v && !$roles_v->hasPermissionTo($permission_v)) {
                    $roles_v->givePermissionTo($permission);
                }
            }
        }

        if ($rolename == 'client') {
            $roles_v = Role::where('name', 'client')->where('id', $role_id)->first();
            foreach ($client_permission as $permission_v) {
                $permission = Permission::where('name', $permission_v)->first();
                if (!empty($permission) && $roles_v && !$roles_v->hasPermissionTo($permission_v)) {
                    $roles_v->givePermissionTo($permission);
                }
            }
        }
    }
}
