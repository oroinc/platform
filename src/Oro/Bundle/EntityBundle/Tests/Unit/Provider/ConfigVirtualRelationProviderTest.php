<?php
namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ConfigVirtualRelationProvider;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider;

class ConfigVirtualRelationProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigVirtualRelationProvider */
    private $configVirtualRelationProvider;

    /** @var array configuration */
    private $configurationVirtualRelation;

    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityHierarchyProvider $entityHierarchyProvider */
        $entityHierarchyProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider')
            ->disableOriginalConstructor()
            ->getMock();

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
                    'query' => [
                        'select' => ['select expression'],
                        'join' => ['join expression']
                    ]
                ],
                'virtual_relation2' => [
                    'relation_type' => 'oneToMany',
                    'related_entity_name' => 'OtherEntity',
                    'target_join_alias' => 'join_alias',
                    'query' => [
                        'select' => ['select expression'],
                        'join' => ['join expression']
                    ]
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

    public function testGetTargetJoinAlias()
    {
        $this->assertEquals(
            $this->configurationVirtualRelation['AbstractEntity']['virtual_relation2']['target_join_alias'],
            $this->configVirtualRelationProvider->getTargetJoinAlias('TestEntity', 'virtual_relation2')
        );

        $this->assertEquals(
            null,
            $this->configVirtualRelationProvider->getTargetJoinAlias('TestEntity', 'virtual_relation')
        );
    }
}
