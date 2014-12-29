<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\DictionaryExclusionProvider;

class DictionaryExclusionProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $groupingConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $metadata;

    /** @var DictionaryExclusionProvider dictionaryExclusionProvider */
    private $dictionaryExclusionProvider;

    protected function setUp()
    {
        $this->groupingConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dictionaryExclusionProvider = new DictionaryExclusionProvider($this->groupingConfigProvider);
    }

    public function testIsIgnoredEntity()
    {
        $this->assertFalse($this->dictionaryExclusionProvider->isIgnoredEntity('testClass'));
    }

    public function testIsIgnoredField()
    {
        $this->assertFalse($this->dictionaryExclusionProvider->isIgnoredField($this->metadata, 'testField'));
    }

    public function testIsIgnoredRelationFalse()
    {
        $this->metadata
            ->expects($this->once())
            ->method('isSingleValuedAssociation')
            ->with('testClass')
            ->will($this->returnValue(false));

        $this->assertFalse($this->dictionaryExclusionProvider->isIgnoredRelation($this->metadata, 'testClass'));
    }

    public function testIsIgnoredRelationWithoutGroupsFalse()
    {
        $this->mockMetadata('testClass', 'parentClass');
        $this->assertFalse($this->dictionaryExclusionProvider->isIgnoredRelation($this->metadata, 'testClass'));
    }

    public function testIsIgnoredRelationTrue()
    {
        $this->mockMetadata('testClass', 'parentClass', ['dictionary', 'activity', 'etc']);
        $this->assertTrue($this->dictionaryExclusionProvider->isIgnoredRelation($this->metadata, 'testClass'));
    }

    protected function mockMetadata($entityClass, $parentClass, $groups = [])
    {
        $this->metadata
            ->expects($this->once())
            ->method('isSingleValuedAssociation')
            ->with($entityClass)
            ->will($this->returnValue(true));
        $this->metadata
            ->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with($entityClass)
            ->will($this->returnValue($parentClass));

        $config = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $config
            ->expects($this->once())
            ->method('get')
            ->with('groups')
            ->will($this->returnValue($groups));

        $this->groupingConfigProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with($parentClass)
            ->will($this->returnValue($config));
    }
}
