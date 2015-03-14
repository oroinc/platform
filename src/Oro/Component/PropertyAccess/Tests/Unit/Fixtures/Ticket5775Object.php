<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess\Fixtures;

class Ticket5775Object
{
    private $property;

    public function getProperty()
    {
        return $this->property;
    }

    private function setProperty()
    {
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }
}
