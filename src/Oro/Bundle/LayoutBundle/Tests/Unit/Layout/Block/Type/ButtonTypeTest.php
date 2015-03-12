<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\ButtonType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class ButtonTypeTest extends BlockTypeTestCase
{
    public function testBuildViewWithDefaultOptions()
    {
        $view = $this->getBlockView(ButtonType::NAME);

        $this->assertEquals('button', $view->vars['type']);
        $this->assertEquals('button', $view->vars['element']);
        $this->assertArrayNotHasKey('type', $view->vars['attr']);
        $this->assertArrayNotHasKey('name', $view->vars);
        $this->assertArrayNotHasKey('value', $view->vars);
        $this->assertArrayNotHasKey('text', $view->vars);
    }

    public function testBuildViewWithNameValueText()
    {
        $view = $this->getBlockView(
            ButtonType::NAME,
            ['name' => 'test_name', 'value' => 'test_value', 'text' => 'test_text']
        );

        $this->assertEquals('button', $view->vars['type']);
        $this->assertEquals('button', $view->vars['element']);
        $this->assertArrayNotHasKey('type', $view->vars['attr']);
        $this->assertEquals('test_name', $view->vars['name']);
        $this->assertEquals('test_value', $view->vars['value']);
        $this->assertEquals('test_text', $view->vars['text']);
    }

    public function testBuildViewForSubmit()
    {
        $view = $this->getBlockView(
            ButtonType::NAME,
            ['type' => 'submit']
        );

        $this->assertEquals('submit', $view->vars['type']);
        $this->assertEquals('input', $view->vars['element']);
        $this->assertEquals('submit', $view->vars['attr']['type']);
    }

    public function testBuildViewForSubmitButton()
    {
        $view = $this->getBlockView(
            ButtonType::NAME,
            ['type' => 'submit_button']
        );

        $this->assertEquals('submit_button', $view->vars['type']);
        $this->assertEquals('button', $view->vars['element']);
        $this->assertEquals('submit', $view->vars['attr']['type']);
    }

    public function testBuildViewForReset()
    {
        $view = $this->getBlockView(
            ButtonType::NAME,
            ['type' => 'reset']
        );

        $this->assertEquals('reset', $view->vars['type']);
        $this->assertEquals('input', $view->vars['element']);
        $this->assertEquals('reset', $view->vars['attr']['type']);
    }

    public function testBuildViewForResetButton()
    {
        $view = $this->getBlockView(
            ButtonType::NAME,
            ['type' => 'reset_button']
        );

        $this->assertEquals('reset_button', $view->vars['type']);
        $this->assertEquals('button', $view->vars['element']);
        $this->assertEquals('reset', $view->vars['attr']['type']);
    }

    public function testBuildViewForInput()
    {
        $view = $this->getBlockView(
            ButtonType::NAME,
            ['type' => 'input']
        );

        $this->assertEquals('input', $view->vars['type']);
        $this->assertEquals('input', $view->vars['element']);
        $this->assertEquals('button', $view->vars['attr']['type']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(ButtonType::NAME);

        $this->assertSame(ButtonType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(ButtonType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
