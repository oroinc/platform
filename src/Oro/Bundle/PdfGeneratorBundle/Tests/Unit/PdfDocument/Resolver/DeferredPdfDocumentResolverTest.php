<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\Resolver;

use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentState;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Resolver\DeferredPdfDocumentResolver;
use PHPUnit\Framework\TestCase;

final class DeferredPdfDocumentResolverTest extends TestCase
{
    public function testIsApplicableReturnsTrueWhenStateIsPending(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument->setPdfDocumentState(PdfDocumentState::PENDING);

        $resolver = new DeferredPdfDocumentResolver();
        $result = $resolver->isApplicable($pdfDocument);

        self::assertTrue($result);
    }

    public function testIsApplicableReturnsFalseWhenStateIsNotPending(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument->setPdfDocumentState(PdfDocumentState::RESOLVED);

        $resolver = new DeferredPdfDocumentResolver();
        $result = $resolver->isApplicable($pdfDocument);

        self::assertFalse($result);
    }

    public function testResolvePdfDocumentSetsStateToDeferredWhenStateIsPending(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument->setPdfDocumentState(PdfDocumentState::PENDING);

        $resolver = new DeferredPdfDocumentResolver();
        $resolver->resolvePdfDocument($pdfDocument);

        self::assertSame(PdfDocumentState::DEFERRED, $pdfDocument->getPdfDocumentState());
    }

    public function testResolvePdfDocumentDoesNothingWhenStateIsNotPending(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument->setPdfDocumentState(PdfDocumentState::NEW);

        $resolver = new DeferredPdfDocumentResolver();
        $resolver->resolvePdfDocument($pdfDocument);

        self::assertSame(PdfDocumentState::NEW, $pdfDocument->getPdfDocumentState());
    }

    public function testResolvePdfDocumentDoesNothingForInvalidState(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument->setPdfDocumentState('invalid_state');

        $resolver = new DeferredPdfDocumentResolver();
        $resolver->resolvePdfDocument($pdfDocument);

        self::assertSame('invalid_state', $pdfDocument->getPdfDocumentState());
    }
}
