<?php

namespace Oro\Component\Layout\Tests\Unit\Util;

use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BlockUtilsTest extends TestCase
{
    public function testRegisterPlugin(): void
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
    public function testNormalizeTransValue($text, $parameters, $expected): void
    {
        $result = BlockUtils::normalizeTransValue($text, $parameters);
        $this->assertSame($expected, $result);
    }

    public function normalizeTransValueDataProvider(): array
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

    public function testProcessUrlShouldThrowExceptionIfRequiredAndEmptyOptions(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Either "path" or "route_name" must be set.');

        BlockUtils::processUrl(
            new BlockView(),
            new Options(),
            true
        );
    }

    public function testProcessUrlWithEmptyOptions(): void
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            new Options()
        );

        $this->assertArrayNotHasKey('path', $view->vars);
        $this->assertArrayNotHasKey('route_name', $view->vars);
        $this->assertArrayNotHasKey('route_parameters', $view->vars);
    }

    public function testProcessUrlWithPrefixAndEmptyOptions(): void
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            new Options(),
            false,
            'test'
        );

        $this->assertArrayNotHasKey('test_path', $view->vars);
        $this->assertArrayNotHasKey('test_route_name', $view->vars);
        $this->assertArrayNotHasKey('test_route_parameters', $view->vars);
    }

    public function testProcessUrlWithPath(): void
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            new Options(['path' => 'http://example.com'])
        );

        $this->assertEquals('http://example.com', $view->vars['path']);
        $this->assertArrayNotHasKey('route_name', $view->vars);
        $this->assertArrayNotHasKey('route_parameters', $view->vars);
    }

    public function testProcessUrlWithPrefixAndPath(): void
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            new Options(['test_path' => 'http://example.com']),
            false,
            'test'
        );

        $this->assertEquals('http://example.com', $view->vars['test_path']);
        $this->assertArrayNotHasKey('test_route_name', $view->vars);
        $this->assertArrayNotHasKey('test_route_parameters', $view->vars);
    }

    public function testProcessUrlWithRoute(): void
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            new Options(['route_name' => 'test_route'])
        );

        $this->assertArrayNotHasKey('path', $view->vars);
        $this->assertEquals('test_route', $view->vars['route_name']);
        $this->assertEquals([], $view->vars['route_parameters']);
    }

    public function testProcessUrlWithPrefixAndRoute(): void
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            new Options(['test_route_name' => 'test_route']),
            false,
            'test'
        );

        $this->assertArrayNotHasKey('test_path', $view->vars);
        $this->assertEquals('test_route', $view->vars['test_route_name']);
        $this->assertEquals([], $view->vars['test_route_parameters']);
    }

    public function testProcessUrlWithRouteParameters(): void
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            new Options(['route_name' => 'test_route', 'route_parameters' => ['foo' => 'bar']])
        );

        $this->assertArrayNotHasKey('path', $view->vars);
        $this->assertEquals('test_route', $view->vars['route_name']);
        $this->assertEquals(new Options(['foo' => 'bar']), $view->vars['route_parameters']);
    }

    public function testProcessUrlWithPrefixAndRouteParameters(): void
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            new Options(['test_route_name' => 'test_route', 'test_route_parameters' => ['foo' => 'bar']]),
            false,
            'test'
        );

        $this->assertArrayNotHasKey('test_path', $view->vars);
        $this->assertEquals('test_route', $view->vars['test_route_name']);
        $this->assertEquals(new Options(['foo' => 'bar']), $view->vars['test_route_parameters']);
    }

    public function testProcessUrlWithPathAndRoute(): void
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            new Options([
                'path'             => 'http://example.com',
                'route_name'       => 'test_route',
                'route_parameters' => ['foo' => 'bar']
            ])
        );

        $this->assertEquals('http://example.com', $view->vars['path']);
        $this->assertArrayNotHasKey('route_name', $view->vars);
        $this->assertArrayNotHasKey('route_parameters', $view->vars);
    }

    public function testProcessUrlWithPrefixAndPathAndRoute(): void
    {
        $view = new BlockView();
        BlockUtils::processUrl(
            $view,
            new Options([
                'test_path'             => 'http://example.com',
                'test_route_name'       => 'test_route',
                'test_route_parameters' => ['foo' => 'bar']
            ]),
            false,
            'test'
        );

        $this->assertEquals('http://example.com', $view->vars['test_path']);
        $this->assertArrayNotHasKey('test_route_name', $view->vars);
        $this->assertArrayNotHasKey('test_route_parameters', $view->vars);
    }

    public function testSetViewVarsFromOptions(): void
    {
        $view = new BlockView();
        BlockUtils::setViewVarsFromOptions(
            $view,
            new Options(
                [
                    'test_path' => 'http://example.com',
                    'test_route_name' => 'test_route',
                    'test_route_parameters' => ['foo' => 'bar']
                ]
            ),
            ['test_route_name', 'test_path']
        );
        $this->assertEquals('http://example.com', $view->vars['test_path']);
        $this->assertEquals('test_route', $view->vars['test_route_name']);
    }
}
