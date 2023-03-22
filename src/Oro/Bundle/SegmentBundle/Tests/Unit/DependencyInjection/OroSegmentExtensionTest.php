<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SegmentBundle\DependencyInjection\OroSegmentExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSegmentExtensionTest extends \PHPUnit\Framework\TestCase
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
