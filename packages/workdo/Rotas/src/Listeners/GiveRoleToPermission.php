<?php

namespace Workdo\Rotas\Listeners;

use App\Events\GivePermissionToRole;
use Workdo\Rotas\Models\RotasUtility;

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
            if (in_array("Rotas", $user_module)) {
                RotasUtility::GivePermissionToRoles($role_id, $rolename);
            }
        }
    }
}
