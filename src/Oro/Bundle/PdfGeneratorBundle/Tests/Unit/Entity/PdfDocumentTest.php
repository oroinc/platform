<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

final class PdfDocumentTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testPropertyAccessors(): void
    {
        $file = new File();

        self::assertPropertyAccessors(
            new PdfDocument(),
            [
                ['id', 1],
                ['uuid', '123e4567-e89b-12d3-a456-426614174000', false],
                ['pdfDocumentName', 'order-0101', false],
                ['pdfDocumentType', 'us_standard_invoice', false],
                ['pdfDocumentFile', $file],
                ['sourceEntityClass', 'App\\Entity\\Order'],
                ['sourceEntityId', 42],
                ['pdfDocumentPayload', ['key' => 'value'], false],
                ['pdfOptionsPreset', 'default_a4', false],
                ['pdfDocumentState', 'new', false],
                ['pdfDocumentGenerationMode', 'automatic', false],
                ['organization', new Organization()],
                ['createdAt', new \DateTime('now', new \DateTimeZone('UTC'))],
                ['updatedAt', new \DateTime('now', new \DateTimeZone('UTC'))],
            ]
        );
    }
}
