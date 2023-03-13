<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ActivityBundle\DependencyInjection\OroActivityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroActivityExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroActivityExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [],
            $container->getDefinition('oro_activity.api.activity_association_provider')
                ->getArgument('$activityAssociationNames')
        );
    }

    public function testLoadWithCustomConfigs(): void
    {
        $container = new ContainerBuilder();
        $configs = [
            ['api' => ['activity_association_names' => ['Test\Activity1' => 'association1']]],
            ['api' => ['activity_association_names' => ['Test\Activity2' => 'association2']]],
            ['api' => ['activity_association_names' => ['Test\Activity1' => 'association1new']]],
        ];

        $extension = new OroActivityExtension();
        $extension->load($configs, $container);

        self::assertSame(
            ['Test\Activity1' => 'association1new', 'Test\Activity2' => 'association2'],
            $container->getDefinition('oro_activity.api.activity_association_provider')
                ->getArgument('$activityAssociationNames')
        );
    }
}
