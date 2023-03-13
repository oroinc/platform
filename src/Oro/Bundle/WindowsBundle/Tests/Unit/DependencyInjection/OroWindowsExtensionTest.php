<?php

namespace Oro\Bundle\WindowsBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\WindowsBundle\DependencyInjection\OroWindowsExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroWindowsExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroWindowsExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
