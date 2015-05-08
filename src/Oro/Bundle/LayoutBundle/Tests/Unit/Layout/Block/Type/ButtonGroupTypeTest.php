<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\ButtonGroupType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class ButtonGroupTypeTest extends BlockTypeTestCase
{
    public function testGetName()
    {
        $type = $this->getBlockType(ButtonGroupType::NAME);

        $this->assertSame(ButtonGroupType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(ButtonGroupType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
