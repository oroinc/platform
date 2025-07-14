<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AddressBundle\DependencyInjection\OroAddressExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroAddressExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroAddressExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
