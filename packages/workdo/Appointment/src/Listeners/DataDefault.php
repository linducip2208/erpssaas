<?php

namespace Workdo\Appointment\Listeners;

use App\Events\DefaultData;
use Workdo\Appointment\Models\AppointmentUtility;

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
            if (in_array("Appointment", $user_module)) {
                AppointmentUtility::defaultdata($company_id);
            }
        }
    }
}
