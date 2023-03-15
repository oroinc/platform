<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\BatchBundle\DependencyInjection\OroBatchExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroBatchExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroBatchExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());

        self::assertEquals('1 week', $container->getParameter('oro_batch.cleanup_interval'));
        self::assertFalse($container->getParameter('oro_batch.log_batch'));
    }
}
