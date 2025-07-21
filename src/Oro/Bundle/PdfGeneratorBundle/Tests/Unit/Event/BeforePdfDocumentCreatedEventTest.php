<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Event;

use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentCreatedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Demand\AbstractPdfDocumentDemand;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentGenerationMode;
use PHPUnit\Framework\TestCase;

final class BeforePdfDocumentCreatedEventTest extends TestCase
{
    public function testEventProperties(): void
    {
        $pdfDocumentDemand = $this->createMock(AbstractPdfDocumentDemand::class);
        $pdfDocumentGenerationMode = PdfDocumentGenerationMode::DEFERRED;

        $event = new BeforePdfDocumentCreatedEvent($pdfDocumentDemand, $pdfDocumentGenerationMode);

        self::assertSame($pdfDocumentDemand, $event->getPdfDocumentDemand());
        self::assertSame($pdfDocumentGenerationMode, $event->getPdfDocumentGenerationMode());
    }
}
