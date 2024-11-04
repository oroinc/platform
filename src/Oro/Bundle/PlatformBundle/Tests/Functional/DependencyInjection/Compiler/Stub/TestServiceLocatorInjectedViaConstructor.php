<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional\DependencyInjection\Compiler\Stub;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class TestServiceLocatorInjectedViaConstructor extends TestServiceLocatorInjection
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'router' => RouterInterface::class,
            LoggerInterface::class
        ]);
    }
}
