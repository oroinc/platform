<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DataGridBundle\DependencyInjection\OroDataGridExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroDataGridExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroDataGridExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'default_per_page' => ['value' => 25, 'scope' => 'app'],
                        'full_screen_layout_enabled' => ['value' => true, 'scope' => 'app'],
                        'row_link_enabled' => ['value' => true, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_data_grid')
        );
    }
}
