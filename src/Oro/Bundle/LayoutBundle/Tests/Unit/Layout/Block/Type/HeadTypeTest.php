<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\HeadType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class HeadTypeTest extends BlockTypeTestCase
{
    public function testGetName()
    {
        $type = $this->getBlockType(HeadType::NAME);

        $this->assertSame(HeadType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(HeadType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
