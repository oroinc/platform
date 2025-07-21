<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\PdfTemplate;

use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfTemplate\PdfDocumentTemplateProviderComposite;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfTemplate\PdfDocumentTemplateProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PdfDocumentTemplateProviderCompositeTest extends TestCase
{
    private PdfDocumentTemplateProviderComposite $providerComposite;

    private PdfDocumentTemplateProviderInterface&MockObject $innerProviderMock1;

    private PdfDocumentTemplateProviderInterface&MockObject $innerProviderMock2;

    protected function setUp(): void
    {
        $this->innerProviderMock1 = $this->createMock(PdfDocumentTemplateProviderInterface::class);
        $this->innerProviderMock2 = $this->createMock(PdfDocumentTemplateProviderInterface::class);

        $this->providerComposite = new PdfDocumentTemplateProviderComposite([
            $this->innerProviderMock1,
            $this->innerProviderMock2,
        ]);
    }

    public function testGetContentTemplateReturnsCorrectPathFromFirstMatchingProvider(): void
    {
        $pdfDocumentType = 'sample';
        $expectedTemplate = '@OroPdfGenerator/PdfDocument/content.html.twig';

        $this->innerProviderMock1
            ->expects(self::once())
            ->method('getContentTemplate')
            ->with($pdfDocumentType)
            ->willReturn(null);

        $this->innerProviderMock2
            ->expects(self::once())
            ->method('getContentTemplate')
            ->with($pdfDocumentType)
            ->willReturn($expectedTemplate);

        $result = $this->providerComposite->getContentTemplate($pdfDocumentType);

        self::assertSame($expectedTemplate, $result);
    }

    public function testGetHeaderTemplateReturnsCorrectPathFromFirstMatchingProvider(): void
    {
        $pdfDocumentType = 'sample';
        $expectedTemplate = '@OroPdfGenerator/PdfDocument/header.html.twig';

        $this->innerProviderMock1
            ->expects(self::once())
            ->method('getHeaderTemplate')
            ->with($pdfDocumentType)
            ->willReturn(null);

        $this->innerProviderMock2
            ->expects(self::once())
            ->method('getHeaderTemplate')
            ->with($pdfDocumentType)
            ->willReturn($expectedTemplate);

        $result = $this->providerComposite->getHeaderTemplate($pdfDocumentType);

        self::assertSame($expectedTemplate, $result);
    }

    public function testGetFooterTemplateReturnsCorrectPathFromFirstMatchingProvider(): void
    {
        $pdfDocumentType = 'sample';
        $expectedTemplate = '@OroPdfGenerator/PdfDocument/footer.html.twig';

        $this->innerProviderMock1
            ->expects(self::once())
            ->method('getFooterTemplate')
            ->with($pdfDocumentType)
            ->willReturn(null);

        $this->innerProviderMock2
            ->expects(self::once())
            ->method('getFooterTemplate')
            ->with($pdfDocumentType)
            ->willReturn($expectedTemplate);

        $result = $this->providerComposite->getFooterTemplate($pdfDocumentType);

        self::assertSame($expectedTemplate, $result);
    }

    public function testGetContentTemplateReturnsNullWhenNoProviderMatches(): void
    {
        $pdfDocumentType = 'non_matching_type';

        $this->innerProviderMock1
            ->expects(self::once())
            ->method('getContentTemplate')
            ->with($pdfDocumentType)
            ->willReturn(null);

        $this->innerProviderMock2
            ->expects(self::once())
            ->method('getContentTemplate')
            ->with($pdfDocumentType)
            ->willReturn(null);

        $result = $this->providerComposite->getContentTemplate($pdfDocumentType);

        self::assertNull($result);
    }

    public function testGetHeaderTemplateReturnsNullWhenNoProviderMatches(): void
    {
        $pdfDocumentType = 'non_matching_type';

        $this->innerProviderMock1
            ->expects(self::once())
            ->method('getHeaderTemplate')
            ->with($pdfDocumentType)
            ->willReturn(null);

        $this->innerProviderMock2
            ->expects(self::once())
            ->method('getHeaderTemplate')
            ->with($pdfDocumentType)
            ->willReturn(null);

        $result = $this->providerComposite->getHeaderTemplate($pdfDocumentType);

        self::assertNull($result);
    }

    public function testGetFooterTemplateReturnsNullWhenNoProviderMatches(): void
    {
        $pdfDocumentType = 'non_matching_type';

        $this->innerProviderMock1
            ->expects(self::once())
            ->method('getFooterTemplate')
            ->with($pdfDocumentType)
            ->willReturn(null);

        $this->innerProviderMock2
            ->expects(self::once())
            ->method('getFooterTemplate')
            ->with($pdfDocumentType)
            ->willReturn(null);

        $result = $this->providerComposite->getFooterTemplate($pdfDocumentType);

        self::assertNull($result);
    }

    public function testGetTemplatesReturnsNullWhenProvidersListIsEmpty(): void
    {
        $providerComposite = new PdfDocumentTemplateProviderComposite([]);

        $pdfDocumentType = 'sample';

        self::assertNull($providerComposite->getContentTemplate($pdfDocumentType));
        self::assertNull($providerComposite->getHeaderTemplate($pdfDocumentType));
        self::assertNull($providerComposite->getFooterTemplate($pdfDocumentType));
    }
}
