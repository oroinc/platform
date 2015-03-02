<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\FieldsetType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class FieldsetTypeTest extends BlockTypeTestCase
{
    public function testBuildViewWithDefaultOptions()
    {
        $view = $this->getBlockView(
            FieldsetType::NAME,
            []
        );

        $this->assertSame('', $view->vars['title']);
        $this->assertEquals([], $view->vars['title_parameters']);
        $this->assertTrue($view->vars['translatable']);
    }

    public function testBuildView()
    {
        $view = $this->getBlockView(
            FieldsetType::NAME,
            ['title' => 'test', 'title_parameters' => ['{{ foo }}' => 'bar'], 'translatable' => false]
        );

        $this->assertEquals('test', $view->vars['title']);
        $this->assertEquals(['{{ foo }}' => 'bar'], $view->vars['title_parameters']);
        $this->assertFalse($view->vars['translatable']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(FieldsetType::NAME);

        $this->assertSame(FieldsetType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(FieldsetType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
