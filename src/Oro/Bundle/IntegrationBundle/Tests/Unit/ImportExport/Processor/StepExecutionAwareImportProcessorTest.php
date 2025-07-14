<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Processor;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Processor\ImportProcessorTest;
use Oro\Bundle\IntegrationBundle\ImportExport\Processor\StepExecutionAwareImportProcessor;
use PHPUnit\Framework\MockObject\MockObject;

class StepExecutionAwareImportProcessorTest extends ImportProcessorTest
{
    private StepExecution&MockObject $stepExecution;
    private ContextRegistry&MockObject $contextRegistry;
    /** @var StepExecutionAwareImportProcessor */
    protected $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->stepExecution = $this->createMock(StepExecution::class);
        $this->contextRegistry = $this->createMock(ContextRegistry::class);

        $this->processor = new StepExecutionAwareImportProcessor();
        $this->processor->setSerializer($this->serializer);
        $this->processor->setImportExportContext($this->context);
    }

    public function testSetStepExecutionWithoutContextRegistry(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing ContextRegistry');

        $this->processor->setStepExecution($this->stepExecution);
    }

    public function testSetStepExecution(): void
    {
        $this->processor->setContextRegistry($this->contextRegistry);

        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->willReturn($this->context);

        $this->processor->setStepExecution($this->stepExecution);
    }
}
