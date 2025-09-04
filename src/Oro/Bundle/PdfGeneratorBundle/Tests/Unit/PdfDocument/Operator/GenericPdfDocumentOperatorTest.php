<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\Operator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\Event\AfterPdfDocumentCreatedEvent;
use Oro\Bundle\PdfGeneratorBundle\Event\AfterPdfDocumentResolvedEvent;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentCreatedEvent;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentResolvedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Demand\AbstractPdfDocumentDemand;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Factory\PdfDocumentFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Operator\GenericPdfDocumentOperator;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentState;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Resolver\PdfDocumentResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GenericPdfDocumentOperatorTest extends TestCase
{
    private GenericPdfDocumentOperator $operator;

    private MockObject&ManagerRegistry $doctrine;

    private MockObject&PdfDocumentFactoryInterface $pdfDocumentFactory;

    private MockObject&PdfDocumentResolverInterface $pdfDocumentResolver;

    private MockObject&EventDispatcherInterface $eventDispatcher;

    private string $pdfDocumentGenerationMode = 'instant';

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->pdfDocumentFactory = $this->createMock(PdfDocumentFactoryInterface::class);
        $this->pdfDocumentResolver = $this->createMock(PdfDocumentResolverInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->operator = new GenericPdfDocumentOperator(
            $this->doctrine,
            $this->pdfDocumentFactory,
            $this->pdfDocumentResolver,
            $this->eventDispatcher,
            $this->pdfDocumentGenerationMode
        );
    }

    public function testIsResolvedPdfDocumentWhenApplicable(): void
    {
        $pdfDocument = new PdfDocument();

        $this->pdfDocumentResolver
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(false);

        $result = $this->operator->isResolvedPdfDocument($pdfDocument);

        self::assertTrue($result);
    }

    public function testIsResolvedPdfDocumentWhenNotApplicable(): void
    {
        $pdfDocument = new PdfDocument();

        $this->pdfDocumentResolver
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(true);

        $result = $this->operator->isResolvedPdfDocument($pdfDocument);

        self::assertFalse($result);
    }

    public function testResolvePdfDocumentWhenAlreadyResolved(): void
    {
        $pdfDocument = new PdfDocument();

        $this->pdfDocumentResolver
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(false);

        $this->pdfDocumentResolver
            ->expects(self::never())
            ->method('resolvePdfDocument');

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(new BeforePdfDocumentResolvedEvent($pdfDocument, $this->pdfDocumentGenerationMode));

        $this->operator->resolvePdfDocument($pdfDocument);
    }

    public function testResolvePdfDocumentWhenNeedsResolution(): void
    {
        $pdfDocument = new PdfDocument();

        $this->pdfDocumentResolver
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(true);

        $this->pdfDocumentResolver
            ->expects(self::once())
            ->method('resolvePdfDocument')
            ->with($pdfDocument)
            ->willReturnCallback(static function (PdfDocument $pdfDocument) {
                $pdfDocument->setPdfDocumentState(PdfDocumentState::RESOLVED);
            });

        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [new BeforePdfDocumentResolvedEvent($pdfDocument, $this->pdfDocumentGenerationMode)],
                [new AfterPdfDocumentResolvedEvent($pdfDocument, $this->pdfDocumentGenerationMode)]
            );

        $this->operator->resolvePdfDocument($pdfDocument);

        self::assertEquals(PdfDocumentState::RESOLVED, $pdfDocument->getPdfDocumentState());
    }

    public function testCreatePdfDocumentWithValidDemand(): void
    {
        $pdfDocumentDemand = $this->createMock(AbstractPdfDocumentDemand::class);
        $pdfDocument = new PdfDocument();
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->pdfDocumentFactory
            ->expects(self::once())
            ->method('createPdfDocument')
            ->with($pdfDocumentDemand)
            ->willReturn($pdfDocument);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(PdfDocument::class)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($pdfDocument);

        $this->pdfDocumentResolver
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(true);

        $this->pdfDocumentResolver
            ->expects(self::once())
            ->method('resolvePdfDocument')
            ->with($pdfDocument)
            ->willReturnCallback(static function (PdfDocument $pdfDocument) {
                $pdfDocument->setPdfDocumentState(PdfDocumentState::RESOLVED);
            });

        $beforeEvent = new BeforePdfDocumentCreatedEvent(
            $pdfDocumentDemand,
            $this->pdfDocumentGenerationMode
        );

        $afterEvent = new AfterPdfDocumentCreatedEvent(
            $pdfDocument,
            $this->pdfDocumentGenerationMode
        );
        $afterEvent->setPdfDocumentDemand($pdfDocumentDemand);

        $resolveBeforeEvent = new BeforePdfDocumentResolvedEvent(
            $pdfDocument,
            $this->pdfDocumentGenerationMode
        );
        $resolveAfterEvent = new AfterPdfDocumentResolvedEvent(
            $pdfDocument,
            $this->pdfDocumentGenerationMode
        );

        $this->eventDispatcher
            ->expects(self::exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [$beforeEvent],
                [$afterEvent],
                [$resolveBeforeEvent],
                [$resolveAfterEvent]
            )
            ->willReturnSelf();

        $result = $this->operator->createPdfDocument($pdfDocumentDemand);

        self::assertSame($pdfDocument, $result);
        self::assertEquals($this->pdfDocumentGenerationMode, $pdfDocument->getPdfDocumentGenerationMode());
        self::assertEquals(PdfDocumentState::RESOLVED, $pdfDocument->getPdfDocumentState());
    }

    public function testUpdatePdfDocumentChangesStateAndResolves(): void
    {
        $pdfDocument = new PdfDocument();

        $this->pdfDocumentResolver
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(true);

        $this->pdfDocumentResolver
            ->expects(self::once())
            ->method('resolvePdfDocument')
            ->with($pdfDocument)
            ->willReturnCallback(static function (PdfDocument $pdfDocument) {
                $pdfDocument->setPdfDocumentState(PdfDocumentState::RESOLVED);
            });

        $resolveBeforeEvent = new BeforePdfDocumentResolvedEvent(
            $pdfDocument,
            $this->pdfDocumentGenerationMode
        );

        $resolveAfterEvent = new AfterPdfDocumentResolvedEvent(
            $pdfDocument,
            $this->pdfDocumentGenerationMode
        );

        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$resolveBeforeEvent],
                [$resolveAfterEvent]
            )
            ->willReturnSelf();

        $this->operator->updatePdfDocument($pdfDocument);

        self::assertEquals($this->pdfDocumentGenerationMode, $pdfDocument->getPdfDocumentGenerationMode());
        self::assertEquals(PdfDocumentState::RESOLVED, $pdfDocument->getPdfDocumentState());
    }

    public function testDeletePdfDocumentRemovesEntity(): void
    {
        $pdfDocument = new PdfDocument();
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(PdfDocument::class)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('remove')
            ->with($pdfDocument);

        $this->operator->deletePdfDocument($pdfDocument);

        $this->expectNotToPerformAssertions();
    }

    public function testDeletePdfDocumentWhenNoManagerFound(): void
    {
        $pdfDocument = new PdfDocument();

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(PdfDocument::class)
            ->willReturn(null);

        $this->operator->deletePdfDocument($pdfDocument);

        $this->expectNotToPerformAssertions();
    }
}
