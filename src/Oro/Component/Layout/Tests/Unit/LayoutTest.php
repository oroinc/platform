<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutRendererRegistry;
use Symfony\Component\Templating\TemplateReference;

class LayoutTest extends LayoutTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $renderer;

    /** @var LayoutRendererRegistry */
    protected $rendererRegistry;

    protected function setUp(): void
    {
        $this->renderer         = $this->createMock('Oro\Component\Layout\LayoutRendererInterface');
        $this->rendererRegistry = new LayoutRendererRegistry();
        $this->rendererRegistry->addRenderer('test', $this->renderer);
        $this->rendererRegistry->setDefaultRenderer('test');
    }

    public function testGetView()
    {
        $view = new BlockView();

        $layout = new Layout($view, $this->rendererRegistry);

        $this->assertSame($view, $layout->getView());
    }

    public function testRender()
    {
        $expected = 'some rendered string';

        $view = new BlockView();

        $this->renderer->expects($this->once())
            ->method('renderBlock')
            ->with($this->identicalTo($view))
            ->will($this->returnValue($expected));

        $layout = new Layout($view, $this->rendererRegistry);
        $result = $layout->render();
        $this->assertEquals($expected, $result);
    }

    public function testRenderByUnknownRenderer()
    {
        $this->expectException(\Oro\Component\Layout\Exception\LogicException::class);
        $this->expectExceptionMessage('The layout renderer named "unknown" was not found.');

        $view   = new BlockView();
        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setRenderer('unknown')->render();
    }

    public function testRenderByOtherRenderer()
    {
        $expected = 'some rendered string';

        $view = new BlockView();

        $otherRenderer = $this->createMock('Oro\Component\Layout\LayoutRendererInterface');
        $this->rendererRegistry->addRenderer('other', $otherRenderer);

        $otherRenderer->expects($this->once())
            ->method('renderBlock')
            ->with($this->identicalTo($view))
            ->will($this->returnValue($expected));

        $layout = new Layout($view, $this->rendererRegistry);
        $result = $layout->setRenderer('other')->render();
        $this->assertEquals($expected, $result);
    }

    public function testRenderWithBlockTheme()
    {
        $expected = 'some rendered string';
        $theme    = 'MyBungle::blocks.html.twig';

        $view = new BlockView();

        $this->renderer->expects($this->once())
            ->method('setBlockTheme')
            ->with($this->identicalTo($view), $theme);

        $this->renderer->expects($this->once())
            ->method('renderBlock')
            ->with($this->identicalTo($view))
            ->will($this->returnValue($expected));

        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setBlockTheme($theme);
        $result = $layout->render();
        $this->assertEquals($expected, $result);
    }

    public function testRenderWithBlockThemeForChild()
    {
        $expected = 'some rendered string';
        $theme    = 'MyBungle::blocks.html.twig';

        $view = new BlockView();

        $childView                  = new BlockView($view);
        $view->children['child_id'] = $childView;
        $this->setLayoutBlocks(['root' => $view]);

        $this->renderer->expects($this->once())
            ->method('setBlockTheme')
            ->with($this->identicalTo($childView), $theme);

        $this->renderer->expects($this->once())
            ->method('renderBlock')
            ->with($this->identicalTo($view))
            ->will($this->returnValue($expected));

        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setBlockTheme($theme, 'child_id');
        $result = $layout->render();
        $this->assertEquals($expected, $result);
    }

    public function testSetFormTheme()
    {
        $theme = 'MyBundle::forms.html.twig';
        $view = new BlockView();
        $this->renderer->expects($this->once())
            ->method('setFormTheme')
            ->with([$theme]);
        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setFormTheme($theme);
        $this->renderer->expects($this->once())
            ->method('renderBlock')
            ->with($this->identicalTo($view))
            ->willReturn('some_value');
        $this->assertSame('some_value', $layout->render());
    }

    /**
     * @dataProvider renderWithAbsoluteUrlBlockThemeDataProvider
     */
    public function testRenderWithAbsoluteUrlBlockTheme($themes, $expected)
    {
        $view = new BlockView();

        $this->renderer->expects($this->once())
            ->method('setBlockTheme')
            ->with($this->identicalTo($view))
            ->willReturnCallback(function ($view, $themes) use ($expected) {
                // Because assertEqual casted TemplateReference to string on compare - compare type manually
                if (\is_array($expected)) {
                    foreach ($expected as $key => $value) {
                        $this->assertSame(\gettype($value), \gettype($themes[$key]));
                        $this->assertEquals($value, $themes[$key]);
                    }
                } else {
                    $this->assertSame(\gettype($expected), \gettype($themes));
                    $this->assertEquals($expected, $themes);
                }
            })
        ;

        $this->renderer->expects($this->once())
            ->method('renderBlock')
            ->with($view)
            ->willReturn('render result');

        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setBlockTheme($themes);
        $result = $layout->render();
        $this->assertEquals('render result', $result);
    }

    public function renderWithAbsoluteUrlBlockThemeDataProvider(): array
    {
        return [
            'absolute string' => [
                'themes' => '/absolute/path.html.twig',
                'expected' => new TemplateReference('/absolute/path.html.twig', 'twig'),
            ],
            'not absolute string' => [
                'themes' => '@AcmeBundle/not-absolute/path.html.twig',
                'expected' => '@AcmeBundle/not-absolute/path.html.twig',
            ],
            'absolute array' => [
                'themes' => [
                    '/absolute/path-1.html.twig',
                    '/absolute/path-2.html.twig',
                    '@AcmeBundle/not-absolute/path.html.twig',
                ],
                'expected' => [
                    new TemplateReference('/absolute/path-1.html.twig', 'twig'),
                    new TemplateReference('/absolute/path-2.html.twig', 'twig'),
                    '@AcmeBundle/not-absolute/path.html.twig',
                ],
            ],
        ];
    }
}
