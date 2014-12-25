<?php
namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ConfigVirtualRelationProvider;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider;

class ConfigVirtualRelationProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigVirtualRelationProvider */
    private $configVirtualRelationProvider;

    /** @var array configuration */
    private $configurationVirtualFields;

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

        $this->configurationVirtualFields = [
            'AbstractEntity' => [
                'virtual_field' => [
                    'query' => [
                        'select' => ['select expression config ...'],
                        'join' => ['join expression config ...']
                    ]
                ]
            ]
        ];

        $this->configVirtualRelationProvider = new ConfigVirtualRelationProvider(
            $entityHierarchyProvider,
            $this->configurationVirtualFields
        );
    }

    public function testGetVirtualFields()
    {
        $this->assertEquals(
            ['virtual_field'],
            $this->configVirtualRelationProvider->getVirtualRelations('TestEntity')
        );
        $this->assertEquals(
            [],
            $this->configVirtualRelationProvider->getVirtualRelations('EntityWithoutVirtualFields')
        );
    }

    public function testIsVirtualField()
    {
        $this->assertTrue($this->configVirtualRelationProvider->isVirtualRelation('TestEntity', 'virtual_field'));
        $this->assertFalse($this->configVirtualRelationProvider->isVirtualRelation('TestEntity', 'non_virtual_field'));
    }

    public function testGetVirtualFieldQuery()
    {
        $this->assertEquals(
            $this->configurationVirtualFields['AbstractEntity']['virtual_field']['query'],
            $this->configVirtualRelationProvider->getVirtualRelationQuery('TestEntity', 'virtual_field')
        );
    }
}
