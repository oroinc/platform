<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class TestFlashBagDependedService
{
    private FlashBagInterface $flashBag;

    public function __construct(FlashBagInterface $flashBag)
    {
        $this->flashBag = $flashBag;
    }
}
