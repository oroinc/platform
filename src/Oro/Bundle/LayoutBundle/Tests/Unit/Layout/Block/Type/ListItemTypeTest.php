<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\ListItemType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class ListItemTypeTest extends BlockTypeTestCase
{
    public function testBuildViewCharset()
    {
        $view = $this->getBlockView(ListItemType::NAME);

        $this->assertTrue($view->vars['own_template']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(ListItemType::NAME);

        $this->assertSame(ListItemType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(ListItemType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
