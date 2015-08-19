<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\ListType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class ListTypeTest extends BlockTypeTestCase
{
    public function testGetName()
    {
        $type = $this->getBlockType(ListType::NAME);

        $this->assertSame(ListType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(ListType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
