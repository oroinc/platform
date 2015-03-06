<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Bundle\UIBundle\Layout\Block\Type\DatagridType;

class DatagridTypeTest extends BlockTypeTestCase
{
    public function testBuildView()
    {
        $view = $this->getBlockView(
            new DatagridType(),
            ['grid_name' => 'test-grid', 'params' => ['foo' => 'bar'], 'grid_scope' => 'test-scope']
        );

        $this->assertEquals('test-grid', $view->vars['grid_name']);
        $this->assertEquals('test-scope', $view->vars['grid_scope']);
        $this->assertEquals(['foo' => 'bar', 'enableFullScreenLayout' => true], $view->vars['params']);
    }

    public function testBuildViewWithParamsOverwrite()
    {
        $view = $this->getBlockView(
            new DatagridType(),
            ['grid_name' => 'test-grid', 'params' => ['enableFullScreenLayout' => false]]
        );
        $this->assertEquals(['enableFullScreenLayout' => false], $view->vars['params']);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "grid_name" is missing.
     */
    public function testBuildViewThrowsExceptionIfGridNameIsNotSpecified()
    {
        $this->getBlockView(new DatagridType());
    }

    public function testGetName()
    {
        $type = new DatagridType();

        $this->assertSame(DatagridType::NAME, $type->getName());
    }
}
