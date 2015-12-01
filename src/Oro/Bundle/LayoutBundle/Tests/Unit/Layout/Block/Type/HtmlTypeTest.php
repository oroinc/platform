<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\HtmlType;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\TextType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class HtmlTypeTest extends BlockTypeTestCase
{
    public function testGetName()
    {
        $type = $this->getBlockType(HtmlType::NAME);

        $this->assertSame(HtmlType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(HtmlType::NAME);

        $this->assertSame(TextType::NAME, $type->getParent());
    }
}
