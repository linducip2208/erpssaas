<?php

namespace Workdo\Rotas\Listeners;

use App\Events\DefaultData;
use Workdo\Rotas\Models\RotasUtility;

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
            if (in_array("Rotas", $user_module)) {
                RotasUtility::defaultdata($company_id);
            }
        }
    }
}
