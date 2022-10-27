<?php
declare(strict_types=1);

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Stub;

class ValueContainer
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getSomething()
    {
        return $this->value;
    }
}
