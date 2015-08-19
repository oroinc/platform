<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\OrderedListType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class OrderedListTypeTest extends BlockTypeTestCase
{
    public function testBuildViewWithDefaultOptions()
    {
        $view = $this->getBlockView(OrderedListType::NAME);

        $this->assertArrayNotHasKey('type', $view->vars['attr']);
        $this->assertArrayNotHasKey('start', $view->vars['attr']);
    }

    public function testBuildViewWithOrgetingOptions()
    {
        $view = $this->getBlockView(OrderedListType::NAME, ['type' => '1', 'start' => 10]);

        $this->assertEquals('1', $view->vars['attr']['type']);
        $this->assertEquals(10, $view->vars['attr']['start']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(OrderedListType::NAME);

        $this->assertSame(OrderedListType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(OrderedListType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
