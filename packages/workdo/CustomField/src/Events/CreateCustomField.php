<?php

namespace Workdo\CustomField\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreateCustomField
{
    use Dispatchable, SerializesModels;

    public $request;
    public $field;

    public function __construct($request, $field)
    {
        $this->request = $request;
        $this->field = $field;
    }
}
