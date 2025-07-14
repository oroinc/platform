<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SegmentBundle\DependencyInjection\OroSegmentExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSegmentExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroSegmentExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
