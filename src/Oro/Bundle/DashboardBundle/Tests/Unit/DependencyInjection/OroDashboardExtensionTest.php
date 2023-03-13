<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DashboardBundle\DependencyInjection\OroDashboardExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroDashboardExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroDashboardExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
