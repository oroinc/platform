<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Bundle\UIBundle\Layout\Block\Type\ButtonSeparatorType;

class ButtonSeparatorTypeTest extends BlockTypeTestCase
{
    public function testGetName()
    {
        $type = new ButtonSeparatorType();

        $this->assertSame(ButtonSeparatorType::NAME, $type->getName());
    }
}
