<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Symfony\Component\HttpFoundation\RequestStack;

class TestFlashBagDependedService
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }
}
