<?php

namespace Workdo\CustomField\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DestroyCustomFieldGroup
{
    use Dispatchable, SerializesModels;

    public $group;

    public function __construct($group)
    {
        $this->group = $group;
    }
}
