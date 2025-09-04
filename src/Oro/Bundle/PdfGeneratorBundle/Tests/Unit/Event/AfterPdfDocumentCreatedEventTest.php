<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Event;

use Oro\Bundle\PdfGeneratorBundle\Event\AfterPdfDocumentCreatedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Demand\AbstractPdfDocumentDemand;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentGenerationMode;
use PHPUnit\Framework\TestCase;

final class AfterPdfDocumentCreatedEventTest extends TestCase
{
    public function testEventProperties(): void
    {
        $pdfDocument = $this->createMock(AbstractPdfDocument::class);
        $pdfDocumentDemand = $this->createMock(AbstractPdfDocumentDemand::class);
        $pdfDocumentGenerationMode = PdfDocumentGenerationMode::DEFERRED;

        $event = new AfterPdfDocumentCreatedEvent(
            $pdfDocument,
            $pdfDocumentDemand,
            $pdfDocumentGenerationMode
        );

        self::assertSame($pdfDocument, $event->getPdfDocument());
        self::assertSame($pdfDocumentDemand, $event->getPdfDocumentDemand());
        self::assertSame($pdfDocumentGenerationMode, $event->getPdfDocumentGenerationMode());
    }
}
