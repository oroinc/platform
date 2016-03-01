<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\InputType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class InputTypeTest extends BlockTypeTestCase
{
    public function testGetName()
    {
        $type = $this->getBlockType(InputType::NAME);

        $this->assertSame(InputType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(InputType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }

    public function testSetDefaultOptions()
    {
        $this->assertEquals(
            [
                'type' => 'text',
                'id' => null,
                'name' => null,
                'value' => null,
                'placeholder' => null,
            ],
            $this->resolveOptions(InputType::NAME, [])
        );

        $options = [
            'type' => 'password',
            'id' => 'passwordId',
            'name' => 'passwordName',
            'value' => '***',
            'placeholder' => 'Enter password',
        ];
        $this->assertEquals($options, $this->resolveOptions(InputType::NAME, $options));
    }

    public function testBuildViewPassword()
    {
        $view = $this->getBlockView(InputType::NAME, ['type' => 'password']);

        $this->assertEquals('password', $view->vars['type']);
        $this->assertEquals(
            [
                'type' => 'password',
                'autocomplete' => 'off',
            ],
            $view->vars['attr']
        );
    }

    public function testBuildViewWithoutOptions()
    {
        $view = $this->getBlockView(InputType::NAME);

        $this->assertEquals(
            [
                'type' => 'text',
            ],
            $view->vars['attr']
        );
    }

    public function testBuildView()
    {
        $options = [
            'id' => 'usernameId',
            'name' => 'usernameName',
            'value' => 'username',
            'placeholder' => 'Enter username',
        ];

        $view = $this->getBlockView(InputType::NAME, $options);

        $this->assertEquals('text', $view->vars['type']);
        $this->assertEquals(['type' => 'text'] + $options, $view->vars['attr']);
    }
}
