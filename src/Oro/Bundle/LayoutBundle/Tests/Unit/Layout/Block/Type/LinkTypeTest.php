<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\LinkType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class LinkTypeTest extends BlockTypeTestCase
{
    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "text" is missing.
     */
    public function testBuildViewWithoutText()
    {
        $this->getBlockView(LinkType::NAME, []);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage Either "path" or "route_name" must be set.
     */
    public function testBuildViewWithoutPathAndRouteName()
    {
        $this->getBlockView(LinkType::NAME, ['text' => 'test']);
    }

    public function testBuildViewWithDefaultOptions()
    {
        $view = $this->getBlockView(
            LinkType::NAME,
            ['path' => 'http://example.com', 'text' => 'test']
        );

        $this->assertEquals('http://example.com', $view->vars['path']);
        $this->assertFalse(isset($view->vars['route_name']));
        $this->assertFalse(isset($view->vars['route_parameters']));
        $this->assertEquals('test', $view->vars['text']);
    }

    public function testBuildView()
    {
        $view = $this->getBlockView(
            LinkType::NAME,
            [
                'route_name'       => 'test_route',
                'route_parameters' => ['foo' => 'bar'],
                'text'             => 'test'
            ]
        );

        $this->assertFalse(isset($view->vars['path']));
        $this->assertEquals('test_route', $view->vars['route_name']);
        $this->assertEquals(['foo' => 'bar'], $view->vars['route_parameters']);
        $this->assertEquals('test', $view->vars['text']);
    }

    public function testBuildViewWithoutRouteParameters()
    {
        $view = $this->getBlockView(
            LinkType::NAME,
            [
                'route_name' => 'test_route',
                'text'       => 'test'
            ]
        );

        $this->assertFalse(isset($view->vars['path']));
        $this->assertEquals('test_route', $view->vars['route_name']);
        $this->assertEquals([], $view->vars['route_parameters']);
        $this->assertEquals('test', $view->vars['text']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(LinkType::NAME);

        $this->assertSame(LinkType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(LinkType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
