<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\Generator;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentGeneratedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilderFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilderInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Generator\GenericPdfDocumentGenerator;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfTemplate\PdfDocumentTemplateProviderInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\Factory\PdfTemplateFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GenericPdfDocumentGeneratorTest extends TestCase
{
    private GenericPdfDocumentGenerator $generator;

    private MockObject&ManagerRegistry $doctrine;

    private MockObject&PdfBuilderFactoryInterface $pdfBuilderFactory;

    private MockObject&PdfDocumentTemplateProviderInterface $pdfDocumentTemplateProvider;

    private MockObject&PdfTemplateFactoryInterface $pdfTemplateFactory;

    private MockObject&EventDispatcherInterface $eventDispatcher;

    private string $pdfContentTemplatePath;

    private string $pdfHeaderTemplatePath;

    private string $pdfFooterTemplatePath;

    private string $sourceEntityClass;

    private string $pdfDocumentType;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->pdfBuilderFactory = $this->createMock(PdfBuilderFactoryInterface::class);
        $this->pdfDocumentTemplateProvider = $this->createMock(PdfDocumentTemplateProviderInterface::class);
        $this->pdfTemplateFactory = $this->createMock(PdfTemplateFactoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->pdfContentTemplatePath = '@@Acme/SamplePdf/samplePdfContentTemplate.html.twig';
        $this->pdfHeaderTemplatePath = '@@Acme/SamplePdf/samplePdfHeaderTemplate.html.twig';
        $this->pdfFooterTemplatePath = '@@Acme/SamplePdf/samplePdfFooterTemplate.html.twig';
        $this->sourceEntityClass = 'Acme\Bundle\SampleBundle\Entity\Sample';
        $this->pdfDocumentType = 'default_document';

        $this->generator = new GenericPdfDocumentGenerator(
            $this->doctrine,
            $this->pdfBuilderFactory,
            $this->pdfDocumentTemplateProvider,
            $this->pdfTemplateFactory,
            $this->eventDispatcher
        );
    }

    public function testIsApplicableReturnsTrue(): void
    {
        $pdfDocument = new PdfDocument();

        $result = $this->generator->isApplicable($pdfDocument);

        self::assertTrue($result);
    }

    public function testGeneratePdfFileCreatesPdfFileSuccessfully(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument->setSourceEntityClass($this->sourceEntityClass);
        $pdfDocument->setSourceEntityId(1);
        $pdfDocument->setPdfDocumentType($this->pdfDocumentType);
        $pdfDocument->setPdfOptionsPreset('default');
        $pdfDocument->setPdfDocumentPayload(['key' => 'value']);

        $sourceEntity = new \stdClass();
        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($sourceEntity);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with($this->sourceEntityClass)
            ->willReturn($repository);

        $pdfBuilder = $this->createMock(PdfBuilderInterface::class);
        $this->pdfBuilderFactory
            ->expects(self::once())
            ->method('createPdfBuilder')
            ->with($pdfDocument->getPdfOptionsPreset())
            ->willReturn($pdfBuilder);

        $pdfDocumentPayload = ['entity' => $sourceEntity, ...$pdfDocument->getPdfDocumentPayload()];
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(new BeforePdfDocumentGeneratedEvent($pdfBuilder, $pdfDocument, $pdfDocumentPayload));

        $this->pdfDocumentTemplateProvider
            ->expects(self::once())
            ->method('getContentTemplate')
            ->with($this->pdfDocumentType)
            ->willReturn($this->pdfContentTemplatePath);

        $this->pdfDocumentTemplateProvider
            ->expects(self::once())
            ->method('getHeaderTemplate')
            ->with($this->pdfDocumentType)
            ->willReturn($this->pdfHeaderTemplatePath);

        $this->pdfDocumentTemplateProvider
            ->expects(self::once())
            ->method('getFooterTemplate')
            ->with($this->pdfDocumentType)
            ->willReturn($this->pdfFooterTemplatePath);

        $contentTemplate = $this->createMock(PdfTemplateInterface::class);
        $headerTemplate = $this->createMock(PdfTemplateInterface::class);
        $footerTemplate = $this->createMock(PdfTemplateInterface::class);

        $this->pdfTemplateFactory
            ->expects(self::exactly(3))
            ->method('createPdfTemplate')
            ->withConsecutive(
                [$this->pdfHeaderTemplatePath, $pdfDocumentPayload],
                [$this->pdfFooterTemplatePath, $pdfDocumentPayload],
                [$this->pdfContentTemplatePath, $pdfDocumentPayload]
            )
            ->willReturnOnConsecutiveCalls($contentTemplate, $headerTemplate, $footerTemplate);

        $pdfFile = $this->createMock(PdfFileInterface::class);

        $pdfBuilder
            ->expects(self::once())
            ->method('content')
            ->with($contentTemplate)
            ->willReturnSelf();
        $pdfBuilder
            ->expects(self::once())
            ->method('header')
            ->with($headerTemplate)
            ->willReturnSelf();
        $pdfBuilder
            ->expects(self::once())
            ->method('footer')
            ->with($footerTemplate)
            ->willReturnSelf();
        $pdfBuilder
            ->expects(self::once())
            ->method('createPdfFile')
            ->willReturn($pdfFile);

        $result = $this->generator->generatePdfFile($pdfDocument);

        self::assertSame($pdfFile, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGeneratePdfFileWhenEventDispatcherModifiesPayload(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument->setSourceEntityClass($this->sourceEntityClass);
        $pdfDocument->setSourceEntityId(1);
        $pdfDocument->setPdfDocumentType($this->pdfDocumentType);
        $pdfDocument->setPdfOptionsPreset('default');
        $pdfDocument->setPdfDocumentPayload(['key' => 'value']);

        $sourceEntity = new \stdClass();
        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($sourceEntity);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with($this->sourceEntityClass)
            ->willReturn($repository);

        $pdfBuilder = $this->createMock(PdfBuilderInterface::class);
        $this->pdfBuilderFactory
            ->expects(self::once())
            ->method('createPdfBuilder')
            ->with($pdfDocument->getPdfOptionsPreset())
            ->willReturn($pdfBuilder);

        $pdfDocumentPayload = ['entity' => $sourceEntity, ...$pdfDocument->getPdfDocumentPayload()];
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(new BeforePdfDocumentGeneratedEvent($pdfBuilder, $pdfDocument, $pdfDocumentPayload))
            ->willReturnCallback(static function (BeforePdfDocumentGeneratedEvent $event) {
                $payload = $event->getPdfDocumentPayload();
                $event->setPdfDocumentPayload(['sample_key' => 'sample_value', ...$payload]);

                return $event;
            });

        $this->pdfDocumentTemplateProvider
            ->expects(self::once())
            ->method('getContentTemplate')
            ->with($this->pdfDocumentType)
            ->willReturn($this->pdfContentTemplatePath);

        $this->pdfDocumentTemplateProvider
            ->expects(self::once())
            ->method('getHeaderTemplate')
            ->with($this->pdfDocumentType)
            ->willReturn($this->pdfHeaderTemplatePath);

        $this->pdfDocumentTemplateProvider
            ->expects(self::once())
            ->method('getFooterTemplate')
            ->with($this->pdfDocumentType)
            ->willReturn($this->pdfFooterTemplatePath);

        $contentTemplate = $this->createMock(PdfTemplateInterface::class);
        $headerTemplate = $this->createMock(PdfTemplateInterface::class);
        $footerTemplate = $this->createMock(PdfTemplateInterface::class);

        $this->pdfTemplateFactory
            ->expects(self::exactly(3))
            ->method('createPdfTemplate')
            ->withConsecutive(
                [$this->pdfHeaderTemplatePath, ['sample_key' => 'sample_value', ...$pdfDocumentPayload]],
                [$this->pdfFooterTemplatePath, ['sample_key' => 'sample_value', ...$pdfDocumentPayload]],
                [$this->pdfContentTemplatePath, ['sample_key' => 'sample_value', ...$pdfDocumentPayload]]
            )
            ->willReturnOnConsecutiveCalls($contentTemplate, $headerTemplate, $footerTemplate);

        $pdfFile = $this->createMock(PdfFileInterface::class);

        $pdfBuilder
            ->expects(self::once())
            ->method('content')
            ->with($contentTemplate)
            ->willReturnSelf();
        $pdfBuilder
            ->expects(self::once())
            ->method('header')
            ->with($headerTemplate)
            ->willReturnSelf();
        $pdfBuilder
            ->expects(self::once())
            ->method('footer')
            ->with($footerTemplate)
            ->willReturnSelf();
        $pdfBuilder
            ->expects(self::once())
            ->method('createPdfFile')
            ->willReturn($pdfFile);

        $result = $this->generator->generatePdfFile($pdfDocument);

        self::assertSame($pdfFile, $result);
    }

    public function testGeneratePdfFileUsesFallbackTemplatePaths(): void
    {
        $pdfDocument = (new PdfDocument())
            ->setSourceEntityClass($this->sourceEntityClass)
            ->setSourceEntityId(1)
            ->setPdfDocumentType($this->pdfDocumentType)
            ->setPdfOptionsPreset('default')
            ->setPdfDocumentPayload([]);

        $sourceEntity = new \stdClass();
        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($sourceEntity);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with($this->sourceEntityClass)
            ->willReturn($repository);

        $pdfBuilder = $this->createMock(PdfBuilderInterface::class);
        $this->pdfBuilderFactory
            ->expects(self::once())
            ->method('createPdfBuilder')
            ->with($pdfDocument->getPdfOptionsPreset())
            ->willReturn($pdfBuilder);

        $payload = ['entity' => $sourceEntity];
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(new BeforePdfDocumentGeneratedEvent($pdfBuilder, $pdfDocument, $payload));

        $this->pdfDocumentTemplateProvider
            ->expects(self::once())
            ->method('getContentTemplate')
            ->with($this->pdfDocumentType)
            ->willReturn($this->pdfContentTemplatePath);

        $this->pdfDocumentTemplateProvider
            ->expects(self::once())
            ->method('getHeaderTemplate')
            ->with($this->pdfDocumentType)
            ->willReturn($this->pdfHeaderTemplatePath);

        $this->pdfDocumentTemplateProvider
            ->expects(self::once())
            ->method('getFooterTemplate')
            ->with($this->pdfDocumentType)
            ->willReturn($this->pdfFooterTemplatePath);

        $contentTemplate = $this->createMock(PdfTemplateInterface::class);
        $headerTemplate = $this->createMock(PdfTemplateInterface::class);
        $footerTemplate = $this->createMock(PdfTemplateInterface::class);

        $this->pdfTemplateFactory
            ->expects(self::exactly(3))
            ->method('createPdfTemplate')
            ->withConsecutive(
                [$this->pdfHeaderTemplatePath, $payload],
                [$this->pdfFooterTemplatePath, $payload],
                [$this->pdfContentTemplatePath, $payload]
            )
            ->willReturnOnConsecutiveCalls($contentTemplate, $headerTemplate, $footerTemplate);

        $pdfFile = $this->createMock(PdfFileInterface::class);

        $pdfBuilder
            ->expects(self::once())
            ->method('content')
            ->with($contentTemplate)
            ->willReturnSelf();
        $pdfBuilder
            ->expects(self::once())
            ->method('header')
            ->with($headerTemplate)
            ->willReturnSelf();
        $pdfBuilder
            ->expects(self::once())
            ->method('footer')
            ->with($footerTemplate)
            ->willReturnSelf();
        $pdfBuilder
            ->expects(self::once())
            ->method('createPdfFile')
            ->willReturn($pdfFile);

        $result = $this->generator->generatePdfFile($pdfDocument);

        self::assertSame($pdfFile, $result);
    }
}
