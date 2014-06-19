<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture;

class TestService
{
    protected $returnedValue;

    public function __construct($returnedValue)
    {
        $this->returnedValue = $returnedValue;
    }

    public function testMethod()
    {
        return $this->returnedValue;
    }
}
