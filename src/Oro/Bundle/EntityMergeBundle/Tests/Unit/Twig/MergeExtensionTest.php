<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model;

use Oro\Bundle\EntityMergeBundle\Twig\MergeExtension;

class MergeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MergeExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $accessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $renderer;

    protected function setUp()
    {
        $this->accessor = $this->getMock('Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface');
        $this->renderer = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Twig\MergeRenderer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new MergeExtension($this->accessor, $this->renderer);
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(2, $functions);
        $this->assertInstanceOf('Twig_SimpleFunction', $functions[0]);
        $this->assertEquals('oro_entity_merge_render_field_value', $functions[0]->getName());
        $this->assertInstanceOf('Twig_SimpleFunction', $functions[1]);
        $this->assertEquals('oro_entity_merge_render_entity_label', $functions[1]->getName());
    }
}
