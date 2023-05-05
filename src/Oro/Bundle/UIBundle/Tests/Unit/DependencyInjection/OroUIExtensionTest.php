<?php
declare(strict_types=1);

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\UIBundle\DependencyInjection\OroUIExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroUIExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroUIExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertEquals(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'organization_name' => ['value' => 'ORO', 'scope' => 'app'],
                        'application_url' => ['value' => 'http://localhost', 'scope' => 'app'],
                        'navbar_position' => ['value' => 'left', 'scope' => 'app'],
                        'quick_create_actions' => ['value' => 'current_page', 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_ui')
        );
    }
}
