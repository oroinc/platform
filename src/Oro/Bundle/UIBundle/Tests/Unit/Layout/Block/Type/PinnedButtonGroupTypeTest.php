<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Bundle\UIBundle\Layout\Block\Type\PinnedButtonGroupType;

class PinnedButtonGroupTypeTest extends BlockTypeTestCase
{
    public function testBuildViewDefaultOptions()
    {
        $view = $this->getBlockView(
            new PinnedButtonGroupType(),
            []
        );

        $this->assertEquals('', $view->vars['group_name']);
        $this->assertArrayNotHasKey('more_button_attr', $view->vars);
    }

    public function testBuildView()
    {
        $view = $this->getBlockView(
            new PinnedButtonGroupType(),
            ['group_name' => 'test_group', 'more_button_attr' => ['class' => 'test_class']]
        );

        $this->assertEquals('test_group', $view->vars['group_name']);
        $this->assertEquals(['class' => 'test_class'], $view->vars['more_button_attr']);
    }

    public function testGetName()
    {
        $type = new PinnedButtonGroupType();

        $this->assertSame(PinnedButtonGroupType::NAME, $type->getName());
    }
}
