<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ChainExclusionProvider;

class ChainExclusionProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ChainExclusionProvider */
    protected $chainProvider;

    /** @var  \PHPUnit_Framework_MockObject_MockObject[] */
    protected $providers = [];

    protected function setUp()
    {
        $hierarchyProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface');

        $this->chainProvider = new ChainExclusionProvider($hierarchyProvider, []);

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
            ->willReturn(true);
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
            ->willReturn(false);
        $this->providers[1]
            ->expects($this->once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->willReturn(true);

        $this->assertTrue($this->chainProvider->isIgnoredEntity('testClass'));
    }

    public function testIsIgnoredEntityNone()
    {
        $this->providers[0]
            ->expects($this->once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->willReturn(false);
        $this->providers[1]
            ->expects($this->once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->willReturn(false);

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
            ->willReturn(true);
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
            ->willReturn(false);
        $this->providers[1]
            ->expects($this->once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->willReturn(true);

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
            ->willReturn(false);
        $this->providers[1]
            ->expects($this->once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->willReturn(false);

        $this->assertFalse($this->chainProvider->isIgnoredField($metadata, 'fieldName'));
    }

    public function testIsIgnoredFieldCache()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->providers[0]
            ->expects($this->once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->willReturn(true);
        $this->providers[1]
            ->expects($this->never())
            ->method('isIgnoredField');

        $this->assertTrue($this->chainProvider->isIgnoredField($metadata, 'fieldName'));
        // test that the result is stored in the local cache
        $this->assertTrue($this->chainProvider->isIgnoredField($metadata, 'fieldName'));
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
            ->willReturn(true);
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
            ->willReturn(false);
        $this->providers[1]
            ->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->willReturn(true);

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
            ->willReturn(false);
        $this->providers[1]
            ->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->willReturn(false);

        $this->assertFalse($this->chainProvider->isIgnoredRelation($metadata, 'associationName'));
    }

    public function testIsIgnoredRelationCache()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->providers[0]
            ->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->willReturn(true);
        $this->providers[1]
            ->expects($this->never())
            ->method('isIgnoredRelation');

        $this->assertTrue($this->chainProvider->isIgnoredRelation($metadata, 'associationName'));
        // test that the result is stored in the local cache
        $this->assertTrue($this->chainProvider->isIgnoredRelation($metadata, 'associationName'));
    }
}
