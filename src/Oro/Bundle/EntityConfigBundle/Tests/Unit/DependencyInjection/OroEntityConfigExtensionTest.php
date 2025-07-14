<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\OroEntityConfigExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroEntityConfigExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroEntityConfigExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
