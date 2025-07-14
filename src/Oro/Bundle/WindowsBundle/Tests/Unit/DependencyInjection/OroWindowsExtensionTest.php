<?php

namespace Oro\Bundle\WindowsBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\WindowsBundle\DependencyInjection\OroWindowsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroWindowsExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroWindowsExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
