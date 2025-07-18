<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\Demand;

use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Demand\GenericPdfDocumentDemand;
use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPreset;
use PHPUnit\Framework\TestCase;

final class GenericPdfDocumentDemandTest extends TestCase
{
    public function testConstructorInitializesPropertiesCorrectly(): void
    {
        $sourceEntity = new \stdClass();
        $pdfDocumentName = 'sample-document';
        $pdfDocumentType = 'custom_type';
        $pdfOptionsPreset = PdfOptionsPreset::DEFAULT;
        $pdfDocumentPayload = ['key' => 'value'];

        $demand = new GenericPdfDocumentDemand(
            $sourceEntity,
            $pdfDocumentName,
            $pdfDocumentType,
            $pdfOptionsPreset,
            $pdfDocumentPayload
        );

        self::assertSame($sourceEntity, $demand->getSourceEntity());
        self::assertSame($pdfDocumentName, $demand->getPdfDocumentName());
        self::assertSame($pdfDocumentType, $demand->getPdfDocumentType());
        self::assertSame($pdfOptionsPreset, $demand->getPdfOptionsPreset());
        self::assertSame($pdfDocumentPayload, $demand->getPdfDocumentPayload());
    }

    public function testConstructorWithDefaultParameters(): void
    {
        $sourceEntity = new \stdClass();
        $pdfDocumentName = 'default-document';
        $pdfDocumentType = 'default_type';

        $demand = new GenericPdfDocumentDemand(
            $sourceEntity,
            $pdfDocumentName,
            $pdfDocumentType
        );

        self::assertSame($sourceEntity, $demand->getSourceEntity());
        self::assertSame($pdfDocumentName, $demand->getPdfDocumentName());
        self::assertSame($pdfDocumentType, $demand->getPdfDocumentType());
        self::assertSame(PdfOptionsPreset::DEFAULT, $demand->getPdfOptionsPreset());
        self::assertSame([], $demand->getPdfDocumentPayload());
    }

    public function testSetPdfDocumentPayloadUpdatesPayload(): void
    {
        $sourceEntity = new \stdClass();
        $demand = new GenericPdfDocumentDemand($sourceEntity, 'doc', 'type');

        $newPayload = ['newKey' => 'newValue'];
        $demand->setPdfDocumentPayload($newPayload);

        self::assertSame($newPayload, $demand->getPdfDocumentPayload());
    }

    public function testSetPdfOptionsPresetUpdatesPreset(): void
    {
        $sourceEntity = new \stdClass();
        $demand = new GenericPdfDocumentDemand($sourceEntity, 'doc', 'type');

        $newPreset = 'custom_preset';
        $demand->setPdfOptionsPreset($newPreset);

        self::assertSame($newPreset, $demand->getPdfOptionsPreset());
    }
}
