<?php

namespace Workdo\FileSharing\Providers;

use Workdo\FileSharing\Listeners\DataDefault;
use Workdo\FileSharing\Listeners\GiveRoleToPermission;
use App\Events\DefaultData;
use App\Events\GivePermissionToRole;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        DefaultData::class => [
            DataDefault::class,
        ],
        GivePermissionToRole::class => [
            GiveRoleToPermission::class,
        ],
    ];
}
