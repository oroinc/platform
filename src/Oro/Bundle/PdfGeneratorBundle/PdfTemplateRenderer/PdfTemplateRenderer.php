<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer;

use Oro\Bundle\PdfGeneratorBundle\Exception\PdfTemplateRenderingException;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer\Twig\PdfTemplateAssetsCollectorExtension;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Twig\Environment as TwigEnvironment;

/**
 * Renders a TWIG template during PDF generation.
 */
class PdfTemplateRenderer implements PdfTemplateRendererInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private TwigEnvironment $pdfTemplateTwigEnvironment
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * @throws PdfTemplateRenderingException
     */
    #[\Override]
    public function render(PdfTemplateInterface $pdfTemplate): PdfContentInterface
    {
        $assetsCollectorExtension = $this->getAssetsCollectorExtension($this->pdfTemplateTwigEnvironment);

        try {
            $content = $this->pdfTemplateTwigEnvironment
                ->render($pdfTemplate->getTemplate(), $pdfTemplate->getContext());
            $assets = (array)$assetsCollectorExtension?->getAssets();

            return new PdfContent($content, $assets);
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed to render a PDF template: {message}', [
                'message' => $throwable->getMessage(),
                'throwable' => $throwable,
                'pdfTemplate' => $pdfTemplate,
            ]);

            throw new PdfTemplateRenderingException(
                'Failed to render a PDF template: ' . $throwable->getMessage(),
                $throwable->getCode(),
                $throwable,
                $pdfTemplate
            );
        } finally {
            $assetsCollectorExtension?->reset();
        }
    }

    private function getAssetsCollectorExtension(TwigEnvironment $twigEnvironment): ?PdfTemplateAssetsCollectorExtension
    {
        $assetsCollectorExtension = null;
        if ($twigEnvironment->hasExtension(PdfTemplateAssetsCollectorExtension::class)) {
            $assetsCollectorExtension = $twigEnvironment->getExtension(PdfTemplateAssetsCollectorExtension::class);
        }

        return $assetsCollectorExtension;
    }
}
