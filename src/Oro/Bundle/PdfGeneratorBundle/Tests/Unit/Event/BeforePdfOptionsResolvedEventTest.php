<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Event;

use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfOptionsResolvedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptionsInterface;
use PHPUnit\Framework\TestCase;

final class BeforePdfOptionsResolvedEventTest extends TestCase
{
    public function testEventProperties(): void
    {
        $pdfOptionsMock = $this->createMock(PdfOptionsInterface::class);
        $pdfEngineName = 'sample_engine';

        $event = new BeforePdfOptionsResolvedEvent($pdfOptionsMock, $pdfEngineName);

        self::assertSame($pdfOptionsMock, $event->getPdfOptions());
        self::assertSame($pdfEngineName, $event->getPdfEngineName());
    }
}
