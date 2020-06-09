<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Reader\TemplateFixtureReader;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;
use PHPUnit\Framework\MockObject\MockObject;

class TemplateFixtureReaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject|TemplateManager */
    protected $templateManager;

    /**
     * @var MockObject|ContextRegistry
     */
    protected $contextRegistry;

    /** @var TemplateFixtureReader */
    protected $reader;

    protected function setUp(): void
    {
        $this->templateManager = $this->getMockBuilder(TemplateManager::class)->disableOriginalConstructor()->getMock();
        $this->contextRegistry = $this->getMockBuilder(ContextRegistry::class)->disableOriginalConstructor()->getMock();

        $this->reader = new class($this->contextRegistry, $this->templateManager) extends TemplateFixtureReader {
            public function xgetStepExecution(): ?StepExecution
            {
                return $this->stepExecution;
            }
        };
    }

    public function testInitializeFromContextExceptionNoOption()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration of fixture reader must contain "entityName".');

        $context = $this->createMock(ContextInterface::class);
        $context->expects(static::once())
            ->method('hasOption')
            ->with('entityName')
            ->willReturn(false);

        /** @var StepExecution|MockObject $stepExecution */
        $stepExecution = $this->getMockBuilder(StepExecution::class)->disableOriginalConstructor()->getMock();

        $this->contextRegistry->expects(static::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $this->reader->setStepExecution($stepExecution);
    }

    public function testInitializeFromContext()
    {
        $context = $this->createMock(ContextInterface::class);
        $context->expects(static::once())
            ->method('hasOption')
            ->with('entityName')
            ->willReturn(true);
        $context->expects(static::atLeastOnce())
            ->method('getOption')
            ->with('entityName')
            ->willReturn('stdClass');

        /** @var StepExecution|MockObject $stepExecution */
        $stepExecution = $this->getMockBuilder(StepExecution::class)->disableOriginalConstructor()->getMock();

        $this->contextRegistry->expects(static::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $iterator = new \ArrayIterator(['test']);
        $fixture = $this->createMock(TemplateFixtureInterface::class);
        $fixture->expects(static::once())->method('getData')->willReturn($iterator);
        $this->templateManager->expects(static::once())
            ->method('getEntityFixture')
            ->with('stdClass')
            ->willReturn($fixture);

        $this->reader->setStepExecution($stepExecution);
        static::assertEquals($iterator, $this->reader->getSourceIterator());
    }
}
