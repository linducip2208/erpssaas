<?php

namespace Workdo\SideMenuBuilder\Listeners;

use App\Events\GivePermissionToRole;
use Workdo\SideMenuBuilder\Models\SideMenuBuilderUtility;

class GiveRoleToPermission
{
    public function __construct()
    {
    }

    public function handle(GivePermissionToRole $event)
    {
        $role_id = $event->role_id;
        $rolename = $event->rolename;
        $user_module = $event->user_module ? explode(',', $event->user_module) : [];
        if (!empty($user_module)) {
            if (in_array("SideMenuBuilder", $user_module)) {
                SideMenuBuilderUtility::GivePermissionToRoles($role_id, $rolename);
            }
        }
    }
}
