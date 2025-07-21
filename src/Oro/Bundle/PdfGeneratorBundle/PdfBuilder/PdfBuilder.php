<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfBuilder;

use Oro\Bundle\PdfGeneratorBundle\Event\AfterPdfOptionsResolvedEvent;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfOptionsResolvedEvent;
use Oro\Bundle\PdfGeneratorBundle\Exception\PdfOptionsException;
use Oro\Bundle\PdfGeneratorBundle\Model\Size;
use Oro\Bundle\PdfGeneratorBundle\PdfEngine\PdfEngineInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptionsInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * PDF builder common for all PDF engines.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PdfBuilder implements PdfBuilderInterface
{
    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
        private PdfEngineInterface $pdfEngine,
        private PdfOptionsInterface $pdfOptions
    ) {
    }

    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    #[\Override]
    public function content(PdfTemplateInterface $pdfTemplate): PdfBuilderInterface
    {
        $this->pdfOptions['content'] = $pdfTemplate;

        return $this;
    }

    #[\Override]
    public function header(PdfTemplateInterface $pdfTemplate): PdfBuilderInterface
    {
        $this->pdfOptions['header'] = $pdfTemplate;

        return $this;
    }

    #[\Override]
    public function footer(PdfTemplateInterface $pdfTemplate): PdfBuilderInterface
    {
        $this->pdfOptions['footer'] = $pdfTemplate;

        return $this;
    }

    #[\Override]
    public function asset(PdfTemplateAssetInterface|string $asset): PdfBuilderInterface
    {
        $this->pdfOptions['assets'] = array_merge($this->pdfOptions['assets'] ?? [], [$asset]);

        return $this;
    }

    #[\Override]
    public function pageSize(Size|string|int|float $width, Size|string|int|float $height): PdfBuilderInterface
    {
        $this->pdfOptions['page_width'] = $width;
        $this->pdfOptions['page_height'] = $height;

        return $this;
    }

    #[\Override]
    public function margins(
        Size|string|int|float $marginTop,
        Size|string|int|float $marginBottom,
        Size|string|int|float $marginLeft,
        Size|string|int|float $marginRight
    ): PdfBuilderInterface {
        $this->pdfOptions['margin_top'] = $marginTop;
        $this->pdfOptions['margin_bottom'] = $marginBottom;
        $this->pdfOptions['margin_left'] = $marginLeft;
        $this->pdfOptions['margin_right'] = $marginRight;

        return $this;
    }

    #[\Override]
    public function landscape(bool $landscape): self
    {
        $this->pdfOptions['landscape'] = $landscape;

        return $this;
    }

    #[\Override]
    public function scale(float $scale): self
    {
        $this->pdfOptions['scale'] = $scale;

        return $this;
    }

    #[\Override]
    public function customOption(string $name, mixed $value = null): PdfBuilderInterface
    {
        $customOptions = $this->pdfOptions['custom_options'];
        $customOptions[$name] = $value;
        $this->pdfOptions['custom_options'] = $customOptions;

        return $this;
    }

    #[\Override]
    public function option(string $name, mixed $value): PdfBuilderInterface
    {
        $this->pdfOptions[$name] = $value;

        return $this;
    }

    /**
     * @throws PdfOptionsException
     */
    #[\Override]
    public function createPdfFile(): PdfFileInterface
    {
        $this->eventDispatcher?->dispatch(
            new BeforePdfOptionsResolvedEvent($this->pdfOptions, $this->pdfEngine::getName())
        );

        $this->pdfOptions->resolve();

        $this->eventDispatcher?->dispatch(
            new AfterPdfOptionsResolvedEvent($this->pdfOptions, $this->pdfEngine::getName())
        );

        return $this->pdfEngine->createPdfFile($this->pdfOptions);
    }

    /**
     * Returns the underlying PDF engine.
     */
    public function getPdfEngine(): PdfEngineInterface
    {
        return $this->pdfEngine;
    }
}
