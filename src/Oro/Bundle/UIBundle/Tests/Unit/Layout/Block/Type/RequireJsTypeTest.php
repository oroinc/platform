<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Bundle\UIBundle\Layout\Block\Type\RequireJsType;

class RequireJsTypeTest extends BlockTypeTestCase
{
    public function testBuildView()
    {
        $view = $this->getBlockView(
            new RequireJsType(),
            [
                'compressed' => false,
                'modules'    => ['module1', 'module2']
            ]
        );

        $this->assertFalse($view->vars['compressed']);
        $this->assertEquals(['module1', 'module2'], $view->vars['modules']);
    }

    public function testBuildViewWithDefaultOptions()
    {
        $view = $this->getBlockView(new RequireJsType());

        $this->assertTrue($view->vars['compressed']);
        $this->assertEquals([], $view->vars['modules']);
    }

    public function testGetName()
    {
        $type = new RequireJsType();

        $this->assertSame(RequireJsType::NAME, $type->getName());
    }
}
