<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Behat\Stub;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\PdfGeneratorBundle\Exception\PdfGeneratorException;
use Oro\Bundle\PdfGeneratorBundle\Model\Size;
use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilderInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;

/**
 * Stub implementation of PdfBuilderInterface used for Behat testing.
 *
 * Simulates PDF generation success or failure to test UI behavior and error handling.
 * Stores options in memory; can either generate a dummy PDF or throw an exception.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PdfBuilderStub implements PdfBuilderInterface
{
    public function __construct(
        private readonly EntityNameResolver $entityNameResolver
    ) {
    }

    private ?object $entity = null;

    #[\Override]
    public function content(PdfTemplateInterface $pdfTemplate): self
    {
        $context = $pdfTemplate->getContext();
        if (isset($context['entity']) && is_object($context['entity'])) {
            $this->entity = $context['entity'];
        }

        return $this;
    }

    #[\Override]
    public function createPdfFile(): PdfFileInterface
    {
        if ($this->entity !== null) {
            $name = $this->entityNameResolver->getName($this->entity);

            if ($name === 'Failure Invoice #InvoiceFailure') {
                throw new PdfGeneratorException('Failed to generate PDF in StubPdfBuilder.');
            }

            if ($name === 'Invoice with commerce default theme #InvoiceCommerceDefaultTheme') {
                return new PdfFileStub(__DIR__ . '/сommerce_default_pdf_file_stub.pdf');
            }

            if ($name === 'Invoice with golden carbon theme #InvoiceGoldenCarbonTheme') {
                return new PdfFileStub(__DIR__ . '/golden_carbon_pdf_file_stub.pdf');
            }
        }

        return new PdfFileStub(__DIR__ . '/pdf_file_stub.pdf');
    }

    #[\Override]
    public function header(PdfTemplateInterface $pdfTemplate): self
    {
        return $this;
    }

    #[\Override]
    public function footer(PdfTemplateInterface $pdfTemplate): self
    {
        return $this;
    }

    #[\Override]
    public function asset(PdfTemplateAssetInterface|string $asset): self
    {
        return $this;
    }

    #[\Override]
    public function pageSize(Size|string|int|float $width, Size|string|int|float $height): self
    {
        return $this;
    }

    #[\Override]
    public function margins(
        Size|string|int|float $marginTop,
        Size|string|int|float $marginBottom,
        Size|string|int|float $marginLeft,
        Size|string|int|float $marginRight
    ): self {
        return $this;
    }

    #[\Override]
    public function landscape(bool $landscape): self
    {
        return $this;
    }

    #[\Override]
    public function scale(float $scale): self
    {
        return $this;
    }

    #[\Override]
    public function customOption(string $name, mixed $value = null): self
    {
        return $this;
    }

    #[\Override]
    public function option(string $name, mixed $value): self
    {
        return $this;
    }
}
