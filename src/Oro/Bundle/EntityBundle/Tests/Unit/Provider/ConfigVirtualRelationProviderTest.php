<?php
namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ConfigVirtualRelationProvider;

class ConfigVirtualRelationProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigVirtualRelationProvider */
    private $configVirtualRelationProvider;

    /** @var array configuration */
    private $configurationVirtualRelation;

    protected function setUp()
    {
        $entityHierarchyProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface');

        $hierarchy = ['TestEntity' => ['AbstractEntity']];
        $entityHierarchyProvider
            ->expects($this->any())
            ->method('getHierarchy')
            ->will($this->returnValue($hierarchy));

        $this->configurationVirtualRelation = [
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

        $this->configVirtualRelationProvider = new ConfigVirtualRelationProvider(
            $entityHierarchyProvider,
            $this->configurationVirtualRelation
        );
    }

    public function testGetVirtualFields()
    {
        $this->assertEquals(
            $this->configurationVirtualRelation['AbstractEntity'],
            $this->configVirtualRelationProvider->getVirtualRelations('TestEntity')
        );
        $this->assertEquals(
            [],
            $this->configVirtualRelationProvider->getVirtualRelations('EntityWithoutVirtualFields')
        );
    }

    public function testIsVirtualField()
    {
        $this->assertTrue($this->configVirtualRelationProvider->isVirtualRelation('TestEntity', 'virtual_relation'));
        $this->assertFalse($this->configVirtualRelationProvider->isVirtualRelation('TestEntity', 'non_virtual_field'));
    }

    public function testGetVirtualFieldQuery()
    {
        $this->assertEquals(
            $this->configurationVirtualRelation['AbstractEntity']['virtual_relation']['query'],
            $this->configVirtualRelationProvider->getVirtualRelationQuery('TestEntity', 'virtual_relation')
        );
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param mixed  $expected
     *
     * @dataProvider targetJoinAliasProvider
     */
    public function testGetTargetJoinAlias($className, $fieldName, $expected)
    {
        if (is_array($expected)) {
            list($exception, $message) = $expected;
            $this->setExpectedException($exception, $message);
        }

        $this->assertEquals(
            $expected,
            $this->configVirtualRelationProvider->getTargetJoinAlias($className, $fieldName)
        );
    }

    /**
     * @return array
     */
    public function targetJoinAliasProvider()
    {
        return [
            'not existing' => [
                'TestEntity',
                'not_existing',
                ['\InvalidArgumentException', 'Not a virtual relation "TestEntity::not_existing"']
            ],
            'configured option' => ['TestEntity', 'virtual_relation', 'configured_alias'],
            'without query' => [
                'TestEntity',
                'without_query',
                ['\InvalidArgumentException', 'Query configuration is empty for "TestEntity::without_query"']
            ],
            'single join' => ['TestEntity', 'single_join', 'alias'],
            'single join without alias' => [
                'TestEntity',
                'single_join_without_alias',
                [
                    '\InvalidArgumentException',
                    'Alias for join is not configured for "TestEntity::single_join_without_alias"'
                ]
            ],
            'multiple joins' => [
                'TestEntity',
                'multiple_joins',
                [
                    '\InvalidArgumentException',
                    'Please configure "target_join_alias" option for "TestEntity::multiple_joins"'
                ]
            ],
        ];
    }
}
