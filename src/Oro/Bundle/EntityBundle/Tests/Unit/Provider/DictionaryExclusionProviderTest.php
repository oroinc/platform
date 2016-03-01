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
        $this->assertFalse($this->dictionaryExclusionProvider->isIgnoredRelation($this->metadata, 'testClass'));
    }
}
