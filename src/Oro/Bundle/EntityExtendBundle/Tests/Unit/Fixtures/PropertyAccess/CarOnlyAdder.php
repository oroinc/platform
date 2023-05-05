<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\PropertyAccess;

class CarOnlyAdder
{
    private $axes;

    public function __construct($axes = null)
    {
        $this->axes = $axes;
    }

    // In the test, use a name that StringUtil can't uniquely singularify
    public function addAxis($axis)
    {
        $this->axes[] = $axis;
    }
}
