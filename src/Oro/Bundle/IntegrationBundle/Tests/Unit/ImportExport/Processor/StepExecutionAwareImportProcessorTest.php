<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Processor;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Processor\ImportProcessorTest;
use Oro\Bundle\IntegrationBundle\ImportExport\Processor\StepExecutionAwareImportProcessor;

class StepExecutionAwareImportProcessorTest extends ImportProcessorTest
{
    /**
     * @var StepExecutionAwareImportProcessor
     */
    protected $processor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|StepExecution
     */
    protected $stepExecution;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContextRegistry
     */
    protected $contextRegistry;

    protected function setUp()
    {
        parent::setUp();

        $this->stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextRegistry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new StepExecutionAwareImportProcessor();
        $this->processor->setSerializer($this->serializer);
        $this->processor->setImportExportContext($this->context);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Missing ContextRegistry
     */
    public function testSetStepExecutionWithoutContextRegistry()
    {
        $this->processor->setStepExecution($this->stepExecution);
    }

    public function testSetStepExecution()
    {
        $this->processor->setContextRegistry($this->contextRegistry);

        $this->contextRegistry->expects($this->once())
            ->method('getByStepExecution')
            ->will($this->returnValue($this->context));

        $this->processor->setStepExecution($this->stepExecution);
    }
}
