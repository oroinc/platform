<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\TextType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class TextTypeTest extends BlockTypeTestCase
{
    public function testBuildView()
    {
        $view = $this->getBlockView(
            TextType::NAME,
            ['text' => 'test', 'text_parameters' => ['{{ foo }}' => 'bar']]
        );

        $this->assertEquals('test', $view->vars['text']);
        $this->assertEquals(['{{ foo }}' => 'bar'], $view->vars['text_parameters']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(TextType::NAME);

        $this->assertSame(TextType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(TextType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
