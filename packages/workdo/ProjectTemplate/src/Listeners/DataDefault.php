<?php

namespace Workdo\ProjectTemplate\Listeners;

use App\Events\DefaultData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DataDefault
{
    public function __construct() {}

    public function handle(DefaultData $event)
    {
        $user_module = $event->user_module ? explode(',', $event->user_module) : [];
        if (!empty($user_module) && in_array("ProjectTemplate", $user_module)) {
            // Set any default data when module is activated
        }
    }
}
