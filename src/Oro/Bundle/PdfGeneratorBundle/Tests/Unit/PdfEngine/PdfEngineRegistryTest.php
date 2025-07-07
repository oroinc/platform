<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfEngine;

use Oro\Bundle\PdfGeneratorBundle\Exception\PdfGeneratorException;
use Oro\Bundle\PdfGeneratorBundle\PdfEngine\PdfEngineInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfEngine\PdfEngineRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class PdfEngineRegistryTest extends TestCase
{
    private ContainerInterface&MockObject $pdfEngineLocator;

    private PdfEngineRegistry $pdfEngineRegistry;

    protected function setUp(): void
    {
        $this->pdfEngineLocator = $this->createMock(ContainerInterface::class);

        $this->pdfEngineRegistry = new PdfEngineRegistry($this->pdfEngineLocator);
    }

    public function testGetPdfEngineSuccessfullyReturnsEngine(): void
    {
        $engineName = 'test_engine';
        $pdfEngineMock = $this->createMock(PdfEngineInterface::class);

        $this->pdfEngineLocator
            ->expects(self::once())
            ->method('get')
            ->with($engineName)
            ->willReturn($pdfEngineMock);

        $result = $this->pdfEngineRegistry->getPdfEngine($engineName);

        self::assertSame($pdfEngineMock, $result);
    }

    public function testGetPdfEngineThrowsExceptionWhenEngineNotFound(): void
    {
        $engineName = 'non_existing_engine';
        $exceptionMessage = 'Service not found';

        $this->pdfEngineLocator
            ->expects(self::once())
            ->method('get')
            ->with($engineName)
            ->willThrowException(new \Exception($exceptionMessage));

        $this->expectException(PdfGeneratorException::class);
        $this->expectExceptionMessage('PDF engine "non_existing_engine" is not found: Service not found');

        $this->pdfEngineRegistry->getPdfEngine($engineName);
    }
}
