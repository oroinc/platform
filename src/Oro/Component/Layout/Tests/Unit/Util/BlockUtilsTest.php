<?php

namespace Oro\Component\Layout\Tests\Unit\Util;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;

class BlockUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function testRegisterPlugin()
    {
        $view = new BlockView();

        $view->vars['block_prefixes'] = ['block', 'container', '_my_container'];

        BlockUtils::registerPlugin($view, 'my_plugin');

        $this->assertEquals(
            ['block', 'container', 'my_plugin', '_my_container'],
            $view->vars['block_prefixes']
        );
    }

    /**
     * @dataProvider normalizeTransValueDataProvider
     */
    public function testNormalizeTransValue($text, $parameters, $expected)
    {
        $result = BlockUtils::normalizeTransValue($text, $parameters);
        $this->assertSame($expected, $result);
    }

    public function normalizeTransValueDataProvider()
    {
        return [
            [null, null, null],
            [null, [], null],
            [null, ['foo' => 'bar'], null],
            ['', null, ''],
            ['', [], ''],
            ['', ['foo' => 'bar'], ''],
            ['test', null, ['label' => 'test']],
            ['test', [], ['label' => 'test']],
            ['test', ['foo' => 'bar'], ['label' => 'test', 'parameters' => ['foo' => 'bar']]],
            [['label' => 'test'], null, ['label' => 'test']],
            [['label' => 'test'], [], ['label' => 'test']],
            [['label' => 'test'], ['foo' => 'bar'], ['label' => 'test', 'parameters' => ['foo' => 'bar']]],
            [
                ['label' => 'test', 'parameters' => ['baz' => 'qux']],
                null,
                ['label' => 'test', 'parameters' => ['baz' => 'qux']]
            ],
            [
                ['label' => 'test', 'parameters' => ['baz' => 'qux']],
                [],
                ['label' => 'test', 'parameters' => ['baz' => 'qux']]
            ],
            [
                ['label' => 'test', 'parameters' => ['baz' => 'qux']],
                ['foo' => 'bar'],
                ['label' => 'test', 'parameters' => ['baz' => 'qux']]
            ]
        ];
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage Either "path" or "route_name" must be set.
     */
    public function testProcessUrlShouldThrowExceptionIfRequiredAndEmptyOptions()
    {
        BlockUtils::processUrl(
            new BlockView(),
            [],
            true
        );
    }

    public function testProcessUrlWithEmptyOptions()
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            []
        );

        $this->assertArrayNotHasKey('path', $view->vars);
        $this->assertArrayNotHasKey('route_name', $view->vars);
        $this->assertArrayNotHasKey('route_parameters', $view->vars);
    }

    public function testProcessUrlWithPrefixAndEmptyOptions()
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            [],
            false,
            'test'
        );

        $this->assertArrayNotHasKey('test_path', $view->vars);
        $this->assertArrayNotHasKey('test_route_name', $view->vars);
        $this->assertArrayNotHasKey('test_route_parameters', $view->vars);
    }

    public function testProcessUrlWithPath()
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            ['path' => 'http://example.com']
        );

        $this->assertEquals('http://example.com', $view->vars['path']);
        $this->assertArrayNotHasKey('route_name', $view->vars);
        $this->assertArrayNotHasKey('route_parameters', $view->vars);
    }

    public function testProcessUrlWithPrefixAndPath()
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            ['test_path' => 'http://example.com'],
            false,
            'test'
        );

        $this->assertEquals('http://example.com', $view->vars['test_path']);
        $this->assertArrayNotHasKey('test_route_name', $view->vars);
        $this->assertArrayNotHasKey('test_route_parameters', $view->vars);
    }

    public function testProcessUrlWithRoute()
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            ['route_name' => 'test_route']
        );

        $this->assertArrayNotHasKey('path', $view->vars);
        $this->assertEquals('test_route', $view->vars['route_name']);
        $this->assertEquals([], $view->vars['route_parameters']);
    }

    public function testProcessUrlWithPrefixAndRoute()
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            ['test_route_name' => 'test_route'],
            false,
            'test'
        );

        $this->assertArrayNotHasKey('test_path', $view->vars);
        $this->assertEquals('test_route', $view->vars['test_route_name']);
        $this->assertEquals([], $view->vars['test_route_parameters']);
    }

    public function testProcessUrlWithRouteParameters()
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            ['route_name' => 'test_route', 'route_parameters' => ['foo' => 'bar']]
        );

        $this->assertArrayNotHasKey('path', $view->vars);
        $this->assertEquals('test_route', $view->vars['route_name']);
        $this->assertEquals(['foo' => 'bar'], $view->vars['route_parameters']);
    }

    public function testProcessUrlWithPrefixAndRouteParameters()
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            ['test_route_name' => 'test_route', 'test_route_parameters' => ['foo' => 'bar']],
            false,
            'test'
        );

        $this->assertArrayNotHasKey('test_path', $view->vars);
        $this->assertEquals('test_route', $view->vars['test_route_name']);
        $this->assertEquals(['foo' => 'bar'], $view->vars['test_route_parameters']);
    }

    public function testProcessUrlWithPathAndRoute()
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            [
                'path'             => 'http://example.com',
                'route_name'       => 'test_route',
                'route_parameters' => ['foo' => 'bar']
            ]
        );

        $this->assertEquals('http://example.com', $view->vars['path']);
        $this->assertArrayNotHasKey('route_name', $view->vars);
        $this->assertArrayNotHasKey('route_parameters', $view->vars);
    }

    public function testProcessUrlWithPrefixAndPathAndRoute()
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            [
                'test_path'             => 'http://example.com',
                'test_route_name'       => 'test_route',
                'test_route_parameters' => ['foo' => 'bar']
            ],
            false,
            'test'
        );

        $this->assertEquals('http://example.com', $view->vars['test_path']);
        $this->assertArrayNotHasKey('test_route_name', $view->vars);
        $this->assertArrayNotHasKey('test_route_parameters', $view->vars);
    }
}
