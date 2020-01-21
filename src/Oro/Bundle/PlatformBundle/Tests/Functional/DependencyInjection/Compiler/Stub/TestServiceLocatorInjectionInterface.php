<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional\DependencyInjection\Compiler\Stub;

use Psr\Container\ContainerInterface;

interface TestServiceLocatorInjectionInterface
{
    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface;

    public function validateInjectedServiceLocator(): void;
}
