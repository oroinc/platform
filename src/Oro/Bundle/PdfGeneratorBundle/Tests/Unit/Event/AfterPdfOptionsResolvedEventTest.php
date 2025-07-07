<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Event;

use Oro\Bundle\PdfGeneratorBundle\Event\AfterPdfOptionsResolvedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptionsInterface;
use PHPUnit\Framework\TestCase;

final class AfterPdfOptionsResolvedEventTest extends TestCase
{
    public function testEventProperties(): void
    {
        $pdfOptionsMock = $this->createMock(PdfOptionsInterface::class);
        $pdfEngineName = 'sample_engine';

        $event = new AfterPdfOptionsResolvedEvent($pdfOptionsMock, $pdfEngineName);

        self::assertSame($pdfOptionsMock, $event->getPdfOptions());
        self::assertSame($pdfEngineName, $event->getPdfEngineName());
    }
}
