<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ChainVirtualRelationProvider;

class ChainVirtualRelationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainVirtualRelationProvider */
    protected $chainProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject[] */
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
