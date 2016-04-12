<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\TitleType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\OptionValueBag;

class TitleTypeTest extends BlockTypeTestCase
{
    public function testSetDefaultOptions()
    {
        $this->assertEquals(
            [
                'value' => [],
                'separator' => '',
                'reverse' => false,
                'resolve_value_bags' => false,
            ],
            $this->resolveOptions(TitleType::NAME, [])
        );
    }

    public function testBuildView()
    {
        $title = ['Default Title', 'Custom Part'];
        $separator = ' > ';

        $view = $this->getBlockView(TitleType::NAME, ['value' => $title, 'separator' => $separator, 'reverse' => true]);

        $this->assertEquals($title, $view->vars['value']);
        $this->assertEquals(' > ', $view->vars['separator']);
        $this->assertTrue($view->vars['reverse']);
    }

    public function testBuildViewWithOptionValueBag()
    {
        $optionBag = new OptionValueBag();
        $optionBag->add(['first', 'second']);
        $optionBag->remove(['first']);

        $view = $this->getBlockView(TitleType::NAME, ['value' => $optionBag]);

        $this->assertEquals(['second'], $view->vars['value']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(TitleType::NAME);

        $this->assertSame(TitleType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(TitleType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
