<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutRendererInterface;
use Oro\Component\Layout\LayoutRendererRegistry;
use Symfony\Component\Templating\TemplateReference;

class LayoutTest extends LayoutTestCase
{
    /** @var LayoutRendererInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $renderer;

    /** @var LayoutRendererRegistry */
    private $rendererRegistry;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(LayoutRendererInterface::class);

        $this->rendererRegistry = new LayoutRendererRegistry();
        $this->rendererRegistry->addRenderer('test', $this->renderer);
        $this->rendererRegistry->setDefaultRenderer('test');
    }

    public function testGetView(): void
    {
        $view = new BlockView();

        $layout = new Layout($view, $this->rendererRegistry);

        self::assertSame($view, $layout->getView());
    }

    public function testRender(): void
    {
        $expected = 'some rendered string';

        $view = new BlockView();

        $this->renderer->expects(self::once())
            ->method('renderBlock')
            ->with($this->identicalTo($view))
            ->willReturn($expected);

        $layout = new Layout($view, $this->rendererRegistry);
        $result = $layout->render();
        self::assertEquals($expected, $result);
    }

    public function testRenderByUnknownRenderer(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The layout renderer named "unknown" was not found.');

        $view = new BlockView();
        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setRenderer('unknown')->render();
    }

    public function testRenderByOtherRenderer(): void
    {
        $expected = 'some rendered string';

        $view = new BlockView();

        $otherRenderer = $this->createMock(LayoutRendererInterface::class);
        $this->rendererRegistry->addRenderer('other', $otherRenderer);

        $otherRenderer->expects(self::once())
            ->method('renderBlock')
            ->with($this->identicalTo($view))
            ->willReturn($expected);

        $layout = new Layout($view, $this->rendererRegistry);
        $result = $layout->setRenderer('other')->render();
        self::assertEquals($expected, $result);
    }

    public function testRenderWithBlockTheme(): void
    {
        $expected = 'some rendered string';
        $theme = '@My/blocks.html.twig';

        $view = new BlockView();

        $this->renderer->expects(self::once())
            ->method('setBlockTheme')
            ->with($this->identicalTo($view), $theme);

        $this->renderer->expects(self::once())
            ->method('renderBlock')
            ->with($this->identicalTo($view))
            ->willReturn($expected);

        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setBlockTheme($theme);
        $result = $layout->render();
        self::assertEquals($expected, $result);
    }

    public function testRenderWithBlockThemeForChild(): void
    {
        $expected = 'some rendered string';
        $theme = '@My/blocks.html.twig';

        $view = new BlockView();

        $childView = new BlockView($view);
        $view->children['child_id'] = $childView;
        $this->setLayoutBlocks(['root' => $view]);

        $this->renderer->expects(self::once())
            ->method('setBlockTheme')
            ->with($this->identicalTo($childView), $theme);

        $this->renderer->expects(self::once())
            ->method('renderBlock')
            ->with($this->identicalTo($view))
            ->willReturn($expected);

        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setBlockTheme($theme, 'child_id');
        $result = $layout->render();
        self::assertEquals($expected, $result);
    }

    public function testSetFormTheme(): void
    {
        $theme = '@My/forms.html.twig';
        $view = new BlockView();
        $this->renderer->expects(self::once())
            ->method('setFormTheme')
            ->with([$theme]);
        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setFormTheme($theme);
        $this->renderer->expects(self::once())
            ->method('renderBlock')
            ->with($this->identicalTo($view))
            ->willReturn('some_value');
        self::assertSame('some_value', $layout->render());
    }

    /**
     * @dataProvider renderWithAbsoluteUrlBlockThemeDataProvider
     */
    public function testRenderWithAbsoluteUrlBlockTheme(mixed $themes, mixed $expected): void
    {
        $view = new BlockView();

        $this->renderer->expects(self::once())
            ->method('setBlockTheme')
            ->with($this->identicalTo($view))
            ->willReturnCallback(function ($view, $themes) use ($expected) {
                // Because assertEqual casted TemplateReference to string on compare - compare type manually
                if (\is_array($expected)) {
                    foreach ($expected as $key => $value) {
                        self::assertSame(\gettype($value), \gettype($themes[$key]));
                        self::assertEquals($value, $themes[$key]);
                    }
                } else {
                    self::assertSame(\gettype($expected), \gettype($themes));
                    self::assertEquals($expected, $themes);
                }
            });

        $this->renderer->expects(self::once())
            ->method('renderBlock')
            ->with($view)
            ->willReturn('render result');

        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setBlockTheme($themes);
        $result = $layout->render();
        self::assertEquals('render result', $result);
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
