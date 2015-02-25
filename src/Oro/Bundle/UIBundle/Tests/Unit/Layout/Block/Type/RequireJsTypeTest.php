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
            ['compressed' => true]
        );

        $this->assertTrue($view->vars['compressed']);
    }

    public function testBuildViewShouldBeCompressedInProdMode()
    {
        $view = $this->getBlockView(new RequireJsType(false));

        $this->assertTrue($view->vars['compressed']);
    }

    public function testBuildViewShouldNotBeCompressedInDevMode()
    {
        $view = $this->getBlockView(new RequireJsType(true));

        $this->assertFalse($view->vars['compressed']);
    }

    public function testGetName()
    {
        $type = new RequireJsType();

        $this->assertSame(RequireJsType::NAME, $type->getName());
    }
}
