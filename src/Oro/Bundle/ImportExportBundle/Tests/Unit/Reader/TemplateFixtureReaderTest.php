<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Reader\TemplateFixtureReader;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;

class TemplateFixtureReaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TemplateManager|\PHPUnit\Framework\MockObject\MockObject */
    private $templateManager;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var TemplateFixtureReader */
    private $reader;

    protected function setUp(): void
    {
        $this->templateManager = $this->createMock(TemplateManager::class);
        $this->contextRegistry = $this->createMock(ContextRegistry::class);

        $this->reader = new TemplateFixtureReader($this->contextRegistry, $this->templateManager);
    }

    public function testInitializeFromContextExceptionNoOption()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration of fixture reader must contain "entityName".');

        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::once())
            ->method('hasOption')
            ->with('entityName')
            ->willReturn(false);

        $stepExecution = $this->createMock(StepExecution::class);

        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $this->reader->setStepExecution($stepExecution);
    }

    public function testInitializeFromContext()
    {
        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::once())
            ->method('hasOption')
            ->with('entityName')
            ->willReturn(true);
        $context->expects(self::atLeastOnce())
            ->method('getOption')
            ->with('entityName')
            ->willReturn('stdClass');

        $stepExecution = $this->createMock(StepExecution::class);

        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $iterator = new \ArrayIterator(['test']);
        $fixture = $this->createMock(TemplateFixtureInterface::class);
        $fixture->expects(self::once())
            ->method('getData')
            ->willReturn($iterator);
        $this->templateManager->expects(self::once())
            ->method('getEntityFixture')
            ->with('stdClass')
            ->willReturn($fixture);

        $this->reader->setStepExecution($stepExecution);
        self::assertEquals($iterator, $this->reader->getSourceIterator());
    }
}
