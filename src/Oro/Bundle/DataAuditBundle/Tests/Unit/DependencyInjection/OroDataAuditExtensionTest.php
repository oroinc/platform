<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DataAuditBundle\DependencyInjection\OroDataAuditExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroDataAuditExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroDataAuditExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
