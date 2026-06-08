<?php

namespace Workdo\CustomField\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateCustomFieldGroup
{
    use Dispatchable, SerializesModels;

    public $request;
    public $group;

    public function __construct($request, $group)
    {
        $this->request = $request;
        $this->group = $group;
    }
}
