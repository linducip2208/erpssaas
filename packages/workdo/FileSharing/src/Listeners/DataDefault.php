<?php

namespace Workdo\FileSharing\Listeners;

use App\Events\DefaultData;
use Workdo\FileSharing\Models\FileSharingUtility;

class DataDefault
{
    public function __construct()
    {
    }

    public function handle(DefaultData $event)
    {
        $company_id = $event->company_id;
        $user_module = $event->user_module ? explode(',', $event->user_module) : [];
        if (!empty($user_module)) {
            if (in_array("FileSharing", $user_module)) {
                FileSharingUtility::defaultdata($company_id);
            }
        }
    }
}
