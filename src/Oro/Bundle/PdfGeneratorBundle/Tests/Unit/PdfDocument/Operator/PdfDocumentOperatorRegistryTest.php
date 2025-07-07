<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\Operator;

use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Operator\PdfDocumentOperatorInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Operator\PdfDocumentOperatorRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class PdfDocumentOperatorRegistryTest extends TestCase
{
    public function testGetOperatorForSpecificEntityAndMode(): void
    {
        $entityClass = 'Acme\Entity\Sample';
        $pdfGenerationMode = 'default';

        $pdfDocumentOperator = $this->createMock(PdfDocumentOperatorInterface::class);
        $pdfDocumentOperatorByModeLocator = $this->createMock(ContainerInterface::class);
        $pdfDocumentOperatorByModeLocator
            ->expects(self::once())
            ->method('has')
            ->with($pdfGenerationMode)
            ->willReturn(true);
        $pdfDocumentOperatorByModeLocator
            ->expects(self::once())
            ->method('get')
            ->with($pdfGenerationMode)
            ->willReturn($pdfDocumentOperator);

        $pdfDocumentOperatorLocator = $this->createMock(ContainerInterface::class);
        $pdfDocumentOperatorLocator
            ->expects(self::once())
            ->method('has')
            ->with($entityClass)
            ->willReturn(true);
        $pdfDocumentOperatorLocator
            ->expects(self::once())
            ->method('get')
            ->with($entityClass)
            ->willReturn($pdfDocumentOperatorByModeLocator);

        $registry = new PdfDocumentOperatorRegistry($pdfDocumentOperatorLocator);

        $result = $registry->getOperator($entityClass, $pdfGenerationMode);

        self::assertSame($pdfDocumentOperator, $result);
    }

    public function testGetOperatorFallsBackToDefault(): void
    {
        $entityClass = 'App\Entity\Unknown';
        $pdfGenerationMode = 'default';

        $pdfDocumentOperator = $this->createMock(PdfDocumentOperatorInterface::class);
        $pdfDocumentOperatorByModeLocator = $this->createMock(ContainerInterface::class);
        $pdfDocumentOperatorByModeLocator
            ->expects(self::once())
            ->method('has')
            ->with($pdfGenerationMode)
            ->willReturn(true);
        $pdfDocumentOperatorByModeLocator
            ->expects(self::once())
            ->method('get')
            ->with($pdfGenerationMode)
            ->willReturn($pdfDocumentOperator);

        $pdfDocumentOperatorLocator = $this->createMock(ContainerInterface::class);
        $pdfDocumentOperatorLocator
            ->expects(self::once())
            ->method('has')
            ->with($entityClass)
            ->willReturn(false);
        $pdfDocumentOperatorLocator
            ->expects(self::once())
            ->method('get')
            ->with(PdfDocumentOperatorRegistry::DEFAULT)
            ->willReturn($pdfDocumentOperatorByModeLocator);

        $registry = new PdfDocumentOperatorRegistry($pdfDocumentOperatorLocator);

        $result = $registry->getOperator($entityClass, $pdfGenerationMode);

        self::assertSame($pdfDocumentOperator, $result);
    }

    public function testGetOperatorThrowsExceptionWhenNoOperatorFound(): void
    {
        $entityClass = 'Acme\Entity\Sample';
        $pdfGenerationMode = 'nonexistent';

        $pdfDocumentOperatorByModeLocator = $this->createMock(ContainerInterface::class);
        $pdfDocumentOperatorByModeLocator
            ->expects(self::once())
            ->method('has')
            ->with($pdfGenerationMode)
            ->willReturn(false);

        $pdfDocumentOperatorLocator = $this->createMock(ContainerInterface::class);
        $pdfDocumentOperatorLocator
            ->expects(self::once())
            ->method('has')
            ->with($entityClass)
            ->willReturn(true);
        $pdfDocumentOperatorLocator
            ->expects(self::once())
            ->method('get')
            ->with($entityClass)
            ->willReturn($pdfDocumentOperatorByModeLocator);

        $registry = new PdfDocumentOperatorRegistry($pdfDocumentOperatorLocator);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'No PDF document operator found for entity class "%s" and PDF generation mode "%s".',
                $entityClass,
                $pdfGenerationMode
            )
        );

        $registry->getOperator($entityClass, $pdfGenerationMode);
    }

    public function testHasOperatorReturnsTrueForSpecificEntityAndMode(): void
    {
        $entityClass = 'Acme\Entity\Sample';
        $pdfGenerationMode = 'default';

        $pdfDocumentOperatorByModeLocator = $this->createMock(ContainerInterface::class);
        $pdfDocumentOperatorByModeLocator
            ->expects(self::once())
            ->method('has')
            ->with($pdfGenerationMode)
            ->willReturn(true);

        $pdfDocumentOperatorLocator = $this->createMock(ContainerInterface::class);
        $pdfDocumentOperatorLocator
            ->expects(self::once())
            ->method('has')
            ->with($entityClass)
            ->willReturn(true);
        $pdfDocumentOperatorLocator
            ->expects(self::once())
            ->method('get')
            ->with($entityClass)
            ->willReturn($pdfDocumentOperatorByModeLocator);

        $registry = new PdfDocumentOperatorRegistry($pdfDocumentOperatorLocator);

        $result = $registry->hasOperator($entityClass, $pdfGenerationMode);

        self::assertTrue($result);
    }

    public function testHasOperatorReturnsFalseWhenEntityClassNotFound(): void
    {
        $entityClass = 'App\Entity\Unknown';
        $pdfGenerationMode = 'default';

        $pdfDocumentOperatorLocator = $this->createMock(ContainerInterface::class);
        $pdfDocumentOperatorLocator
            ->expects(self::once())
            ->method('has')
            ->with($entityClass)
            ->willReturn(false);

        $registry = new PdfDocumentOperatorRegistry($pdfDocumentOperatorLocator);

        $result = $registry->hasOperator($entityClass, $pdfGenerationMode);

        self::assertFalse($result);
    }

    public function testHasOperatorReturnsFalseWhenModeNotFound(): void
    {
        $entityClass = 'Acme\Entity\Sample';
        $pdfGenerationMode = 'nonexistent';

        $pdfDocumentOperatorByModeLocator = $this->createMock(ContainerInterface::class);
        $pdfDocumentOperatorByModeLocator
            ->expects(self::once())
            ->method('has')
            ->with($pdfGenerationMode)
            ->willReturn(false);

        $pdfDocumentOperatorLocator = $this->createMock(ContainerInterface::class);
        $pdfDocumentOperatorLocator
            ->expects(self::once())
            ->method('has')
            ->with($entityClass)
            ->willReturn(true);
        $pdfDocumentOperatorLocator
            ->expects(self::once())
            ->method('get')
            ->with($entityClass)
            ->willReturn($pdfDocumentOperatorByModeLocator);

        $registry = new PdfDocumentOperatorRegistry($pdfDocumentOperatorLocator);

        $result = $registry->hasOperator($entityClass, $pdfGenerationMode);

        self::assertFalse($result);
    }
}
