<?php

namespace Oro\Component\PropertyAccess\Tests\Unit\Fixtures;

class CarOnlyRemover
{
    private $axes;

    public function __construct($axes = null)
    {
        $this->axes = $axes;
    }

    public function removeAxis($axis)
    {
        foreach ($this->axes as $key => $value) {
            if ($value === $axis) {
                unset($this->axes[$key]);

                return;
            }
        }
    }
}
