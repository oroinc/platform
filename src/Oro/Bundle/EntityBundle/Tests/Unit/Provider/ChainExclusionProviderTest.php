<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ChainExclusionProvider;

class ChainExclusionProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ChainExclusionProvider */
    protected $chainProvider;

    /** @var  \PHPUnit_Framework_MockObject_MockObject[] */
    protected $providers = [];

    protected function setUp()
    {
        $this->chainProvider = new ChainExclusionProvider();

        $highPriorityProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface')
            ->setMockClassName('HighPriorityExclusionProvider')
            ->getMock();
        $lowPriorityProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface')
            ->setMockClassName('LowPriorityExclusionProvider')
            ->getMock();

        $this->chainProvider->addProvider($highPriorityProvider);
        $this->chainProvider->addProvider($lowPriorityProvider);

        $this->providers = [$highPriorityProvider, $lowPriorityProvider];
    }

    public function testIsIgnoredEntityByLowPriorityProvider()
    {
        $this->providers[0]
            ->expects($this->once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->will($this->returnValue(true));
        $this->providers[1]
            ->expects($this->never())
            ->method('isIgnoredEntity');

        $this->assertTrue($this->chainProvider->isIgnoredEntity('testClass'));
    }

    public function testIsIgnoredEntityByHighPriorityProvider()
    {
        $this->providers[0]
            ->expects($this->once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->will($this->returnValue(false));
        $this->providers[1]
            ->expects($this->once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->will($this->returnValue(true));

        $this->assertTrue($this->chainProvider->isIgnoredEntity('testClass'));
    }

    public function testIsIgnoredEntityNone()
    {
        $this->providers[0]
            ->expects($this->once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->will($this->returnValue(false));
        $this->providers[1]
            ->expects($this->once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->will($this->returnValue(false));

        $this->assertFalse($this->chainProvider->isIgnoredEntity('testClass'));
    }

    public function testIsIgnoredFieldByLowPriorityProvider()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->providers[0]
            ->expects($this->once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->will($this->returnValue(true));
        $this->providers[1]
            ->expects($this->never())
            ->method('isIgnoredField');

        $this->assertTrue($this->chainProvider->isIgnoredField($metadata, 'fieldName'));
    }

    public function testIsIgnoredFieldByHighPriorityProvider()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->providers[0]
            ->expects($this->once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->will($this->returnValue(false));
        $this->providers[1]
            ->expects($this->once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->will($this->returnValue(true));

        $this->assertTrue($this->chainProvider->isIgnoredField($metadata, 'fieldName'));
    }

    public function testIsIgnoredFieldNone()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->providers[0]
            ->expects($this->once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->will($this->returnValue(false));
        $this->providers[1]
            ->expects($this->once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->will($this->returnValue(false));

        $this->assertFalse($this->chainProvider->isIgnoredField($metadata, 'fieldName'));
    }

    public function testIsIgnoredRelationByLowPriorityProvider()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->providers[0]
            ->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->will($this->returnValue(true));
        $this->providers[1]
            ->expects($this->never())
            ->method('isIgnoredRelation');

        $this->assertTrue($this->chainProvider->isIgnoredRelation($metadata, 'associationName'));
    }

    public function testIsIgnoredRelationByHighPriorityProvider()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->providers[0]
            ->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->will($this->returnValue(false));
        $this->providers[1]
            ->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->will($this->returnValue(true));

        $this->assertTrue($this->chainProvider->isIgnoredRelation($metadata, 'associationName'));
    }

    public function testIsIgnoredRelationNone()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->providers[0]
            ->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->will($this->returnValue(false));
        $this->providers[1]
            ->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->will($this->returnValue(false));

        $this->assertFalse($this->chainProvider->isIgnoredRelation($metadata, 'associationName'));
    }
}
