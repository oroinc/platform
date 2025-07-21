<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\PdfTemplate;

use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfTemplate\GenericPdfDocumentTemplateProvider;
use PHPUnit\Framework\TestCase;

final class GenericPdfDocumentTemplateProviderTest extends TestCase
{
    private string $pdfDocumentType;
    private string $pdfContentTemplatePath;
    private ?string $pdfHeaderTemplatePath;
    private ?string $pdfFooterTemplatePath;

    private GenericPdfDocumentTemplateProvider $provider;

    protected function setUp(): void
    {
        $this->pdfDocumentType = 'sample';
        $this->pdfContentTemplatePath = '@OroPdfGenerator/PdfDocument/content.html.twig';
        $this->pdfHeaderTemplatePath = '@OroPdfGenerator/PdfDocument/header.html.twig';
        $this->pdfFooterTemplatePath = '@OroPdfGenerator/PdfDocument/footer.html.twig';

        $this->provider = new GenericPdfDocumentTemplateProvider(
            $this->pdfDocumentType,
            $this->pdfContentTemplatePath,
            $this->pdfHeaderTemplatePath,
            $this->pdfFooterTemplatePath
        );
    }

    public function testGetContentTemplateReturnsCorrectPathForMatchingDocumentType(): void
    {
        self::assertSame($this->pdfContentTemplatePath, $this->provider->getContentTemplate($this->pdfDocumentType));
    }

    public function testGetHeaderTemplateReturnsCorrectPathForMatchingDocumentType(): void
    {
        self::assertSame($this->pdfHeaderTemplatePath, $this->provider->getHeaderTemplate($this->pdfDocumentType));
    }

    public function testGetFooterTemplateReturnsCorrectPathForMatchingDocumentType(): void
    {
        self::assertSame($this->pdfFooterTemplatePath, $this->provider->getFooterTemplate($this->pdfDocumentType));
    }

    public function testGetContentTemplateReturnsNullForNonMatchingDocumentType(): void
    {
        self::assertNull($this->provider->getContentTemplate('not_applicable_type'));
    }

    public function testGetHeaderTemplateReturnsNullForNonMatchingDocumentType(): void
    {
        self::assertNull($this->provider->getHeaderTemplate('not_applicable_type'));
    }

    public function testGetFooterTemplateReturnsNullForNonMatchingDocumentType(): void
    {
        self::assertNull($this->provider->getFooterTemplate('not_applicable_type'));
    }
}
