<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step;

use Akeneo\Bundle\BatchBundle\Job\ExitStatus;
use Oro\Bundle\BatchBundle\Step\ItemStep;
use Akeneo\Bundle\BatchBundle\Job\BatchStatus;

/**
 * Tests related to the ItemStep class
 *
 */
class ItemStepTest extends \PHPUnit_Framework_TestCase
{
    const STEP_NAME = 'test_step_name';

    /**
     * @var ItemStep
     */
    protected $itemStep = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $jobRepository = null;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface');
        $this->jobRepository   = $this->getMock('Akeneo\\Bundle\\BatchBundle\\Job\\JobRepositoryInterface');

        $this->itemStep = new ItemStep(self::STEP_NAME);

        $this->itemStep->setEventDispatcher($this->eventDispatcher);
        $this->itemStep->setJobRepository($this->jobRepository);
    }

    public function testExecute()
    {
        $stepExecution = $this->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Entity\\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $stepExecution->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(new BatchStatus(BatchStatus::STARTING)));
        $stepExecution->expects($this->any())
            ->method('getExitStatus')
            ->will($this->returnValue(new ExitStatus()));

        $reader = $this->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Step\\Stub\\ReaderStub')
            ->setMethods(array('setStepExecution', 'read'))
            ->getMock();
        $reader->expects($this->once())
            ->method('setStepExecution')
            ->with($stepExecution);
        $reader->expects($this->exactly(8))
            ->method('read')
            ->will($this->onConsecutiveCalls(1, 2, 3, 4, 5, 6, 7, null));

        $processor = $this->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Step\\Stub\\ProcessorStub')
            ->setMethods(array('setStepExecution', 'process'))
            ->getMock();
        $processor->expects($this->once())
            ->method('setStepExecution')
            ->with($stepExecution);
        $processor->expects($this->exactly(7))
            ->method('process')
            ->will($this->onConsecutiveCalls(1, 2, 3, 4, 5, 6, 7));

        $writer = $this->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Step\\Stub\\WriterStub')
            ->setMethods(array('setStepExecution', 'write'))
            ->getMock();
        $writer->expects($this->once())
            ->method('setStepExecution')
            ->with($stepExecution);
        $writer->expects($this->exactly(2))
            ->method('write');

        $this->itemStep->setReader($reader);
        $this->itemStep->setProcessor($processor);
        $this->itemStep->setWriter($writer);
        $this->itemStep->setBatchSize(5);
        $this->itemStep->execute($stepExecution);
    }

    /**
     * Assert the entity tested
     *
     * @param object $entity
     */
    protected function assertEntity($entity)
    {
        $this->assertInstanceOf('Oro\\Bundle\\BatchBundle\\Step\\ItemStep', $entity);
    }
}
