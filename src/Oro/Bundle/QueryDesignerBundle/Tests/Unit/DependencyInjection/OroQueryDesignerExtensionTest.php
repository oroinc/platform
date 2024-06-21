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
        self::assertSame([], $container->getParameter('oro_query_designer.collapsed_associations'));
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

    public function testLoadCollapsedAssociations(): void
    {
        $container = new ContainerBuilder();

        $configs = [
            [
                'collapsed_associations' => [
                    'Test\Entity1' => ['virtual_fields' => ['field1'], 'search_fields' => ['field1', 'field2']],
                    'Test\Entity2' => ['virtual_fields' => ['field1'], 'search_fields' => ['field1', 'field2']],
                    'Test\Entity3' => ['virtual_fields' => ['field1'], 'search_fields' => ['field1', 'field2']]
                ]
            ],
            [
                'collapsed_associations' => [
                    'Test\Entity4' => ['virtual_fields' => ['name'], 'search_fields' => ['label']],
                    'Test\Entity2' => ['virtual_fields' => ['field3']],
                    'Test\Entity3' => ['search_fields' => ['field3']]
                ]
            ]
        ];

        $extension = new OroQueryDesignerExtension();
        $extension->load($configs, $container);

        self::assertSame(
            [
                'Test\Entity1' => ['virtual_fields' => ['field1'], 'search_fields' => ['field1', 'field2']],
                'Test\Entity2' => ['virtual_fields' => ['field3'], 'search_fields' => ['field1', 'field2']],
                'Test\Entity3' => ['virtual_fields' => ['field1'], 'search_fields' => ['field3']],
                'Test\Entity4' => ['virtual_fields' => ['name'], 'search_fields' => ['label']]
            ],
            $container->getParameter('oro_query_designer.collapsed_associations')
        );
    }
}
