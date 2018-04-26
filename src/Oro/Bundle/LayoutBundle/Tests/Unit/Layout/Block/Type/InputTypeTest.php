<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\InputType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\BaseType;

class InputTypeTest extends BlockTypeTestCase
{
    public function testConfigureOptions()
    {
        $this->assertEquals(
            [
                'type' => 'text',
                'visible' => true
            ],
            $this->resolveOptions(InputType::NAME, [])
        );
    }

    public function testGetBlockView()
    {
        $type = 'button';
        $id = 'test_id';
        $value = 'test_value';
        $name = 'test_name';
        $placeholder = 'test_placeholder';

        $view = $this->getBlockView(
            InputType::NAME,
            ['type' => $type, 'id' => $id, 'value' => $value, 'name' => $name, 'placeholder' => $placeholder]
        );

        $this->assertEquals($type, $view->vars['type']);
        $this->assertEquals($id, $view->vars['attr']['id']);
        $this->assertEquals($value, $view->vars['value']);
        $this->assertEquals($name, $view->vars['name']);
        $this->assertEquals($placeholder, $view->vars['placeholder']);
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(InputType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
