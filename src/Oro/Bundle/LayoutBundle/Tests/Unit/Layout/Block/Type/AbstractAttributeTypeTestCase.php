<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\ActionBundle\Model\AttributeManager;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\AbstractAttributeType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

abstract class AbstractAttributeTypeTestCase extends BlockTypeTestCase
{
    /** @var AttributeManager|\PHPUnit_Framework_MockObject_MockObject */
    private $attributeManager;

    /** @var AbstractAttributeType */
    private $testedType;

    protected function setUp()
    {
        parent::setUp();
        $this->attributeManager = $this->getMockBuilder(AttributeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->testedType = $this->setType();
    }

    /**
     * @return AbstractAttributeType $type
     */
    abstract protected function setType();


    public function testRequireOptions()
    {
        $this->assertEquals(
            [
                'attribute' => ''

            ],
            $this->resolveOptions($this->testedType->getName(), [])
        );
    }
}
