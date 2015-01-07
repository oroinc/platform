<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ChainVirtualRelationProvider;

class ChainVirtualRelationProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChainVirtualRelationProvider */
    protected $chainProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject[] */
    protected $providers = [];

    protected function setUp()
    {
        $this->chainProvider = new ChainVirtualRelationProvider();

        $highPriorityProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface')
            ->setMockClassName('HighPriorityVirtualRelationProvider')
            ->getMock();
        $lowPriorityProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface')
            ->setMockClassName('LowPriorityVirtualRelationProvider')
            ->getMock();

        $this->chainProvider->addProvider($lowPriorityProvider);
        $this->chainProvider->addProvider($highPriorityProvider, -10);

        $this->providers = [$highPriorityProvider, $lowPriorityProvider];
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

    public function testGetVirtualRelations()
    {
        $firstRelation = [
            'testField1' => [
                'relation_type' => 'manyToOne',
                'related_entity_name' => 'testClassRelated',
                'query' => [
                    'join' => [
                        'left' => [
                            [
                                'join' => 'testClassRelated',
                                'alias' => 'trackingEvent',
                                'conditionType' => 'WITH',
                                'condition' => 'trackingEvent.code = entity.code'
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
                                'alias' => 'trackingEvent',
                                'conditionType' => 'WITH',
                                'condition' => 'trackingEvent.code = entity.code'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->providers[0]
            ->expects($this->once())
            ->method('getVirtualRelations')
            ->with('testClass')
            ->will(
                $this->returnValue($firstRelation)
            );
        $this->providers[1]
            ->expects($this->once())
            ->method('getVirtualRelations')
            ->with('testClass')
            ->will($this->returnValue($secondRelation));

        $this->assertEquals(
            array_merge($firstRelation, $secondRelation),
            $this->chainProvider->getVirtualRelations('testClass')
        );
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
                        'alias' => 'trackingEvent',
                        'conditionType' => 'WITH',
                        'condition' => 'trackingEvent.code = entity.code'
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
     * @expectedExceptionMessage A query for relation "testField1" in class "stdClass" was not found.
     */
    public function testGetVirtualRelationQueryException()
    {
        $className = 'stdClass';
        $fieldName = 'testField1';
        $this->chainProvider->getVirtualRelationQuery($className, $fieldName);
    }
}
