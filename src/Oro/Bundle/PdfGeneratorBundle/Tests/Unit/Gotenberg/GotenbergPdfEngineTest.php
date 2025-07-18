<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Gotenberg;

use Gotenberg\Exceptions\GotenbergApiErrored;
use Gotenberg\Modules\ChromiumPdf;
use Gotenberg\Stream;
use GuzzleHttp\Psr7\Response;
use Oro\Bundle\PdfGeneratorBundle\Exception\PdfEngineException;
use Oro\Bundle\PdfGeneratorBundle\Gotenberg\GotenbergAssetFactory;
use Oro\Bundle\PdfGeneratorBundle\Gotenberg\GotenbergChromiumPdfFactory;
use Oro\Bundle\PdfGeneratorBundle\Gotenberg\GotenbergPdfEngine;
use Oro\Bundle\PdfGeneratorBundle\Gotenberg\GotenbergPdfFileFactory;
use Oro\Bundle\PdfGeneratorBundle\Model\Size;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFile;
use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptions;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAsset;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer\PdfContent;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer\PdfContentInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer\PdfTemplateRendererInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class GotenbergPdfEngineTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private ClientInterface&MockObject $httpClient;

    private PdfTemplateRendererInterface&MockObject $pdfTemplateRenderer;

    private GotenbergPdfFileFactory&MockObject $gotenbergPdfFileFactory;

    private GotenbergPdfEngine $pdfEngine;

    private PdfOptions $pdfOptions;

    private ChromiumPdf&MockObject $chromiumPdf;

    private array $definedOptions = [
        'header',
        'footer',
        'content',
        'assets',
        'scale',
        'landscape',
        'page_width',
        'page_height',
        'margin_top',
        'margin_bottom',
        'margin_left',
        'margin_right',
        'custom_options',
    ];

    private array $defaultOptions = [
        'assets' => [],
        'custom_options' => [],
    ];

    private PdfTemplateInterface&MockObject $pdfTemplateMain;

    private PdfContentInterface $pdfContentMain;

    private RequestInterface&MockObject $gotenbergRequest;

    protected function setUp(): void
    {
        $gotenbergChromiumPdfFactory = $this->createMock(GotenbergChromiumPdfFactory::class);
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->pdfTemplateRenderer = $this->createMock(PdfTemplateRendererInterface::class);
        $gotenbergAssetFactory = new GotenbergAssetFactory();
        $this->gotenbergPdfFileFactory = $this->createMock(GotenbergPdfFileFactory::class);
        $this->pdfOptions = $this->createPdfOptions();

        $this->pdfEngine = new GotenbergPdfEngine(
            $gotenbergChromiumPdfFactory,
            $this->httpClient,
            $this->pdfTemplateRenderer,
            $gotenbergAssetFactory,
            $this->gotenbergPdfFileFactory
        );
        $this->setUpLoggerMock($this->pdfEngine);

        $this->chromiumPdf = $this->createMock(ChromiumPdf::class);
        $gotenbergChromiumPdfFactory
            ->method('createGotenbergChromiumPdf')
            ->with($this->pdfOptions)
            ->willReturn($this->chromiumPdf);

        $this->pdfTemplateMain = $this->createMock(PdfTemplateInterface::class);
        $this->pdfContentMain = $this->createPdfContent(
            'sample content',
            [$this->createPdfTemplateAsset('main.css')]
        );

        $this->gotenbergRequest = $this->createMock(RequestInterface::class);
        $this->chromiumPdf
            ->method('html')
            ->with(self::isInstanceOf(Stream::class))
            ->willReturn($this->gotenbergRequest);
    }

    private function createPdfOptions(): PdfOptions
    {
        return new PdfOptions(
            [],
            (new OptionsResolver())
                ->setDefined($this->definedOptions)
                ->setDefaults($this->defaultOptions)
        );
    }

    private function createPdfTemplate(string $content, array $assets = []): PdfTemplateInterface&MockObject
    {
        $pdfTemplate = $this->createMock(PdfTemplateInterface::class);
        $pdfTemplate
            ->method('getContent')
            ->willReturn($content);

        $pdfTemplate
            ->method('getAssets')
            ->willReturn(array_combine(array_map(static fn ($asset) => $asset->getName(), $assets), $assets));

        return $pdfTemplate;
    }

    private function createPdfContent(string $content, array $assets = []): PdfContent
    {
        return new PdfContent(
            $content,
            array_combine(array_map(static fn ($asset) => $asset->getName(), $assets), $assets)
        );
    }

    private function createPdfTemplateAsset(string $name): PdfTemplateAssetInterface
    {
        return new PdfTemplateAsset($name, null, $this->createMock(StreamInterface::class));
    }

    private function createGotenbergAsset(PdfTemplateAssetInterface $pdfTemplateAsset): Stream
    {
        return new Stream($pdfTemplateAsset->getName(), $pdfTemplateAsset->getStream());
    }

    public function testGetName(): void
    {
        self::assertSame('gotenberg', GotenbergPdfEngine::getName());
    }

    public function testWithContentOnly(): void
    {
        $this->chromiumPdf
            ->expects(self::never())
            ->method('assets');

        $mainTemplate = $this->createMock(PdfTemplateInterface::class);
        $this->pdfTemplateRenderer
            ->expects(self::once())
            ->method('render')
            ->with($mainTemplate)
            ->willReturn($this->createPdfContent('sample content'));

        $gotenbergResponse = new Response();
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->gotenbergRequest)
            ->willReturn($gotenbergResponse);

        $pdfFile = new PdfFile($this->createMock(StreamInterface::class), 'application/pdf');
        $this->gotenbergPdfFileFactory
            ->expects(self::once())
            ->method('createPdfFile')
            ->with($gotenbergResponse)
            ->willReturn($pdfFile);

        $this->pdfOptions['content'] = $mainTemplate;
        $this->pdfOptions->resolve();

        self::assertSame($pdfFile, $this->pdfEngine->createPdfFile($this->pdfOptions));
    }

    public function testWithContentAndAssets(): void
    {
        $this->chromiumPdf
            ->expects(self::once())
            ->method('assets')
            ->with($this->createGotenbergAsset(...array_values($this->pdfContentMain->getAssets())));

        $this->pdfTemplateRenderer
            ->expects(self::once())
            ->method('render')
            ->with($this->pdfTemplateMain)
            ->willReturn($this->pdfContentMain);

        $gotenbergResponse = new Response();
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->gotenbergRequest)
            ->willReturn($gotenbergResponse);

        $pdfFile = new PdfFile($this->createMock(StreamInterface::class), 'application/pdf');
        $this->gotenbergPdfFileFactory
            ->expects(self::once())
            ->method('createPdfFile')
            ->with($gotenbergResponse)
            ->willReturn($pdfFile);

        $this->pdfOptions['content'] = $this->pdfTemplateMain;
        $this->pdfOptions->resolve();

        self::assertSame($pdfFile, $this->pdfEngine->createPdfFile($this->pdfOptions));
    }

    public function testWithContentAndExtraAssets(): void
    {
        $pdfTemplateAssetExtra = $this->createPdfTemplateAsset('extra.css');

        $this->chromiumPdf
            ->expects(self::once())
            ->method('assets')
            ->with(
                $this->createGotenbergAsset(...array_values($this->pdfContentMain->getAssets())),
                $this->createGotenbergAsset($pdfTemplateAssetExtra)
            );

        $this->pdfTemplateRenderer
            ->expects(self::once())
            ->method('render')
            ->with($this->pdfTemplateMain)
            ->willReturn($this->pdfContentMain);

        $gotenbergResponse = new Response();
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->gotenbergRequest)
            ->willReturn($gotenbergResponse);

        $pdfFile = new PdfFile($this->createMock(StreamInterface::class), 'application/pdf');
        $this->gotenbergPdfFileFactory
            ->expects(self::once())
            ->method('createPdfFile')
            ->with($gotenbergResponse)
            ->willReturn($pdfFile);

        $this->pdfOptions['assets'] = [$pdfTemplateAssetExtra];
        $this->pdfOptions['content'] = $this->pdfTemplateMain;
        $this->pdfOptions->resolve();

        self::assertSame($pdfFile, $this->pdfEngine->createPdfFile($this->pdfOptions));
    }

    public function testWithHeader(): void
    {
        $this->chromiumPdf
            ->expects(self::once())
            ->method('assets')
            ->with($this->createGotenbergAsset(...array_values($this->pdfContentMain->getAssets())));

        $headerTemplate = $this->createMock(PdfTemplateInterface::class);
        $headerContent = $this->createPdfContent('sample header');

        $this->pdfTemplateRenderer
            ->expects(self::exactly(2))
            ->method('render')
            ->withConsecutive([$headerTemplate], [$this->pdfTemplateMain])
            ->willReturn($headerContent, $this->pdfContentMain);

        $this->chromiumPdf
            ->expects(self::once())
            ->method('header')
            ->with(self::isInstanceOf(Stream::class))
            ->willReturnSelf();

        $gotenbergResponse = new Response();
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->gotenbergRequest)
            ->willReturn($gotenbergResponse);

        $pdfFile = new PdfFile($this->createMock(StreamInterface::class), 'application/pdf');
        $this->gotenbergPdfFileFactory
            ->expects(self::once())
            ->method('createPdfFile')
            ->with($gotenbergResponse)
            ->willReturn($pdfFile);

        $this->pdfOptions['header'] = $headerTemplate;
        $this->pdfOptions['content'] = $this->pdfTemplateMain;
        $this->pdfOptions->resolve();

        self::assertSame($pdfFile, $this->pdfEngine->createPdfFile($this->pdfOptions));
    }

    public function testWithFooter(): void
    {
        $this->chromiumPdf
            ->expects(self::once())
            ->method('assets')
            ->with($this->createGotenbergAsset(...array_values($this->pdfContentMain->getAssets())));

        $headerTemplate = $this->createMock(PdfTemplateInterface::class);
        $footerContent = $this->createPdfContent('sample footer');

        $this->pdfTemplateRenderer
            ->expects(self::exactly(2))
            ->method('render')
            ->withConsecutive([$headerTemplate], [$this->pdfTemplateMain])
            ->willReturn($footerContent, $this->pdfContentMain);

        $this->chromiumPdf
            ->expects(self::once())
            ->method('footer')
            ->with(self::isInstanceOf(Stream::class))
            ->willReturnSelf();

        $gotenbergResponse = new Response();
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->gotenbergRequest)
            ->willReturn($gotenbergResponse);

        $pdfFile = new PdfFile($this->createMock(StreamInterface::class), 'application/pdf');
        $this->gotenbergPdfFileFactory
            ->expects(self::once())
            ->method('createPdfFile')
            ->with($gotenbergResponse)
            ->willReturn($pdfFile);

        $this->pdfOptions['footer'] = $headerTemplate;
        $this->pdfOptions['content'] = $this->pdfTemplateMain;
        $this->pdfOptions->resolve();

        self::assertSame($pdfFile, $this->pdfEngine->createPdfFile($this->pdfOptions));
    }

    public function testWithScale(): void
    {
        $this->chromiumPdf
            ->expects(self::once())
            ->method('assets')
            ->with($this->createGotenbergAsset(...array_values($this->pdfContentMain->getAssets())));

        $this->pdfTemplateRenderer
            ->expects(self::once())
            ->method('render')
            ->with($this->pdfTemplateMain)
            ->willReturn($this->pdfContentMain);

        $this->pdfOptions['scale'] = 0.5;
        $this->chromiumPdf
            ->expects(self::once())
            ->method('scale')
            ->with($this->pdfOptions['scale'])
            ->willReturnSelf();

        $gotenbergResponse = new Response();
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->gotenbergRequest)
            ->willReturn($gotenbergResponse);

        $pdfFile = new PdfFile($this->createMock(StreamInterface::class), 'application/pdf');
        $this->gotenbergPdfFileFactory
            ->expects(self::once())
            ->method('createPdfFile')
            ->with($gotenbergResponse)
            ->willReturn($pdfFile);

        $this->pdfOptions['content'] = $this->pdfTemplateMain;
        $this->pdfOptions->resolve();

        self::assertSame($pdfFile, $this->pdfEngine->createPdfFile($this->pdfOptions));
    }

    public function testWithLandscape(): void
    {
        $this->chromiumPdf
            ->expects(self::once())
            ->method('assets')
            ->with($this->createGotenbergAsset(...array_values($this->pdfContentMain->getAssets())));

        $this->pdfTemplateRenderer
            ->expects(self::once())
            ->method('render')
            ->with($this->pdfTemplateMain)
            ->willReturn($this->pdfContentMain);

        $this->pdfOptions['landscape'] = true;
        $this->chromiumPdf
            ->expects(self::once())
            ->method('landscape')
            ->willReturnSelf();

        $gotenbergResponse = new Response();
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->gotenbergRequest)
            ->willReturn($gotenbergResponse);

        $pdfFile = new PdfFile($this->createMock(StreamInterface::class), 'application/pdf');
        $this->gotenbergPdfFileFactory
            ->expects(self::once())
            ->method('createPdfFile')
            ->with($gotenbergResponse)
            ->willReturn($pdfFile);

        $this->pdfOptions['content'] = $this->pdfTemplateMain;
        $this->pdfOptions->resolve();

        self::assertSame($pdfFile, $this->pdfEngine->createPdfFile($this->pdfOptions));
    }

    public function testWithPageSize(): void
    {
        $this->chromiumPdf
            ->expects(self::once())
            ->method('assets')
            ->with($this->createGotenbergAsset(...array_values($this->pdfContentMain->getAssets())));

        $this->pdfTemplateRenderer
            ->expects(self::once())
            ->method('render')
            ->with($this->pdfTemplateMain)
            ->willReturn($this->pdfContentMain);

        $this->pdfOptions['page_width'] = Size::create('210mm');
        $this->pdfOptions['page_height'] = Size::create('297mm');
        $this->chromiumPdf
            ->expects(self::once())
            ->method('paperSize')
            ->with((string)$this->pdfOptions['page_width'], (string)$this->pdfOptions['page_height']);

        $gotenbergResponse = new Response();
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->gotenbergRequest)
            ->willReturn($gotenbergResponse);

        $pdfFile = new PdfFile($this->createMock(StreamInterface::class), 'application/pdf');
        $this->gotenbergPdfFileFactory
            ->expects(self::once())
            ->method('createPdfFile')
            ->with($gotenbergResponse)
            ->willReturn($pdfFile);

        $this->pdfOptions['content'] = $this->pdfTemplateMain;
        $this->pdfOptions->resolve();

        self::assertSame($pdfFile, $this->pdfEngine->createPdfFile($this->pdfOptions));
    }

    public function testWithMargins(): void
    {
        $this->chromiumPdf
            ->expects(self::once())
            ->method('assets')
            ->with($this->createGotenbergAsset(...array_values($this->pdfContentMain->getAssets())));

        $this->pdfTemplateRenderer
            ->expects(self::once())
            ->method('render')
            ->with($this->pdfTemplateMain)
            ->willReturn($this->pdfContentMain);

        $this->pdfOptions['margin_top'] = Size::create('10mm');
        $this->pdfOptions['margin_bottom'] = Size::create('11mm');
        $this->pdfOptions['margin_left'] = Size::create('12mm');
        $this->pdfOptions['margin_right'] = Size::create('13mm');
        $this->chromiumPdf
            ->expects(self::once())
            ->method('margins')
            ->with(
                (string)$this->pdfOptions['margin_top'],
                (string)$this->pdfOptions['margin_bottom'],
                (string)$this->pdfOptions['margin_left'],
                (string)$this->pdfOptions['margin_right']
            )
            ->willReturnSelf();

        $gotenbergResponse = new Response();
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->gotenbergRequest)
            ->willReturn($gotenbergResponse);

        $pdfFile = new PdfFile($this->createMock(StreamInterface::class), 'application/pdf');
        $this->gotenbergPdfFileFactory
            ->expects(self::once())
            ->method('createPdfFile')
            ->with($gotenbergResponse)
            ->willReturn($pdfFile);

        $this->pdfOptions['content'] = $this->pdfTemplateMain;
        $this->pdfOptions->resolve();

        self::assertSame($pdfFile, $this->pdfEngine->createPdfFile($this->pdfOptions));
    }

    public function testWithCustomOptions(): void
    {
        $this->chromiumPdf
            ->expects(self::once())
            ->method('assets')
            ->with($this->createGotenbergAsset(...array_values($this->pdfContentMain->getAssets())));

        $this->pdfTemplateRenderer
            ->expects(self::once())
            ->method('render')
            ->with($this->pdfTemplateMain)
            ->willReturn($this->pdfContentMain);

        $this->pdfOptions['custom_options'] = ['generateDocumentOutline' => true];
        $this->chromiumPdf
            ->expects(self::once())
            ->method('generateDocumentOutline')
            ->willReturnSelf();

        $gotenbergResponse = new Response();
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->gotenbergRequest)
            ->willReturn($gotenbergResponse);

        $pdfFile = new PdfFile($this->createMock(StreamInterface::class), 'application/pdf');
        $this->gotenbergPdfFileFactory
            ->expects(self::once())
            ->method('createPdfFile')
            ->with($gotenbergResponse)
            ->willReturn($pdfFile);

        $this->pdfOptions['content'] = $this->pdfTemplateMain;
        $this->pdfOptions->resolve();

        self::assertSame($pdfFile, $this->pdfEngine->createPdfFile($this->pdfOptions));
    }

    public function testWithMissingCustomOption(): void
    {
        $this->chromiumPdf
            ->expects(self::once())
            ->method('assets')
            ->with($this->createGotenbergAsset(...array_values($this->pdfContentMain->getAssets())));

        $this->pdfTemplateRenderer
            ->expects(self::once())
            ->method('render')
            ->with($this->pdfTemplateMain)
            ->willReturn($this->pdfContentMain);

        $this->pdfOptions['custom_options'] = ['missingOption' => true];

        $gotenbergResponse = new Response();
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->gotenbergRequest)
            ->willReturn($gotenbergResponse);

        $pdfFile = new PdfFile($this->createMock(StreamInterface::class), 'application/pdf');
        $this->gotenbergPdfFileFactory
            ->expects(self::once())
            ->method('createPdfFile')
            ->with($gotenbergResponse)
            ->willReturn($pdfFile);

        $this->pdfOptions['content'] = $this->pdfTemplateMain;
        $this->pdfOptions->resolve();

        $this->loggerMock
            ->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to apply a custom option: method {method} does not exist in {builder}',
                [
                    'method' => 'missingOption',
                    'builder' => get_debug_type($this->chromiumPdf),
                    'pdf_options' => $this->pdfOptions->toArray(),
                    'pdf_engine' => $this->pdfEngine,
                ]
            );

        self::assertSame($pdfFile, $this->pdfEngine->createPdfFile($this->pdfOptions));
    }

    public function testCreatePdfFileThrowsExceptionOnFailure(): void
    {
        $this->chromiumPdf
            ->expects(self::once())
            ->method('assets')
            ->with($this->createGotenbergAsset(...array_values($this->pdfContentMain->getAssets())));

        $this->pdfTemplateRenderer
            ->expects(self::once())
            ->method('render')
            ->with($this->pdfTemplateMain)
            ->willReturn($this->pdfContentMain);

        $gotenbergException = new GotenbergApiErrored('sample error');
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->gotenbergRequest)
            ->willThrowException($gotenbergException);

        $this->pdfOptions['content'] = $this->pdfTemplateMain;
        $this->pdfOptions->resolve();

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to generate a PDF: {message}',
                [
                    'message' => $gotenbergException->getMessage(),
                    'pdf_options' => $this->pdfOptions->toArray(),
                    'pdf_engine' => $this->pdfEngine,
                    'throwable' => $gotenbergException,
                ]
            );

        $this->expectExceptionObject(
            new PdfEngineException(
                'Failed to generate a PDF: ' . $gotenbergException->getMessage(),
                $gotenbergException->getCode(),
                $gotenbergException
            )
        );

        $this->pdfEngine->createPdfFile($this->pdfOptions);
    }
}
