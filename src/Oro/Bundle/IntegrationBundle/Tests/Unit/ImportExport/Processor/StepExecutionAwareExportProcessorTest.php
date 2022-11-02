<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Processor;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Processor\ExportProcessorTest;
use Oro\Bundle\IntegrationBundle\ImportExport\Processor\StepExecutionAwareExportProcessor;

class StepExecutionAwareExportProcessorTest extends ExportProcessorTest
{
    /** @var StepExecution|\PHPUnit\Framework\MockObject\MockObject */
    private $stepExecution;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var StepExecutionAwareExportProcessor */
    protected $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stepExecution = $this->createMock(StepExecution::class);
        $this->contextRegistry = $this->createMock(ContextRegistry::class);

        $this->processor = new StepExecutionAwareExportProcessor();
    }

    public function testSetStepExecutionWithoutContextRegistry()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing ContextRegistry');

        $this->processor->setStepExecution($this->stepExecution);
    }

    public function testSetStepExecution()
    {
        $this->processor->setContextRegistry($this->contextRegistry);

        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->willReturn($this->context);

        $this->processor->setStepExecution($this->stepExecution);
    }
}
