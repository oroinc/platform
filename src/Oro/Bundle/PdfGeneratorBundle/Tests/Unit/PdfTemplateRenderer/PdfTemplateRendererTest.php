<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfTemplateRenderer;

use Oro\Bundle\PdfGeneratorBundle\Exception\PdfTemplateRenderingException;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplate;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer\PdfTemplateRenderer;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer\Twig\PdfTemplateAssetsCollectorExtension;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment as TwigEnvironment;
use Twig\Error\RuntimeError;

final class PdfTemplateRendererTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private TwigEnvironment&MockObject $twigEnvironment;

    private PdfTemplateRenderer $renderer;

    protected function setUp(): void
    {
        $this->twigEnvironment = $this->createMock(TwigEnvironment::class);
        $this->renderer = new PdfTemplateRenderer($this->twigEnvironment);

        $this->setUpLoggerMock($this->renderer);
    }

    public function testRenderSuccessfully(): void
    {
        $pdfTemplate = new PdfTemplate('template.html.twig', ['key' => 'value']);

        $renderedContent = '<div>Rendered Content</div>';
        $this->twigEnvironment
            ->expects(self::once())
            ->method('render')
            ->with($pdfTemplate->getTemplate(), $pdfTemplate->getContext())
            ->willReturn($renderedContent);

        $assetsCollectorExtension = $this->createMock(PdfTemplateAssetsCollectorExtension::class);
        $pdfTemplateAsset = $this->createMock(PdfTemplateAssetInterface::class);
        $assets = ['main.css' => $pdfTemplateAsset];
        $assetsCollectorExtension
            ->expects(self::once())
            ->method('getAssets')
            ->willReturn($assets);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('hasExtension')
            ->with(PdfTemplateAssetsCollectorExtension::class)
            ->willReturn(true);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('getExtension')
            ->with(PdfTemplateAssetsCollectorExtension::class)
            ->willReturn($assetsCollectorExtension);

        $pdfContent = $this->renderer->render($pdfTemplate);

        self::assertSame($renderedContent, $pdfContent->getContent());
        self::assertSame($assets, $pdfContent->getAssets());
    }

    public function testRenderSuccessfullyWhenNoAssetsCollector(): void
    {
        $pdfTemplate = new PdfTemplate('template.html.twig', ['key' => 'value']);

        $renderedContent = '<div>Rendered Content</div>';
        $this->twigEnvironment
            ->expects(self::once())
            ->method('render')
            ->with($pdfTemplate->getTemplate(), $pdfTemplate->getContext())
            ->willReturn($renderedContent);

        $assetsCollectorExtension = $this->createMock(PdfTemplateAssetsCollectorExtension::class);
        $assetsCollectorExtension
            ->expects(self::never())
            ->method(self::anything());

        $this->twigEnvironment
            ->expects(self::once())
            ->method('hasExtension')
            ->with(PdfTemplateAssetsCollectorExtension::class)
            ->willReturn(false);

        $pdfContent = $this->renderer->render($pdfTemplate);

        self::assertSame($renderedContent, $pdfContent->getContent());
        self::assertEmpty($pdfContent->getAssets());
    }

    public function testRenderThrowsExceptionOnTwigError(): void
    {
        $pdfTemplate = new PdfTemplate('template.html.twig');

        $exception = new RuntimeError('Twig rendering failed');
        $this->twigEnvironment
            ->expects(self::once())
            ->method('render')
            ->with($pdfTemplate->getTemplate())
            ->willThrowException($exception);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to render a PDF template: {message}',
                [
                    'message' => $exception->getMessage(),
                    'throwable' => $exception,
                    'pdfTemplate' => $pdfTemplate,
                ]
            );

        $this->expectException(PdfTemplateRenderingException::class);
        $this->expectExceptionMessage('Failed to render a PDF template: Twig rendering failed');

        $this->renderer->render($pdfTemplate);
    }
}
