<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\Exception\PdfGeneratorException;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Generator\PdfDocumentGeneratorInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentState;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Resolver\InstantPdfDocumentResolver;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\Factory\FileEntityFromPdfFileFactory;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFile;
use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPreset;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class InstantPdfDocumentResolverTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private InstantPdfDocumentResolver $resolver;

    private MockObject&ManagerRegistry $doctrine;

    private MockObject&PdfDocumentGeneratorInterface $pdfDocumentGeneratorComposite;

    private MockObject&FileEntityFromPdfFileFactory $fileFromPdfFileFactory;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->pdfDocumentGeneratorComposite = $this->createMock(PdfDocumentGeneratorInterface::class);
        $this->fileFromPdfFileFactory = $this->createMock(FileEntityFromPdfFileFactory::class);

        $this->resolver = new InstantPdfDocumentResolver(
            $this->doctrine,
            $this->pdfDocumentGeneratorComposite,
            $this->fileFromPdfFileFactory
        );
        $this->setUpLoggerMock($this->resolver);
    }

    /**
     * @dataProvider applicableStatesProvider
     */
    public function testIsApplicableReturnsTrueForValidState(string $pdfDocumentState): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument
            ->setPdfDocumentName('sample-document')
            ->setPdfDocumentType('default_order')
            ->setPdfOptionsPreset(PdfOptionsPreset::DEFAULT)
            ->setPdfDocumentState($pdfDocumentState);

        self::assertTrue($this->resolver->isApplicable($pdfDocument));
    }

    public function applicableStatesProvider(): array
    {
        return [
            [PdfDocumentState::PENDING],
            [PdfDocumentState::DEFERRED],
            [PdfDocumentState::FAILED],
        ];
    }

    public function testIsApplicableReturnsFalseForInvalidState(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument
            ->setPdfDocumentName('sample-document')
            ->setPdfDocumentType('default_order')
            ->setPdfOptionsPreset(PdfOptionsPreset::DEFAULT)
            ->setPdfDocumentState(PdfDocumentState::RESOLVED);

        self::assertFalse($this->resolver->isApplicable($pdfDocument));
    }

    public function testResolvePdfDocumentSuccessfullyGeneratesPdfFile(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument
            ->setPdfDocumentName('sample-document')
            ->setPdfDocumentType('default_order')
            ->setPdfOptionsPreset(PdfOptionsPreset::DEFAULT)
            ->setPdfDocumentState(PdfDocumentState::PENDING);

        $tempFilePath = sys_get_temp_dir() . '/sample.pdf';

        try {
            // Create a temporary file with sample PDF content
            file_put_contents($tempFilePath, '%PDF-1.4 Sample PDF Content');

            $pdfFile = new PdfFile($tempFilePath, 'application/pdf');
            $fileEntity = new File();

            $this->pdfDocumentGeneratorComposite
                ->expects(self::once())
                ->method('generatePdfFile')
                ->with($pdfDocument)
                ->willReturn($pdfFile);

            $this->fileFromPdfFileFactory
                ->expects(self::once())
                ->method('createFile')
                ->with($pdfFile, 'sample-document.pdf')
                ->willReturn($fileEntity);

            $entityManager = $this->createMock(EntityManagerInterface::class);
            $this->doctrine
                ->expects(self::once())
                ->method('getManagerForClass')
                ->with(File::class)
                ->willReturn($entityManager);

            $entityManager
                ->expects(self::once())
                ->method('persist')
                ->with($fileEntity);

            $this->assertLoggerNotCalled();

            $this->resolver->resolvePdfDocument($pdfDocument);

            self::assertSame(PdfDocumentState::RESOLVED, $pdfDocument->getPdfDocumentState());
            self::assertSame($fileEntity, $pdfDocument->getPdfDocumentFile());
        } finally {
            // Clean up the temporary file.
            unlink($tempFilePath);
        }
    }

    public function testResolvePdfDocumentSkipsWhenNotApplicable(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument
            ->setPdfDocumentName('sample-document')
            ->setPdfDocumentType('default_order')
            ->setPdfOptionsPreset(PdfOptionsPreset::DEFAULT)
            ->setPdfDocumentState(PdfDocumentState::RESOLVED); // Not an applicable state

        $this->pdfDocumentGeneratorComposite
            ->expects(self::never())
            ->method('generatePdfFile');

        $this->fileFromPdfFileFactory
            ->expects(self::never())
            ->method('createFile');

        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->assertLoggerNotCalled();

        $this->resolver->resolvePdfDocument($pdfDocument);

        self::assertSame(PdfDocumentState::RESOLVED, $pdfDocument->getPdfDocumentState());
        self::assertNull($pdfDocument->getPdfDocumentFile());
    }

    public function testResolvePdfDocumentHandlesPdfGeneratorException(): void
    {
        $pdfDocument = new PdfDocument();
        ReflectionUtil::setId($pdfDocument, 42);
        $pdfDocument
            ->setPdfDocumentName('sample-document')
            ->setPdfDocumentType('default_order')
            ->setPdfOptionsPreset(PdfOptionsPreset::DEFAULT)
            ->setPdfDocumentState(PdfDocumentState::PENDING);

        $exception = new PdfGeneratorException('PDF generation error');
        $this->pdfDocumentGeneratorComposite
            ->expects(self::once())
            ->method('generatePdfFile')
            ->with($pdfDocument)
            ->willThrowException($exception);

        $this->fileFromPdfFileFactory
            ->expects(self::never())
            ->method('createFile');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $entityManager
            ->expects(self::never())
            ->method('persist');

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'PDF generation failed for PDF document {pdfDocumentId}: {message}',
                [
                    'pdfDocumentId' => $pdfDocument->getId(),
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]
            );

        $this->resolver->resolvePdfDocument($pdfDocument);

        self::assertSame(PdfDocumentState::FAILED, $pdfDocument->getPdfDocumentState());
        self::assertNull($pdfDocument->getPdfDocumentFile());
    }

    public function testResolvePdfDocumentHandlesPdfGeneratorExceptionWhenDebugTrue(): void
    {
        $this->resolver->setDebug(true);

        $pdfDocument = new PdfDocument();
        ReflectionUtil::setId($pdfDocument, 42);
        $pdfDocument
            ->setPdfDocumentName('sample-document')
            ->setPdfDocumentType('default_order')
            ->setPdfOptionsPreset(PdfOptionsPreset::DEFAULT)
            ->setPdfDocumentState(PdfDocumentState::PENDING);

        $exception = new PdfGeneratorException('PDF generation error');
        $this->pdfDocumentGeneratorComposite
            ->expects(self::once())
            ->method('generatePdfFile')
            ->with($pdfDocument)
            ->willThrowException($exception);

        $this->fileFromPdfFileFactory
            ->expects(self::never())
            ->method('createFile');

        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'PDF generation failed for PDF document {pdfDocumentId}: {message}',
                [
                    'pdfDocumentId' => $pdfDocument->getId(),
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]
            );

        $this->expectException(PdfGeneratorException::class);
        $this->expectExceptionMessage('PDF generation error');

        $this->resolver->resolvePdfDocument($pdfDocument);

        self::assertSame(PdfDocumentState::FAILED, $pdfDocument->getPdfDocumentState());
        self::assertNull($pdfDocument->getPdfDocumentFile());
    }
}
