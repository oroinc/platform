<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Gotenberg;

use Gotenberg\Gotenberg;
use Gotenberg\Modules\ChromiumPdf;
use Gotenberg\Stream;
use Oro\Bundle\PdfGeneratorBundle\Exception\PdfEngineException;
use Oro\Bundle\PdfGeneratorBundle\PdfEngine\PdfEngineInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptionsInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer\PdfTemplateRendererInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Gotenberg PDF engine.
 */
class GotenbergPdfEngine implements PdfEngineInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private GotenbergChromiumPdfFactory $gotenbergChromiumPdfFactory,
        private ClientInterface $httpClient,
        private PdfTemplateRendererInterface $pdfTemplateRenderer,
        private GotenbergAssetFactory $gotenbergAssetFactory,
        private GotenbergPdfFileFactory $gotenbergPdfFileFactory
    ) {
        $this->logger = new NullLogger();
    }

    public static function getName(): string
    {
        return 'gotenberg';
    }

    /**
     * @throws PdfEngineException
     */
    #[\Override]
    public function createPdfFile(PdfOptionsInterface $pdfOptions): PdfFileInterface
    {
        try {
            $chromiumPdf = $this->gotenbergChromiumPdfFactory->createGotenbergChromiumPdf($pdfOptions);

            $assets = [];
            if (!empty($pdfOptions['header'])) {
                $headerContent = $this->pdfTemplateRenderer->render($pdfOptions['header']);
                $chromiumPdf->header(Stream::string('header.html', $headerContent->getContent()));
                // External resources are not loaded, see https://gotenberg.dev/docs/6.x/html#header-and-footer
                // $assets[] = $headerTemplate->getAssets();
            }

            if (!empty($pdfOptions['footer'])) {
                $footerContent = $this->pdfTemplateRenderer->render($pdfOptions['footer']);
                $chromiumPdf->footer(Stream::string('footer.html', $footerContent->getContent()));
                // External resources are not loaded, see https://gotenberg.dev/docs/6.x/html#header-and-footer
                // $assets[] = $footerTemplate->getAssets();
            }

            $mainContent = $this->pdfTemplateRenderer->render($pdfOptions['content']);
            $assets[] = $mainContent->getAssets();

            $this->addAssets($chromiumPdf, [...array_values(array_merge(...$assets)), ...$pdfOptions['assets']]);
            $this->addPageProperties($chromiumPdf, $pdfOptions);
            $this->addCustomOptions($chromiumPdf, $pdfOptions);

            $request = $chromiumPdf->html(Stream::string('index.html', $mainContent->getContent()));
            $httpResponse = Gotenberg::send($request, $this->httpClient);
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed to generate a PDF: {message}', [
                'message' => $throwable->getMessage(),
                'pdf_options' => $pdfOptions->toArray(),
                'pdf_engine' => $this,
                'throwable' => $throwable,
            ]);

            throw new PdfEngineException(
                'Failed to generate a PDF: ' . $throwable->getMessage(),
                $throwable->getCode(),
                $throwable
            );
        }

        return $this->gotenbergPdfFileFactory->createPdfFile($httpResponse);
    }

    /**
     * @param ChromiumPdf $builder
     * @param array<string,PdfTemplateAssetInterface> $assets
     */
    private function addAssets(ChromiumPdf $builder, array $assets): void
    {
        /** @var array<array<string,Stream>> $gotenbergAssets */
        $gotenbergAssets = array_map($this->gotenbergAssetFactory->createFromPdfTemplateAsset(...), $assets);

        if ($gotenbergAssets) {
            $builder->assets(...array_values(array_merge(...$gotenbergAssets)));
        }
    }

    private function addPageProperties(ChromiumPdf $builder, PdfOptionsInterface $pdfOptions): void
    {
        if (isset($pdfOptions['scale'])) {
            $builder->scale($pdfOptions['scale']);
        }

        if (!empty($pdfOptions['landscape'])) {
            $builder->landscape();
        }

        if (isset($pdfOptions['page_width'], $pdfOptions['page_height'])) {
            $builder->paperSize((string)$pdfOptions['page_width'], (string)$pdfOptions['page_height']);
        }

        if (
            isset($pdfOptions['margin_top'], $pdfOptions['margin_bottom']) &&
            isset($pdfOptions['margin_left'], $pdfOptions['margin_right'])
        ) {
            $builder->margins(
                (string)$pdfOptions['margin_top'],
                (string)$pdfOptions['margin_bottom'],
                (string)$pdfOptions['margin_left'],
                (string)$pdfOptions['margin_right']
            );
        }
    }

    private function addCustomOptions(ChromiumPdf $builder, PdfOptionsInterface $pdfOptions): void
    {
        foreach ($pdfOptions['custom_options'] as $optionName => $optionValue) {
            if (!is_array($optionValue)) {
                $optionValue = [$optionValue];
            }

            if (!method_exists($builder, $optionName)) {
                $this->logger->warning(
                    'Failed to apply a custom option: method {method} does not exist in {builder}',
                    [
                        'method' => $optionName,
                        'builder' => get_debug_type($builder),
                        'pdf_options' => $pdfOptions->toArray(),
                        'pdf_engine' => $this,
                    ]
                );

                continue;
            }

            $builder->$optionName(...$optionValue);
        }
    }
}
