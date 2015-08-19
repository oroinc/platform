<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\ButtonType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class ButtonTypeTest extends BlockTypeTestCase
{
    public function testBuildViewWithDefaultOptions()
    {
        $view = $this->getBlockView(ButtonType::NAME);

        $this->assertEquals('button', $view->vars['type']);
        $this->assertEquals('none', $view->vars['action']);
        $this->assertArrayNotHasKey('name', $view->vars);
        $this->assertArrayNotHasKey('value', $view->vars);
        $this->assertArrayNotHasKey('text', $view->vars);
        $this->assertArrayNotHasKey('icon', $view->vars);
    }

    public function testBuildViewWithEmptyOptions()
    {
        $view = $this->getBlockView(
            ButtonType::NAME,
            [
                'name'   => '',
                'value'  => '',
                'text'   => '',
                'icon'   => ''
            ]
        );

        $this->assertArrayNotHasKey('name', $view->vars);
        $this->assertArrayNotHasKey('value', $view->vars);
        $this->assertArrayNotHasKey('text', $view->vars);
        $this->assertArrayNotHasKey('icon', $view->vars);
    }

    public function testBuildViewWithAllOptions()
    {
        $view = $this->getBlockView(
            ButtonType::NAME,
            [
                'type'   => 'input',
                'action' => 'reset',
                'name'   => 'test_name',
                'value'  => 'test_value',
                'text'   => 'test_text',
                'icon'   => 'test_icon'
            ]
        );

        $this->assertEquals('input', $view->vars['type']);
        $this->assertEquals('reset', $view->vars['action']);
        $this->assertEquals('test_name', $view->vars['name']);
        $this->assertEquals('test_value', $view->vars['value']);
        $this->assertEquals('test_text', $view->vars['text']);
        $this->assertEquals('test_icon', $view->vars['icon']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(ButtonType::NAME);

        $this->assertSame(ButtonType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(ButtonType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
