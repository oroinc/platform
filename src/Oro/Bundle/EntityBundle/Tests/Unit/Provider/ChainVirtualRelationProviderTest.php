<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ChainVirtualRelationProvider;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class ChainVirtualRelationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainVirtualRelationProvider */
    private $chainProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject[] */
    private $providers = [];

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    protected function setUp()
    {
        $highPriorityProvider = $this->getMockBuilder(VirtualRelationProviderInterface::class)
            ->setMockClassName('HighPriorityVirtualRelationProvider')
            ->getMock();
        $lowPriorityProvider = $this->getMockBuilder(VirtualRelationProviderInterface::class)
            ->setMockClassName('LowPriorityVirtualRelationProvider')
            ->getMock();

        $this->providers = [$highPriorityProvider, $lowPriorityProvider];
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->chainProvider = new ChainVirtualRelationProvider($this->providers, $this->configProvider);
    }

    public function testIsVirtualRelationByLowPriorityProvider()
    {
        $this->providers[0]
            ->expects($this->once())
            ->method('isVirtualRelation')
            ->with('testClass', 'testField')
            ->will($this->returnValue(true));
        $this->providers[1]
            ->expects($this->never())
            ->method('isVirtualRelation');

        $this->assertTrue($this->chainProvider->isVirtualRelation('testClass', 'testField'));
    }

    public function testIsVirtualRelationByHighPriorityProvider()
    {
        $this->providers[0]
            ->expects($this->once())
            ->method('isVirtualRelation')
            ->with('testClass', 'testField')
            ->will($this->returnValue(false));
        $this->providers[1]
            ->expects($this->once())
            ->method('isVirtualRelation')
            ->with('testClass', 'testField')
            ->will($this->returnValue(true));

        $this->assertTrue($this->chainProvider->isVirtualRelation('testClass', 'testField'));
    }

    public function testIsVirtualRelationNone()
    {
        $this->providers[0]
            ->expects($this->once())
            ->method('isVirtualRelation')
            ->with('testClass', 'testField')
            ->will($this->returnValue(false));
        $this->providers[1]
            ->expects($this->once())
            ->method('isVirtualRelation')
            ->with('testClass', 'testField')
            ->will($this->returnValue(false));

        $this->assertFalse($this->chainProvider->isVirtualRelation('testClass', 'testField'));
    }

    public function testIsVirtualRelationWithoutChildProviders()
    {
        $chainProvider = new ChainVirtualRelationProvider([], $this->configProvider);
        $this->assertFalse($chainProvider->isVirtualRelation('testClass', 'testField'));
    }

    public function testGetVirtualRelations()
    {
        $entityClass = 'testClass';

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);
        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $firstRelation = [
            'testField1' => [
                'relation_type' => 'manyToOne',
                'related_entity_name' => 'testClassRelated',
                'query' => [
                    'join' => [
                        'left' => [
                            [
                                'join' => 'testClassRelated',
                                'alias' => 'testAlias',
                                'conditionType' => 'WITH',
                                'condition' => 'testAlias.code = entity.code'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $secondRelation = [
            'testField2' => [
                'relation_type' => 'manyToOne',
                'related_entity_name' => 'testClassRelated2',
                'query' => [
                    'join' => [
                        'left' => [
                            [
                                'join' => 'testClassRelated2',
                                'alias' => 'testAlias',
                                'conditionType' => 'WITH',
                                'condition' => 'testAlias.code = entity.code'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->providers[0]->expects($this->once())
            ->method('getVirtualRelations')
            ->with($entityClass)
            ->willReturn($firstRelation);
        $this->providers[1]->expects($this->once())
            ->method('getVirtualRelations')
            ->with($entityClass)
            ->willReturn($secondRelation);

        $this->assertEquals(
            array_merge($firstRelation, $secondRelation),
            $this->chainProvider->getVirtualRelations($entityClass)
        );
    }

    public function testGetVirtualRelationsForNotAccessibleEntity()
    {
        $entityClass = 'testClass';

        $entityConfig = new Config(
            $this->createMock(ConfigIdInterface::class),
            ['is_extend' => true, 'state' => ExtendScope::STATE_NEW]
        );
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClass)
            ->willReturn($entityConfig);

        $this->providers[0]->expects($this->never())
            ->method('getVirtualRelations');
        $this->providers[1]->expects($this->never())
            ->method('getVirtualRelations');

        $this->assertSame(
            [],
            $this->chainProvider->getVirtualRelations($entityClass)
        );
    }

    public function testGetVirtualRelationsWithoutChildProviders()
    {
        $chainProvider = new ChainVirtualRelationProvider([], $this->configProvider);
        $this->assertSame([], $chainProvider->getVirtualRelations('testClass'));
    }

    public function testGetVirtualRelationQuery()
    {
        $className = 'stdClass';
        $fieldName = 'testField1';

        $query = [
            'join' => [
                'left' => [
                    [
                        'join' => 'testClassRelated',
                        'alias' => 'testAlias',
                        'conditionType' => 'WITH',
                        'condition' => 'testAlias.code = entity.code'
                    ]
                ]
            ]
        ];

        $this->providers[0]->expects($this->once())
            ->method('isVirtualRelation')
            ->with($className, $fieldName)
            ->will($this->returnValue(true));
        $this->providers[0]->expects($this->once())
            ->method('getVirtualRelationQuery')
            ->with($className, $fieldName)
            ->will($this->returnValue($query));
        $this->providers[1]->expects($this->never())
            ->method('isVirtualRelation')
            ->with($className, $fieldName);

        $this->assertEquals($query, $this->chainProvider->getVirtualRelationQuery($className, $fieldName));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage A query for relation "testField" in class "stdClass" was not found.
     */
    public function testGetVirtualRelationQueryException()
    {
        $this->chainProvider->getVirtualRelationQuery('stdClass', 'testField');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage A query for relation "testField" in class "stdClass" was not found.
     */
    public function testGetVirtualRelationQueryWithoutChildProviders()
    {
        $chainProvider = new ChainVirtualRelationProvider([], $this->configProvider);
        $chainProvider->getVirtualRelationQuery('stdClass', 'testField');
    }
}
