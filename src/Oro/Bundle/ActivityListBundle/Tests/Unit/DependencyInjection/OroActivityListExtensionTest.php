<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ActivityListBundle\DependencyInjection\OroActivityListExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroActivityListExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroActivityListExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'sorting_field' => ['value' => 'updatedAt', 'scope' => 'app'],
                        'sorting_direction' => ['value' => 'DESC', 'scope' => 'app'],
                        'per_page' => ['value' => 10, 'scope' => 'app'],
                        'grouping' => ['value' => true, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_activity_list')
        );
    }
}
