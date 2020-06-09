<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Processor;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Processor\ContextAwareProcessor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Processor\RegistryDelegateProcessor;
use Oro\Bundle\ImportExportBundle\Processor\StepExecutionAwareProcessor;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Processor\Mocks\ClassWithCloseMethod;
use PHPUnit\Framework\MockObject\MockObject;

class RegistryDelegateProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject */
    protected $processorRegistry;

    /** @var MockObject */
    protected $contextRegistry;

    /** @var string */
    protected $delegateType = 'import';

    /** @var RegistryDelegateProcessor */
    protected $processor;

    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ProcessorRegistry::class);
        $this->contextRegistry = $this->createMock(ContextRegistry::class);

        $this->processor = new class(
            $this->processorRegistry,
            $this->delegateType,
            $this->contextRegistry
        ) extends RegistryDelegateProcessor {
            public function xgetStepExecution(): StepExecution
            {
                return $this->stepExecution;
            }
        };
    }

    public function testSetStepExecution()
    {
        /** @var StepExecution|MockObject $stepExecution */
        $stepExecution = $this->getMockBuilder(StepExecution::class)->disableOriginalConstructor()->getMock();

        $this->processor->setStepExecution($stepExecution);

        static::assertEquals($stepExecution, $this->processor->xgetStepExecution());
    }

    public function testProcessContextAwareProcessor()
    {
        $entityName = 'entity_name';
        $processorAlias = 'processor_alias';
        $stepExecution = $this->getMockStepExecution();
        $item = $this->createMock(\stdClass::class);

        $delegateProcessor = $this->createMock(ContextAwareProcessor::class);

        $this->processorRegistry->expects(static::once())
            ->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->willReturn($delegateProcessor);

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects(static::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $context->method('getOption')
            ->willReturnMap([
                ['entityName', null, $entityName],
                ['processorAlias', null, $processorAlias],
            ]);

        $delegateProcessor->expects(static::once())->method('setImportExportContext')->with($context);
        $delegateProcessor->expects(static::once())->method('process')->with($item);

        $this->processor->setStepExecution($stepExecution);
        $this->processor->process($item);
    }

    public function testProcessStepExecutionAwareProcessor()
    {
        $entityName = 'entity_name';
        $processorAlias = 'processor_alias';
        $stepExecution = $this->getMockStepExecution();
        $item = $this->createMock(\stdClass::class);

        $delegateProcessor = $this->createMock(StepExecutionAwareProcessor::class);

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects(static::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $context->method('getOption')
            ->willReturnMap([
                ['entityName', null, $entityName],
                ['processorAlias', null, $processorAlias],
            ]);

        $this->processorRegistry->expects(static::once())
            ->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->willReturn($delegateProcessor);

        $delegateProcessor->expects(static::once())->method('setStepExecution')->with($stepExecution);
        $delegateProcessor->expects(static::once())->method('process')->with($item);

        $this->processor->setStepExecution($stepExecution);
        $this->processor->process($item);
    }

    public function testProcessSimpleProcessor()
    {
        $entityName = 'entity_name';
        $processorAlias = 'processor_alias';
        $stepExecution = $this->getMockStepExecution();
        $item = $this->createMock(\stdClass::class);

        $delegateProcessor = $this
            ->getMockBuilder(ProcessorInterface::class)
            ->onlyMethods(['process'])
            ->addMethods(['setImportExportContext'])
            ->getMock();

        $this->processorRegistry->expects(static::once())
            ->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->willReturn($delegateProcessor);

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects(static::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $context->method('getOption')
            ->willReturnMap([
                ['entityName', null, $entityName],
                ['processorAlias', null, $processorAlias],
            ]);

        $delegateProcessor->expects(static::never())->method('setImportExportContext');
        $delegateProcessor->expects(static::once())->method('process')->with($item);

        $this->processor->setStepExecution($stepExecution);
        $this->processor->process($item);
    }

    public function testProcessFailsWhenNoConfigurationProvided()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration of processor must contain "processorAlias" options.');

        $context = $this->createMock(ContextInterface::class);

        $stepExecution = $this->getMockStepExecution();

        $this->contextRegistry->expects(static::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $context->method('getOption')
            ->willReturnMap([
                ['processorAlias', null, null],
                ['entityName', null, null],
            ]);

        $this->processor->setStepExecution($stepExecution);
        $this->processor->process($this->createMock(\stdClass::class));
    }

    public function testProcessFailsWhenNoStepExecution()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Step execution entity must be injected to processor.');

        $this->processor->process($this->createMock(\stdClass::class));
    }

    public function testClose()
    {
        $entityName = 'entity_name';
        $processorAlias = 'processor_alias';
        $stepExecution = $this->getMockStepExecution();

        $delegateProcessor = $this->createMock(ClosableInterface::class);
        $delegateProcessor->expects(static::once())->method('close');

        $this->processorRegistry->expects(static::once())
            ->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->willReturn($delegateProcessor);

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects(static::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $context->method('getOption')
            ->willReturnMap([
                ['entityName', null, $entityName],
                ['processorAlias', null, $processorAlias],
            ]);

        $this->processor->setStepExecution($stepExecution);
        $this->processor->close();
    }

    public function testCloseNoClosableInterface()
    {
        $entityName = 'entity_name';
        $processorAlias = 'processor_alias';
        $stepExecution = $this->getMockStepExecution();

        $delegateProcessor = $this->createMock(ClassWithCloseMethod::class);

        $this->processorRegistry->expects(static::once())
            ->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->willReturn($delegateProcessor);

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects(static::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $context->method('getOption')
            ->willReturnMap([
                ['entityName', null, $entityName],
                ['processorAlias', null, $processorAlias],
            ]);

        $this->processor->setStepExecution($stepExecution);
        $this->processor->close();
    }

    /**
     * @return MockObject|StepExecution
     */
    protected function getMockStepExecution()
    {
        return $this->getMockBuilder(StepExecution::class)->disableOriginalConstructor()->getMock();
    }
}
