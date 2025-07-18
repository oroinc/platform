<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\Provider;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Provider\SinglePdfDocumentBySourceEntityProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SinglePdfDocumentBySourceEntityProviderTest extends TestCase
{
    private SinglePdfDocumentBySourceEntityProvider $provider;

    private MockObject&DoctrineHelper $doctrineHelper;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->provider = new SinglePdfDocumentBySourceEntityProvider($this->doctrineHelper);
    }

    public function testFindPdfDocumentReturnsPdfDocument(): void
    {
        $sourceEntity = new \stdClass();
        $pdfDocumentName = 'Test Document';
        $pdfDocumentType = 'us_standard';
        $sourceEntityClass = 'TestEntityClass';
        $sourceEntityId = 123;

        $expectedPdfDocument = $this->createMock(AbstractPdfDocument::class);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityClass')
            ->with($sourceEntity)
            ->willReturn($sourceEntityClass);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($sourceEntity)
            ->willReturn($sourceEntityId);

        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(
                [
                    'sourceEntityClass' => $sourceEntityClass,
                    'sourceEntityId' => $sourceEntityId,
                    'pdfDocumentName' => $pdfDocumentName,
                    'pdfDocumentType' => $pdfDocumentType,
                ],
                ['id' => 'DESC']
            )
            ->willReturn($expectedPdfDocument);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(PdfDocument::class)
            ->willReturn($repository);

        $result = $this->provider->findPdfDocument($sourceEntity, $pdfDocumentName, $pdfDocumentType);

        self::assertSame($expectedPdfDocument, $result);
    }

    public function testFindPdfDocumentReturnsNullWhenNoDocumentFound(): void
    {
        $sourceEntity = new \stdClass();
        $pdfDocumentName = 'Test Document';
        $pdfDocumentType = 'us_standard';
        $sourceEntityClass = 'TestEntityClass';
        $sourceEntityId = 123;

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityClass')
            ->with($sourceEntity)
            ->willReturn($sourceEntityClass);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($sourceEntity)
            ->willReturn($sourceEntityId);

        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(
                [
                    'sourceEntityClass' => $sourceEntityClass,
                    'sourceEntityId' => $sourceEntityId,
                    'pdfDocumentName' => $pdfDocumentName,
                    'pdfDocumentType' => $pdfDocumentType,
                ],
                ['id' => 'DESC']
            )
            ->willReturn(null);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(PdfDocument::class)
            ->willReturn($repository);

        $result = $this->provider->findPdfDocument($sourceEntity, $pdfDocumentName, $pdfDocumentType);

        self::assertNull($result);
    }

    public function testFindPdfDocumentHandlesNullSourceEntityId(): void
    {
        $sourceEntity = new \stdClass();
        $pdfDocumentName = 'Test Document';
        $pdfDocumentType = 'us_standard';

        $this->doctrineHelper
            ->expects(self::never())
            ->method('getEntityClass')
            ->with($sourceEntity);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($sourceEntity)
            ->willReturn(null);

        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects(self::never())
            ->method('findOneBy');

        $this->doctrineHelper
            ->expects(self::never())
            ->method('getEntityRepositoryForClass');

        $result = $this->provider->findPdfDocument($sourceEntity, $pdfDocumentName, $pdfDocumentType);

        self::assertNull($result);
    }
}
