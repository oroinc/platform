<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\Generator;

use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Generator\PdfDocumentGeneratorComposite;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Generator\PdfDocumentGeneratorInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use PHPUnit\Framework\TestCase;

final class PdfDocumentGeneratorCompositeTest extends TestCase
{
    public function testIsApplicableWithApplicableGenerator(): void
    {
        $pdfDocument = new PdfDocument();

        $applicableGenerator = $this->createMock(PdfDocumentGeneratorInterface::class);
        $applicableGenerator
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(true);

        $nonApplicableGenerator = $this->createMock(PdfDocumentGeneratorInterface::class);
        $nonApplicableGenerator
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(false);

        $composite = new PdfDocumentGeneratorComposite([$nonApplicableGenerator, $applicableGenerator]);

        $result = $composite->isApplicable($pdfDocument);

        self::assertTrue($result);
    }

    public function testIsApplicableWithNoApplicableGenerator(): void
    {
        $pdfDocument = new PdfDocument();

        $nonApplicableGenerator1 = $this->createMock(PdfDocumentGeneratorInterface::class);
        $nonApplicableGenerator1
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(false);

        $nonApplicableGenerator2 = $this->createMock(PdfDocumentGeneratorInterface::class);
        $nonApplicableGenerator2
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(false);

        $composite = new PdfDocumentGeneratorComposite([$nonApplicableGenerator1, $nonApplicableGenerator2]);

        $result = $composite->isApplicable($pdfDocument);

        self::assertFalse($result);
    }

    public function testGeneratePdfFileWithApplicableGenerator(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfFile = $this->createMock(PdfFileInterface::class);

        $applicableGenerator = $this->createMock(PdfDocumentGeneratorInterface::class);
        $applicableGenerator
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(true);
        $applicableGenerator
            ->expects(self::once())
            ->method('generatePdfFile')
            ->with($pdfDocument)
            ->willReturn($pdfFile);

        $nonApplicableGenerator = $this->createMock(PdfDocumentGeneratorInterface::class);
        $nonApplicableGenerator
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(false);
        $nonApplicableGenerator
            ->expects(self::never())
            ->method('generatePdfFile');

        $composite = new PdfDocumentGeneratorComposite([$nonApplicableGenerator, $applicableGenerator]);

        $result = $composite->generatePdfFile($pdfDocument);

        self::assertSame($pdfFile, $result);
    }

    public function testGeneratePdfFileWithNoApplicableGenerator(): void
    {
        $pdfDocument = new PdfDocument();

        $nonApplicableGenerator1 = $this->createMock(PdfDocumentGeneratorInterface::class);
        $nonApplicableGenerator1
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(false);
        $nonApplicableGenerator1
            ->expects(self::never())
            ->method('generatePdfFile');

        $nonApplicableGenerator2 = $this->createMock(PdfDocumentGeneratorInterface::class);
        $nonApplicableGenerator2
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(false);
        $nonApplicableGenerator2
            ->expects(self::never())
            ->method('generatePdfFile');

        $composite = new PdfDocumentGeneratorComposite([$nonApplicableGenerator1, $nonApplicableGenerator2]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No applicable PDF generator found for the given PDF document.');

        $composite->generatePdfFile($pdfDocument);
    }
}
