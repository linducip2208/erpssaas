<?php

namespace Workdo\ProjectTemplate\Listeners;

use App\Events\GivePermissionToRole;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\ProjectTemplate\Models\ProjectTemplate;

class GiveRoleToPermission
{
    public function __construct() {}

    public function handle(GivePermissionToRole $event)
    {
        $role_id = $event->role_id;
        $rolename = $event->rolename;
        $user_module = $event->user_module ? explode(',', $event->user_module) : [];

        if (!empty($user_module) && in_array("ProjectTemplate", $user_module)) {
            ProjectTemplate::GivePermissionToRoles($role_id, $rolename);
        }
    }
}
