<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Demand\GenericPdfDocumentDemand;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Factory\GenericPdfDocumentFactory;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentState;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GenericPdfDocumentFactoryTest extends TestCase
{
    private GenericPdfDocumentFactory $factory;

    private MockObject&DoctrineHelper $doctrineHelper;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->factory = new GenericPdfDocumentFactory($this->doctrineHelper);
    }

    public function testCreatePdfDocumentWithValidDemand(): void
    {
        $sourceEntity = new \stdClass();
        $pdfDocumentName = 'test-document';
        $pdfDocumentType = 'default_order';
        $pdfOptionsPreset = 'default';
        $pdfDocumentPayload = ['key' => 'value'];
        $pdfDocumentDemand = new GenericPdfDocumentDemand(
            $sourceEntity,
            $pdfDocumentName,
            $pdfDocumentType,
            $pdfOptionsPreset,
            $pdfDocumentPayload
        );

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityClass')
            ->with($sourceEntity)
            ->willReturn('stdClass');

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($sourceEntity)
            ->willReturn(123);

        $pdfDocument = $this->factory->createPdfDocument($pdfDocumentDemand);

        self::assertSame(PdfDocumentState::NEW, $pdfDocument->getPdfDocumentState());
        self::assertSame($pdfDocumentName, $pdfDocument->getPdfDocumentName());
        self::assertSame($pdfDocumentType, $pdfDocument->getPdfDocumentType());
        self::assertSame($pdfOptionsPreset, $pdfDocument->getPdfOptionsPreset());
        self::assertSame('stdClass', $pdfDocument->getSourceEntityClass());
        self::assertSame(123, $pdfDocument->getSourceEntityId());
        self::assertSame($pdfDocumentPayload, $pdfDocument->getPdfDocumentPayload());
    }
}
