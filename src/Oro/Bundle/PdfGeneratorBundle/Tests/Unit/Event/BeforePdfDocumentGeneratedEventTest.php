<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Event;

use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentGeneratedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilderInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use PHPUnit\Framework\TestCase;

final class BeforePdfDocumentGeneratedEventTest extends TestCase
{
    public function testGetPdfBuilder(): void
    {
        $pdfBuilder = $this->createMock(PdfBuilderInterface::class);
        $pdfDocument = $this->createMock(AbstractPdfDocument::class);
        $payload = ['key' => 'value'];

        $event = new BeforePdfDocumentGeneratedEvent($pdfBuilder, $pdfDocument, $payload);

        self::assertSame($pdfBuilder, $event->getPdfBuilder());
    }

    public function testGetPdfDocument(): void
    {
        $pdfBuilder = $this->createMock(PdfBuilderInterface::class);
        $pdfDocument = $this->createMock(AbstractPdfDocument::class);
        $payload = ['key' => 'value'];

        $event = new BeforePdfDocumentGeneratedEvent($pdfBuilder, $pdfDocument, $payload);

        self::assertSame($pdfDocument, $event->getPdfDocument());
    }

    public function testGetPdfDocumentPayload(): void
    {
        $pdfBuilder = $this->createMock(PdfBuilderInterface::class);
        $pdfDocument = $this->createMock(AbstractPdfDocument::class);
        $payload = ['key' => 'value'];

        $event = new BeforePdfDocumentGeneratedEvent($pdfBuilder, $pdfDocument, $payload);

        self::assertSame($payload, $event->getPdfDocumentPayload());
    }

    public function testSetPdfDocumentPayload(): void
    {
        $pdfBuilder = $this->createMock(PdfBuilderInterface::class);
        $pdfDocument = $this->createMock(AbstractPdfDocument::class);
        $initialPayload = ['key' => 'value'];
        $newPayload = ['newKey' => 'newValue'];

        $event = new BeforePdfDocumentGeneratedEvent($pdfBuilder, $pdfDocument, $initialPayload);
        $event->setPdfDocumentPayload($newPayload);

        self::assertSame($newPayload, $event->getPdfDocumentPayload());
    }

    public function testEmptyPayload(): void
    {
        $pdfBuilder = $this->createMock(PdfBuilderInterface::class);
        $pdfDocument = $this->createMock(AbstractPdfDocument::class);
        $payload = [];

        $event = new BeforePdfDocumentGeneratedEvent($pdfBuilder, $pdfDocument, $payload);

        self::assertSame($payload, $event->getPdfDocumentPayload());
    }

    public function testNullPayloadKey(): void
    {
        $pdfBuilder = $this->createMock(PdfBuilderInterface::class);
        $pdfDocument = $this->createMock(AbstractPdfDocument::class);
        $payload = ['key' => null];

        $event = new BeforePdfDocumentGeneratedEvent($pdfBuilder, $pdfDocument, $payload);

        self::assertArrayHasKey('key', $event->getPdfDocumentPayload());
        self::assertNull($event->getPdfDocumentPayload()['key']);
    }

    public function testOverridePayloadKey(): void
    {
        $pdfBuilder = $this->createMock(PdfBuilderInterface::class);
        $pdfDocument = $this->createMock(AbstractPdfDocument::class);
        $initialPayload = ['key' => 'value'];
        $newPayload = ['key' => 'newValue'];

        $event = new BeforePdfDocumentGeneratedEvent($pdfBuilder, $pdfDocument, $initialPayload);
        $event->setPdfDocumentPayload($newPayload);

        self::assertSame('newValue', $event->getPdfDocumentPayload()['key']);
    }
}
