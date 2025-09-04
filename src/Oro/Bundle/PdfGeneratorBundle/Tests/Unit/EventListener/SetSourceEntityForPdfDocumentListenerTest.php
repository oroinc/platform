<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\Event\AfterPdfDocumentCreatedEvent;
use Oro\Bundle\PdfGeneratorBundle\EventListener\Doctrine\SetSourceEntityForPdfDocumentListener;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Demand\GenericPdfDocumentDemand;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentGenerationMode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SetSourceEntityForPdfDocumentListenerTest extends TestCase
{
    private MockObject&DoctrineHelper $doctrineHelper;
    private SetSourceEntityForPdfDocumentListener $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->listener = new SetSourceEntityForPdfDocumentListener($this->doctrineHelper);
    }

    public function testPostFlushSkipsDocumentNotContainedInEntityManager(): void
    {
        $pdfDocument = new PdfDocument();
        $sourceEntity = new \stdClass();

        // First store the document
        $pdfDocumentDemand = new GenericPdfDocumentDemand(
            $sourceEntity,
            'test-document',
            'test_type'
        );
        $event = new AfterPdfDocumentCreatedEvent($pdfDocument, PdfDocumentGenerationMode::INSTANT);
        $event->setPdfDocumentDemand($pdfDocumentDemand);
        $this->listener->onAfterPdfDocumentCreated($event);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('contains')
            ->with($pdfDocument)
            ->willReturn(false);

        $entityManager
            ->expects(self::never())
            ->method('flush');

        $this->doctrineHelper
            ->expects(self::never())
            ->method('getSingleEntityIdentifier');

        $postFlushEvent = new PostFlushEventArgs($entityManager);
        $this->listener->postFlush($postFlushEvent);

        self::assertNull($pdfDocument->getSourceEntityId());
    }

    public function testPostFlushSetsSourceEntityIdAndFlushes(): void
    {
        $pdfDocument = new PdfDocument();
        $sourceEntity = new \stdClass();
        $sourceEntityId = 456;

        // First store the document
        $pdfDocumentDemand = new GenericPdfDocumentDemand(
            $sourceEntity,
            'test-document',
            'test_type'
        );
        $event = new AfterPdfDocumentCreatedEvent($pdfDocument, PdfDocumentGenerationMode::INSTANT);
        $event->setPdfDocumentDemand($pdfDocumentDemand);
        $this->listener->onAfterPdfDocumentCreated($event);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('contains')
            ->with($pdfDocument)
            ->willReturn(true);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($sourceEntity)
            ->willReturn($sourceEntityId);

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $postFlushEvent = new PostFlushEventArgs($entityManager);
        $this->listener->postFlush($postFlushEvent);

        self::assertEquals($sourceEntityId, $pdfDocument->getSourceEntityId());
    }

    public function testPostFlushWithMultipleDocuments(): void
    {
        $pdfDocument1 = new PdfDocument();
        $pdfDocument2 = new PdfDocument();
        $sourceEntity1 = new \stdClass();
        $sourceEntity2 = new \stdClass();

        // Store both documents
        $pdfDocumentDemand1 = new GenericPdfDocumentDemand(
            $sourceEntity1,
            'test-document-1',
            'test_type'
        );
        $event1 = new AfterPdfDocumentCreatedEvent($pdfDocument1, PdfDocumentGenerationMode::INSTANT);
        $event1->setPdfDocumentDemand($pdfDocumentDemand1);
        $this->listener->onAfterPdfDocumentCreated($event1);

        $pdfDocumentDemand2 = new GenericPdfDocumentDemand(
            $sourceEntity2,
            'test-document-2',
            'test_type'
        );
        $event2 = new AfterPdfDocumentCreatedEvent($pdfDocument2, PdfDocumentGenerationMode::INSTANT);
        $event2->setPdfDocumentDemand($pdfDocumentDemand2);
        $this->listener->onAfterPdfDocumentCreated($event2);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::exactly(2))
            ->method('contains')
            ->willReturnMap([
                [$pdfDocument1, true],
                [$pdfDocument2, false], // Second document not in EM
            ]);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($sourceEntity1)
            ->willReturn(123);

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $postFlushEvent = new PostFlushEventArgs($entityManager);
        $this->listener->postFlush($postFlushEvent);

        self::assertEquals(123, $pdfDocument1->getSourceEntityId());
        self::assertNull($pdfDocument2->getSourceEntityId());
    }

    public function testOnClearResetsStorage(): void
    {
        $pdfDocument = new PdfDocument();
        $sourceEntity = new \stdClass();

        // First store something
        $pdfDocumentDemand = new GenericPdfDocumentDemand(
            $sourceEntity,
            'test-document',
            'test_type'
        );
        $event = new AfterPdfDocumentCreatedEvent($pdfDocument, PdfDocumentGenerationMode::INSTANT);
        $event->setPdfDocumentDemand($pdfDocumentDemand);
        $this->listener->onAfterPdfDocumentCreated($event);

        // Clear storage
        $this->listener->onClear();

        // Verify storage is empty by checking that postFlush doesn't process any documents
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::never())
            ->method('contains');
        $entityManager
            ->expects(self::never())
            ->method('flush');

        $postFlushEvent = new PostFlushEventArgs($entityManager);
        $this->listener->postFlush($postFlushEvent);
    }

    public function testResetClearsStorage(): void
    {
        $pdfDocument = new PdfDocument();
        $sourceEntity = new \stdClass();

        // First store something
        $pdfDocumentDemand = new GenericPdfDocumentDemand(
            $sourceEntity,
            'test-document',
            'test_type'
        );
        $event = new AfterPdfDocumentCreatedEvent($pdfDocument, PdfDocumentGenerationMode::INSTANT);
        $event->setPdfDocumentDemand($pdfDocumentDemand);
        $this->listener->onAfterPdfDocumentCreated($event);

        // Reset storage
        $this->listener->reset();

        // Verify storage is empty by checking that postFlush doesn't process any documents
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::never())
            ->method('contains');
        $entityManager
            ->expects(self::never())
            ->method('flush');

        $postFlushEvent = new PostFlushEventArgs($entityManager);
        $this->listener->postFlush($postFlushEvent);
    }

    public function testPostFlushDoesNotFlushWhenNoDocumentsProcessed(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::never())
            ->method('flush');

        $postFlushEvent = new PostFlushEventArgs($entityManager);
        $this->listener->postFlush($postFlushEvent);
    }

    public function testPostFlushClearsStorageAfterProcessing(): void
    {
        $pdfDocument = new PdfDocument();
        $sourceEntity = new \stdClass();

        // Store a document
        $pdfDocumentDemand = new GenericPdfDocumentDemand(
            $sourceEntity,
            'test-document',
            'test_type'
        );
        $event = new AfterPdfDocumentCreatedEvent($pdfDocument, PdfDocumentGenerationMode::INSTANT);
        $event->setPdfDocumentDemand($pdfDocumentDemand);
        $this->listener->onAfterPdfDocumentCreated($event);

        // Process with postFlush
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('contains')
            ->with($pdfDocument)
            ->willReturn(true);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($sourceEntity)
            ->willReturn(789);

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $postFlushEvent = new PostFlushEventArgs($entityManager);
        $this->listener->postFlush($postFlushEvent);

        // Verify storage was cleared by running postFlush again and expecting no operations
        $entityManager2 = $this->createMock(EntityManagerInterface::class);
        $entityManager2
            ->expects(self::never())
            ->method('contains');
        $entityManager2
            ->expects(self::never())
            ->method('flush');

        $postFlushEvent2 = new PostFlushEventArgs($entityManager2);
        $this->listener->postFlush($postFlushEvent2);
    }
}
