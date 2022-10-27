<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SidebarBundle\Controller\Api\Rest\SidebarController;
use Oro\Bundle\SidebarBundle\Controller\Api\Rest\WidgetController;
use Oro\Bundle\SidebarBundle\DependencyInjection\OroSidebarExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSidebarExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroSidebarExtension();
        $extension->load([], $container);

        self::assertEquals(
            [
                [
                    'settings' => [
                        'resolved'             => true,
                        'sidebar_left_active'  => ['value' => false, 'scope' => 'app'],
                        'sidebar_right_active' => ['value' => true, 'scope' => 'app']
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_sidebar')
        );
        self::assertTrue($container->hasDefinition(SidebarController::class));
        self::assertTrue($container->hasDefinition(WidgetController::class));
    }
}
