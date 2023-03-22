<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\QueryDesignerBundle\DependencyInjection\OroQueryDesignerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroQueryDesignerExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroQueryDesignerExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'conditions_group_merge_same_entity_conditions' => ['value' => true, 'scope' => 'app']
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_query_designer')
        );
    }
}
