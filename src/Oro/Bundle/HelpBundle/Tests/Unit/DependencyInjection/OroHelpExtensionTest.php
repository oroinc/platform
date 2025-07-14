<?php

namespace Oro\Bundle\HelpBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\HelpBundle\DependencyInjection\OroHelpExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroHelpExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $configs = [
            ['defaults' => ['server' => 'http://server.com']]
        ];

        $extension = new OroHelpExtension();
        $extension->load($configs, $container);

        self::assertNotEmpty($container->getDefinitions());

        self::assertEquals(
            ['server' => 'http://server.com'],
            $container->getDefinition('oro_help.help_link_provider')->getArgument(0)
        );
    }
}
