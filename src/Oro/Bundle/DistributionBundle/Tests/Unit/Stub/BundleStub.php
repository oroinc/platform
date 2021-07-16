<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Stub;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class BundleStub extends Bundle
{
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
