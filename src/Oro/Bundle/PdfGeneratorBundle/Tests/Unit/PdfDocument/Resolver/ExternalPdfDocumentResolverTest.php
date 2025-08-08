<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentState;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Resolver\ExternalPdfDocumentResolver;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ExternalPdfDocumentResolverTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private ExternalPdfDocumentResolver $resolver;
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->resolver = new ExternalPdfDocumentResolver($this->doctrine);

        $this->setUpLoggerMock($this->resolver);
    }

    public function testResolvePdfDocumentWithValidFile(): void
    {
        $file = new File();
        $pdfDocument = $this->createPdfDocument();
        $pdfDocument->setPdfDocumentPayload([
            'file' => $file,
            'other_data' => 'test',
        ]);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(File::class)
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($file);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->resolver->resolvePdfDocument($pdfDocument);

        self::assertSame($file, $pdfDocument->getPdfDocumentFile());
        self::assertEquals(PdfDocumentState::RESOLVED, $pdfDocument->getPdfDocumentState());
        self::assertEquals(['other_data' => 'test'], $pdfDocument->getPdfDocumentPayload());
    }

    public function testResolvePdfDocumentWithInvalidFile(): void
    {
        $pdfDocument = $this->createPdfDocument();
        $pdfDocument->setPdfDocumentPayload([
            'file' => new \stdClass(),
            'other_data' => 'test',
        ]);

        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'External PDF document resolver failed for the PDF document {pdfDocumentId}: '
                . 'file is expected to be an instance of "{expectedFile}", got "{actualFile}"',
                [
                    'pdfDocumentId' => 1,
                    'expectedFile' => File::class,
                    'actualFile' => 'stdClass',
                    'pdfDocumentPayload' => ['other_data' => 'test'],
                ]
            );

        $this->resolver->resolvePdfDocument($pdfDocument);

        self::assertNull($pdfDocument->getPdfDocumentFile());
        self::assertEquals(PdfDocumentState::FAILED, $pdfDocument->getPdfDocumentState());
        self::assertEquals(['other_data' => 'test'], $pdfDocument->getPdfDocumentPayload());
    }

    public function testResolvePdfDocumentWithNullFile(): void
    {
        $pdfDocument = $this->createPdfDocument();
        $pdfDocument->setPdfDocumentPayload([
            'file' => null,
            'other_data' => 'test',
        ]);

        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->loggerMock
            ->expects(self::once())
            ->method('notice')
            ->with(
                'External PDF document resolver skips the PDF document {pdfDocumentId}: file is missing from payload',
                [
                    'pdfDocumentId' => 1,
                    'pdfDocumentPayload' => ['other_data' => 'test'],
                ]
            );

        $this->resolver->resolvePdfDocument($pdfDocument);

        self::assertNull($pdfDocument->getPdfDocumentFile());
        self::assertEquals(PdfDocumentState::NEW, $pdfDocument->getPdfDocumentState());
        self::assertEquals(['other_data' => 'test'], $pdfDocument->getPdfDocumentPayload());
    }

    public function testResolvePdfDocumentWithMissingFileKey(): void
    {
        $pdfDocument = $this->createPdfDocument();
        $pdfDocument->setPdfDocumentPayload([
            'other_data' => 'test',
        ]);

        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->loggerMock
            ->expects(self::once())
            ->method('notice')
            ->with(
                'External PDF document resolver skips the PDF document {pdfDocumentId}: file is missing from payload',
                [
                    'pdfDocumentId' => 1,
                    'pdfDocumentPayload' => ['other_data' => 'test'],
                ]
            );

        $this->resolver->resolvePdfDocument($pdfDocument);

        self::assertNull($pdfDocument->getPdfDocumentFile());
        self::assertEquals(PdfDocumentState::NEW, $pdfDocument->getPdfDocumentState());
        self::assertEquals(['other_data' => 'test'], $pdfDocument->getPdfDocumentPayload());
    }

    public function testResolvePdfDocumentWithEmptyPayload(): void
    {
        $pdfDocument = $this->createPdfDocument();
        $pdfDocument->setPdfDocumentPayload([]);

        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->loggerMock
            ->expects(self::once())
            ->method('notice')
            ->with(
                'External PDF document resolver skips the PDF document {pdfDocumentId}: file is missing from payload',
                [
                    'pdfDocumentId' => 1,
                    'pdfDocumentPayload' => [],
                ]
            );

        $this->resolver->resolvePdfDocument($pdfDocument);

        self::assertNull($pdfDocument->getPdfDocumentFile());
        self::assertEquals(PdfDocumentState::NEW, $pdfDocument->getPdfDocumentState());
        self::assertEquals([], $pdfDocument->getPdfDocumentPayload());
    }

    public function testResolvePdfDocumentWhenNotApplicable(): void
    {
        $pdfDocument = $this->createPdfDocument();
        $pdfDocument->setPdfDocumentState(PdfDocumentState::RESOLVED);

        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->resolver->resolvePdfDocument($pdfDocument);

        // State should remain unchanged
        self::assertEquals(PdfDocumentState::RESOLVED, $pdfDocument->getPdfDocumentState());
    }

    /**
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(string $state, bool $expected): void
    {
        $pdfDocument = $this->createPdfDocument();
        $pdfDocument->setPdfDocumentState($state);

        $result = $this->resolver->isApplicable($pdfDocument);

        self::assertEquals($expected, $result);
    }

    public static function isApplicableDataProvider(): array
    {
        return [
            [PdfDocumentState::NEW, true],
            [PdfDocumentState::PENDING, true],
            [PdfDocumentState::DEFERRED, true],
            [PdfDocumentState::IN_PROGRESS, true],
            [PdfDocumentState::FAILED, true],
            [PdfDocumentState::RESOLVED, false],
        ];
    }

    private function createPdfDocument(): PdfDocument
    {
        $pdfDocument = new PdfDocument();
        ReflectionUtil::setId($pdfDocument, 1);

        $pdfDocument->setPdfDocumentState(PdfDocumentState::NEW);

        return $pdfDocument;
    }
}
