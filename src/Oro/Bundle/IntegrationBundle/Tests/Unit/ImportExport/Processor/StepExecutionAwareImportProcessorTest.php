<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Processor;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Processor\ImportProcessorTest;
use Oro\Bundle\IntegrationBundle\ImportExport\Processor\StepExecutionAwareImportProcessor;
use PHPUnit\Framework\MockObject\MockObject;

class StepExecutionAwareImportProcessorTest extends ImportProcessorTest
{
    /** @var StepExecutionAwareImportProcessor */
    protected $processor;

    /** @var MockObject|StepExecution */
    protected $stepExecution;

    /** @var MockObject|ContextRegistry */
    protected $contextRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stepExecution = $this->getMockBuilder(StepExecution::class)->disableOriginalConstructor()->getMock();
        $this->contextRegistry = $this->getMockBuilder(ContextRegistry::class)->disableOriginalConstructor()->getMock();

        $this->processor = new class() extends StepExecutionAwareImportProcessor {
            public function xgetEntityName(): string
            {
                return $this->entityName;
            }
        };

        $this->processor->setSerializer($this->serializer);
        $this->processor->setImportExportContext($this->context);
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

        $this->contextRegistry->expects(static::once())
            ->method('getByStepExecution')
            ->willReturn($this->context);

        $this->processor->setStepExecution($this->stepExecution);
    }
}
