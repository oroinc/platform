<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional\DependencyInjection\Compiler\Stub;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class TestServiceLocatorInjectedViaConstructor extends TestServiceLocatorInjection
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'router' => RouterInterface::class,
            LoggerInterface::class
        ]);
    }
}
