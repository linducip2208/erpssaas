<?php

namespace Workdo\CustomField\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DestroyCustomField
{
    use Dispatchable, SerializesModels;

    public $field;

    public function __construct($field)
    {
        $this->field = $field;
    }
}
