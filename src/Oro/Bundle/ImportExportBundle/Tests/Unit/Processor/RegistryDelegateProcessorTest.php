<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Processor;

use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Processor\RegistryDelegateProcessor;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Processor\Mocks\ClassWithCloseMethod;

class RegistryDelegateProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $processorRegistry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextRegistry;

    /**
     * @var string
     */
    protected $delegateType = 'import';

    /**
     * @var RegistryDelegateProcessor
     */
    protected $processor;

    protected function setUp()
    {
        $this->processorRegistry = $this->createMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry');
        $this->contextRegistry = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextRegistry');
        $this->processor = new RegistryDelegateProcessor(
            $this->processorRegistry,
            $this->delegateType,
            $this->contextRegistry
        );
    }

    public function testSetStepExecution()
    {
        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()->getMock();
        $this->processor->setStepExecution($stepExecution);

        $this->assertAttributeEquals($stepExecution, 'stepExecution', $this->processor);
    }

    public function testProcessContextAwareProcessor()
    {
        $entityName = 'entity_name';
        $processorAlias = 'processor_alias';
        $stepExecution = $this->getMockStepExecution();
        $item = $this->createMock(\stdClass::class);

        $delegateProcessor = $this->createMock('Oro\Bundle\ImportExportBundle\Processor\ContextAwareProcessor');

        $this->processorRegistry->expects($this->once())->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->will($this->returnValue($delegateProcessor));

        $context = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $this->contextRegistry->expects($this->once())->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

        $context->expects($this->any())
            ->method('getOption')
            ->will(
                $this->returnValueMap(
                    array(
                        array('entityName', null, $entityName),
                        array('processorAlias', null, $processorAlias),
                    )
                )
            );

        $delegateProcessor->expects($this->once())->method('setImportExportContext')->with($context);
        $delegateProcessor->expects($this->once())->method('process')->with($item);

        $this->processor->setStepExecution($stepExecution);
        $this->processor->process($item);
    }

    public function testProcessStepExecutionAwareProcessor()
    {
        $entityName = 'entity_name';
        $processorAlias = 'processor_alias';
        $stepExecution = $this->getMockStepExecution();
        $item = $this->createMock(\stdClass::class);

        $delegateProcessor = $this->createMock('Oro\Bundle\ImportExportBundle\Processor\StepExecutionAwareProcessor');

        $context = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $this->contextRegistry->expects($this->once())->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

        $context->expects($this->any())
            ->method('getOption')
            ->will(
                $this->returnValueMap(
                    array(
                        array('entityName', null, $entityName),
                        array('processorAlias', null, $processorAlias),
                    )
                )
            );

        $this->processorRegistry->expects($this->once())->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->will($this->returnValue($delegateProcessor));

        $delegateProcessor->expects($this->once())->method('setStepExecution')->with($stepExecution);
        $delegateProcessor->expects($this->once())->method('process')->with($item);

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
            ->getMockBuilder('Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface')
            ->setMethods(['process', 'setImportExportContext'])
            ->getMock()
        ;

        $this->processorRegistry->expects($this->once())->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->will($this->returnValue($delegateProcessor));

        $context = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $this->contextRegistry->expects($this->once())->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

        $context->expects($this->any())
            ->method('getOption')
            ->will(
                $this->returnValueMap(
                    array(
                        array('entityName', null, $entityName),
                        array('processorAlias', null, $processorAlias),
                    )
                )
            );

        $delegateProcessor->expects($this->never())->method('setImportExportContext');
        $delegateProcessor->expects($this->once())->method('process')->with($item);

        $this->processor->setStepExecution($stepExecution);
        $this->processor->process($item);
    }


    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Configuration of processor must contain "processorAlias" options.
     */
    public function testProcessFailsWhenNoConfigurationProvided()
    {
        $context = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');

        $stepExecution = $this->getMockStepExecution(array());

        $this->contextRegistry->expects($this->once())->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

        $context->expects($this->any())
            ->method('getOption')
            ->will(
                $this->returnValueMap(
                    array(
                        array('entityName', null, null),
                        array('processorAlias', null, null),
                    )
                )
            );
        $this->processor->setStepExecution($stepExecution);
        $this->processor->process($this->createMock(\stdClass::class));
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Step execution entity must be injected to processor.
     */
    public function testProcessFailsWhenNoStepExecution()
    {
        $this->processor->process($this->createMock(\stdClass::class));
    }

    public function testClose()
    {
        $entityName = 'entity_name';
        $processorAlias = 'processor_alias';
        $stepExecution = $this->getMockStepExecution();

        $delegateProcessor = $this->createMock(ClosableInterface::class);
        $delegateProcessor->expects($this->once())
            ->method('close');

        $this->processorRegistry->expects($this->once())->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->will($this->returnValue($delegateProcessor));

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects($this->once())->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

        $context->expects($this->any())
            ->method('getOption')
            ->will(
                $this->returnValueMap(
                    array(
                        array('entityName', null, $entityName),
                        array('processorAlias', null, $processorAlias),
                    )
                )
            );

        $this->processor->setStepExecution($stepExecution);
        $this->processor->close();
    }

    public function testCloseNoClosableInterface()
    {
        $entityName = 'entity_name';
        $processorAlias = 'processor_alias';
        $stepExecution = $this->getMockStepExecution();

        $delegateProcessor = $this->createMock(ClassWithCloseMethod::class);

        $this->processorRegistry->expects($this->once())->method('getProcessor')
            ->with($this->delegateType, $processorAlias)
            ->will($this->returnValue($delegateProcessor));

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects($this->once())->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

        $context->expects($this->any())
            ->method('getOption')
            ->will(
                $this->returnValueMap(
                    array(
                        array('entityName', null, $entityName),
                        array('processorAlias', null, $processorAlias),
                    )
                )
            );

        $this->processor->setStepExecution($stepExecution);
        $this->processor->close();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockStepExecution()
    {
        return $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
