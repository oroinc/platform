<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\TextType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class TextTypeTest extends BlockTypeTestCase
{
    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "text" is missing.
     */
    public function testBuildViewWithoutText()
    {
        $this->getBlockView(TextType::NAME, []);
    }

    public function testBuildViewWithDefaultOptions()
    {
        $view = $this->getBlockView(
            TextType::NAME,
            ['text' => '']
        );

        $this->assertEquals('', $view->vars['text']);
    }

    public function testBuildView()
    {
        $view = $this->getBlockView(
            TextType::NAME,
            ['text' => 'test']
        );

        $this->assertEquals('test', $view->vars['text']);
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
