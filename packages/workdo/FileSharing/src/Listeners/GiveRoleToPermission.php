<?php

namespace Workdo\FileSharing\Listeners;

use App\Events\GivePermissionToRole;
use Workdo\FileSharing\Models\FileSharingUtility;

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
            if (in_array("FileSharing", $user_module)) {
                FileSharingUtility::GivePermissionToRoles($role_id, $rolename);
            }
        }
    }
}
