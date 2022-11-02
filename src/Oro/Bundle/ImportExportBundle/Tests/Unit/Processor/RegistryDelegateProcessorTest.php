<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Processor;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
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
use Oro\Component\Testing\ReflectionUtil;

class RegistryDelegateProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $processorRegistry;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var string */
    private $delegateType = 'import';

    /** @var RegistryDelegateProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ProcessorRegistry::class);
        $this->contextRegistry = $this->createMock(ContextRegistry::class);

        $this->processor = new RegistryDelegateProcessor(
            $this->processorRegistry,
            $this->delegateType,
            $this->contextRegistry
        );
    }

    public function testSetStepExecution()
    {
        $stepExecution = $this->createMock(StepExecution::class);

        $this->processor->setStepExecution($stepExecution);

        self::assertEquals($stepExecution, ReflectionUtil::getPropertyValue($this->processor, 'stepExecution'));
    }

    public function testProcessContextAwareProcessor()
    {
        $entityName = 'entity_name';
        $processorAlias = 'processor_alias';
        $stepExecution = $this->createMock(StepExecution::class);
        $item = $this->createMock(\stdClass::class);

        $delegateProcessor = $this->createMock(ContextAwareProcessor::class);

        $this->processorRegistry->expects(self::once())
            ->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->willReturn($delegateProcessor);

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $context->expects(self::any())
            ->method('getOption')
            ->willReturnMap([
                ['entityName', null, $entityName],
                ['processorAlias', null, $processorAlias],
            ]);

        $delegateProcessor->expects(self::once())
            ->method('setImportExportContext')
            ->with($context);
        $delegateProcessor->expects(self::once())
            ->method('process')
            ->with($item);

        $this->processor->setStepExecution($stepExecution);
        $this->processor->process($item);
    }

    public function testProcessStepExecutionAwareProcessor()
    {
        $entityName = 'entity_name';
        $processorAlias = 'processor_alias';
        $stepExecution = $this->createMock(StepExecution::class);
        $item = $this->createMock(\stdClass::class);

        $delegateProcessor = $this->createMock(StepExecutionAwareProcessor::class);

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $context->expects(self::any())
            ->method('getOption')
            ->willReturnMap([
                ['entityName', null, $entityName],
                ['processorAlias', null, $processorAlias],
            ]);

        $this->processorRegistry->expects(self::once())
            ->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->willReturn($delegateProcessor);

        $delegateProcessor->expects(self::once())
            ->method('setStepExecution')
            ->with($stepExecution);
        $delegateProcessor->expects(self::once())
            ->method('process')
            ->with($item);

        $this->processor->setStepExecution($stepExecution);
        $this->processor->process($item);
    }

    public function testProcessSimpleProcessor()
    {
        $entityName = 'entity_name';
        $processorAlias = 'processor_alias';
        $stepExecution = $this->createMock(StepExecution::class);
        $item = $this->createMock(\stdClass::class);

        $delegateProcessor = $this->getMockBuilder(ProcessorInterface::class)
            ->onlyMethods(['process'])
            ->addMethods(['setImportExportContext'])
            ->getMock();

        $this->processorRegistry->expects(self::once())
            ->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->willReturn($delegateProcessor);

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $context->expects(self::any())
            ->method('getOption')
            ->willReturnMap([
                ['entityName', null, $entityName],
                ['processorAlias', null, $processorAlias],
            ]);

        $delegateProcessor->expects(self::never())
            ->method('setImportExportContext');
        $delegateProcessor->expects(self::once())
            ->method('process')->with($item);

        $this->processor->setStepExecution($stepExecution);
        $this->processor->process($item);
    }

    public function testProcessFailsWhenNoConfigurationProvided()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration of processor must contain "processorAlias" options.');

        $context = $this->createMock(ContextInterface::class);

        $stepExecution = $this->createMock(StepExecution::class);

        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $context->expects(self::any())
            ->method('getOption')
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
        $stepExecution = $this->createMock(StepExecution::class);

        $delegateProcessor = $this->createMock(ClosableInterface::class);
        $delegateProcessor->expects(self::once())
            ->method('close');

        $this->processorRegistry->expects(self::once())
            ->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->willReturn($delegateProcessor);

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $context->expects(self::any())
            ->method('getOption')
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
        $stepExecution = $this->createMock(StepExecution::class);

        $delegateProcessor = $this->createMock(ClassWithCloseMethod::class);

        $this->processorRegistry->expects(self::once())
            ->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->willReturn($delegateProcessor);

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $context->expects(self::any())
            ->method('getOption')
            ->willReturnMap([
                ['entityName', null, $entityName],
                ['processorAlias', null, $processorAlias],
            ]);

        $this->processor->setStepExecution($stepExecution);
        $this->processor->close();
    }
}
