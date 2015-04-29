<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\LinkType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class LinkTypeTest extends BlockTypeTestCase
{
    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage Either "path" or "route_name" must be set.
     */
    public function testBuildViewWithoutPathAndRouteName()
    {
        $this->getBlockView(LinkType::NAME, []);
    }

    public function testBuildViewWithDefaultOptions()
    {
        $view = $this->getBlockView(
            LinkType::NAME,
            ['path' => 'http://example.com']
        );

        $this->assertEquals('http://example.com', $view->vars['path']);
        $this->assertArrayNotHasKey('route_name', $view->vars);
        $this->assertArrayNotHasKey('route_parameters', $view->vars);
        $this->assertArrayNotHasKey('text', $view->vars);
        $this->assertArrayNotHasKey('icon', $view->vars);
    }

    public function testBuildViewWithEmptyOptions()
    {
        $view = $this->getBlockView(
            LinkType::NAME,
            [
                'path' => 'http://example.com',
                'text' => '',
                'icon' => ''
            ]
        );

        $this->assertArrayNotHasKey('text', $view->vars);
        $this->assertArrayNotHasKey('icon', $view->vars);
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

        $this->assertArrayNotHasKey('path', $view->vars);
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

        $this->assertArrayNotHasKey('path', $view->vars);
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
