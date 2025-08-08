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
        $pdfDocumentGenerationMode = PdfDocumentGenerationMode::DEFERRED;

        $event = new AfterPdfDocumentCreatedEvent($pdfDocument, $pdfDocumentGenerationMode);

        self::assertSame($pdfDocument, $event->getPdfDocument());
        self::assertSame($pdfDocumentGenerationMode, $event->getPdfDocumentGenerationMode());
    }

    public function testPdfDocumentDemandInitiallyNull(): void
    {
        $pdfDocument = $this->createMock(AbstractPdfDocument::class);
        $event = new AfterPdfDocumentCreatedEvent($pdfDocument, PdfDocumentGenerationMode::INSTANT);

        self::assertNull($event->getPdfDocumentDemand());
    }

    public function testSetAndGetPdfDocumentDemand(): void
    {
        $pdfDocument = $this->createMock(AbstractPdfDocument::class);
        $pdfDocumentDemand = $this->createMock(AbstractPdfDocumentDemand::class);
        $event = new AfterPdfDocumentCreatedEvent($pdfDocument, PdfDocumentGenerationMode::INSTANT);

        $event->setPdfDocumentDemand($pdfDocumentDemand);

        self::assertSame($pdfDocumentDemand, $event->getPdfDocumentDemand());
    }

    public function testSetPdfDocumentDemandToNull(): void
    {
        $pdfDocument = $this->createMock(AbstractPdfDocument::class);
        $pdfDocumentDemand = $this->createMock(AbstractPdfDocumentDemand::class);
        $event = new AfterPdfDocumentCreatedEvent($pdfDocument, PdfDocumentGenerationMode::INSTANT);

        $event->setPdfDocumentDemand($pdfDocumentDemand);
        $event->setPdfDocumentDemand(null);

        self::assertNull($event->getPdfDocumentDemand());
    }
}
