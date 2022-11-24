<?php
namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Oro\Bundle\EntityBundle\Configuration\EntityConfigurationProvider;
use Oro\Bundle\EntityBundle\Provider\ConfigVirtualRelationProvider;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;

class ConfigVirtualRelationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigVirtualRelationProvider */
    private $virtualRelationProvider;

    /** @var array */
    private $virtualRelationsConfig;

    protected function setUp(): void
    {
        $hierarchy = [
            'TestEntity1' => ['AbstractEntity']
        ];
        $this->virtualRelationsConfig = [
            'AbstractEntity' => [
                'virtual_relation' => [
                    'relation_type' => 'oneToMany',
                    'related_entity_name' => 'OtherEntity',
                    'target_join_alias' => 'configured_alias',
                    'query' => [
                        'select' => ['select expression'],
                        'join' => ['inner' => [['join' => 'join', 'alias' => 'configured_alias']]]
                    ]
                ],
                'single_join' => [
                    'relation_type' => 'oneToMany',
                    'related_entity_name' => 'OtherEntity',
                    'query' => [
                        'select' => ['select expression'],
                        'join' => ['inner' => [['join' => 'join', 'alias' => 'alias']]]
                    ]
                ],
                'single_join_without_alias' => [
                    'relation_type' => 'oneToMany',
                    'related_entity_name' => 'OtherEntity',
                    'query' => [
                        'select' => ['select expression'],
                        'join' => ['inner' => [['join' => 'join']]]
                    ]
                ],
                'multiple_joins' => [
                    'relation_type' => 'oneToMany',
                    'related_entity_name' => 'OtherEntity',
                    'query' => [
                        'select' => ['select expression'],
                        'join' => [
                            'inner' => [['join' => 'join', 'alias' => 'alias']],
                            'left' => [['join' => 'join2', 'alias' => 'alias2']]
                        ]
                    ]
                ],
                'without_query' => [
                    'relation_type' => 'oneToMany',
                    'related_entity_name' => 'OtherEntity',
                    'query' => []
                ]
            ]
        ];

        $entityHierarchyProvider = $this->createMock(EntityHierarchyProviderInterface::class);
        $entityHierarchyProvider->expects($this->any())
            ->method('getHierarchy')
            ->willReturn($hierarchy);

        $configProvider = $this->createMock(EntityConfigurationProvider::class);
        $configProvider->expects(self::any())
            ->method('getConfiguration')
            ->with(EntityConfiguration::VIRTUAL_RELATIONS)
            ->willReturn($this->virtualRelationsConfig);

        $this->virtualRelationProvider = new ConfigVirtualRelationProvider(
            $entityHierarchyProvider,
            $configProvider
        );
    }

    public function testGetVirtualFields()
    {
        $this->assertEquals(
            $this->virtualRelationsConfig['AbstractEntity'],
            $this->virtualRelationProvider->getVirtualRelations('TestEntity1')
        );
        $this->assertEquals(
            [],
            $this->virtualRelationProvider->getVirtualRelations('EntityWithoutVirtualFields')
        );
    }

    public function testIsVirtualField()
    {
        $this->assertTrue($this->virtualRelationProvider->isVirtualRelation('TestEntity1', 'virtual_relation'));
        $this->assertFalse($this->virtualRelationProvider->isVirtualRelation('TestEntity1', 'non_virtual_field'));
    }

    public function testGetVirtualFieldQuery()
    {
        $this->assertEquals(
            $this->virtualRelationsConfig['AbstractEntity']['virtual_relation']['query'],
            $this->virtualRelationProvider->getVirtualRelationQuery('TestEntity1', 'virtual_relation')
        );
    }

    /**
     * @dataProvider targetJoinAliasProvider
     */
    public function testGetTargetJoinAlias(string $className, string $fieldName, mixed $expected)
    {
        if (is_array($expected)) {
            [$exception, $message] = $expected;
            $this->expectException($exception);
            $this->expectExceptionMessage($message);
        }

        $this->assertEquals(
            $expected,
            $this->virtualRelationProvider->getTargetJoinAlias($className, $fieldName)
        );
    }

    public function targetJoinAliasProvider(): array
    {
        return [
            'not existing' => [
                'TestEntity1',
                'not_existing',
                [\InvalidArgumentException::class, 'Not a virtual relation "TestEntity1::not_existing"']
            ],
            'configured option' => ['TestEntity1', 'virtual_relation', 'configured_alias'],
            'without query' => [
                'TestEntity1',
                'without_query',
                [\InvalidArgumentException::class, 'Query configuration is empty for "TestEntity1::without_query"']
            ],
            'single join' => ['TestEntity1', 'single_join', 'alias'],
            'single join without alias' => [
                'TestEntity1',
                'single_join_without_alias',
                [
                    \InvalidArgumentException::class,
                    'Alias for join is not configured for "TestEntity1::single_join_without_alias"'
                ]
            ],
            'multiple joins' => [
                'TestEntity1',
                'multiple_joins',
                [
                    \InvalidArgumentException::class,
                    'Please configure "target_join_alias" option for "TestEntity1::multiple_joins"'
                ]
            ],
        ];
    }
}
