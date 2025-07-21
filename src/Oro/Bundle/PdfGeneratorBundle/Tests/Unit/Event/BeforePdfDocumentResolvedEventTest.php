<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Event;

use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentResolvedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentGenerationMode;
use PHPUnit\Framework\TestCase;

final class BeforePdfDocumentResolvedEventTest extends TestCase
{
    public function testEventProperties(): void
    {
        $pdfDocument = $this->createMock(AbstractPdfDocument::class);
        $pdfDocumentGenerationMode = PdfDocumentGenerationMode::DEFERRED;

        $event = new BeforePdfDocumentResolvedEvent($pdfDocument, $pdfDocumentGenerationMode);

        self::assertSame($pdfDocument, $event->getPdfDocument());
        self::assertSame($pdfDocumentGenerationMode, $event->getPdfDocumentGenerationMode());
    }
}
