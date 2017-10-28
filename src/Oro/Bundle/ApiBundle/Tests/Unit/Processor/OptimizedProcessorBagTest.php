<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorApplicableCheckerFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;
use Oro\Component\ChainProcessor\ProcessorIteratorFactoryInterface;
use Oro\Bundle\ApiBundle\Processor\OptimizedProcessorBag;

class OptimizedProcessorBagTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorFactoryInterface */
    protected $processorFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorApplicableCheckerFactoryInterface */
    protected $applicableCheckerFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorIteratorFactoryInterface */
    protected $processorIteratorFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorApplicableCheckerFactoryInterface */
    protected $ungroupedApplicableCheckerFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorIteratorFactoryInterface */
    protected $ungroupedProcessorIteratorFactory;

    /** @var ChainApplicableChecker */
    protected $applicableChecker;

    /** @var ChainApplicableChecker */
    protected $ungroupedApplicableChecker;

    /** @var OptimizedProcessorBag */
    protected $processorBag;

    protected function setUp()
    {
        $this->processorFactory = $this->createMock(ProcessorFactoryInterface::class);
        $this->applicableCheckerFactory = $this->createMock(ProcessorApplicableCheckerFactoryInterface::class);
        $this->processorIteratorFactory = $this->createMock(ProcessorIteratorFactoryInterface::class);
        $this->ungroupedApplicableCheckerFactory = $this->createMock(ProcessorApplicableCheckerFactoryInterface::class);
        $this->ungroupedProcessorIteratorFactory = $this->createMock(ProcessorIteratorFactoryInterface::class);
        $this->applicableChecker = new ChainApplicableChecker();
        $this->ungroupedApplicableChecker = new ChainApplicableChecker();

        $this->applicableCheckerFactory->expects(self::any())
            ->method('createApplicableChecker')
            ->willReturn($this->applicableChecker);
        $this->ungroupedApplicableCheckerFactory->expects(self::any())
            ->method('createApplicableChecker')
            ->willReturn($this->ungroupedApplicableChecker);

        $this->processorBag = new OptimizedProcessorBag(
            $this->processorFactory,
            false,
            $this->applicableCheckerFactory,
            $this->processorIteratorFactory,
            $this->ungroupedApplicableCheckerFactory,
            $this->ungroupedProcessorIteratorFactory
        );

        $this->processorBag->addGroup('group1', 'action_with_groups');
        $this->processorBag->addProcessor('processor1', [], 'action_with_groups', 'group1');
        $this->processorBag->addProcessor('processor2', [], 'action_without_groups');
    }

    public function testBagWithGroups()
    {
        $context = new Context();
        $context->setAction('action_with_groups');

        $iterator = $this->createMock(ProcessorIterator::class);

        $this->processorIteratorFactory->expects(self::once())
            ->method('createProcessorIterator')
            ->with(
                [['processor' => 'processor1', 'attributes' => ['group' => 'group1']]],
                self::identicalTo($context),
                self::identicalTo($this->applicableChecker),
                self::identicalTo($this->processorFactory)
            )
            ->willReturn($iterator);

        self::assertSame(
            $iterator,
            $this->processorBag->getProcessors($context)
        );
    }

    public function testBagWithoutGroups()
    {
        $context = new Context();
        $context->setAction('action_without_groups');

        $iterator = $this->createMock(ProcessorIterator::class);

        $this->ungroupedProcessorIteratorFactory->expects(self::once())
            ->method('createProcessorIterator')
            ->with(
                [['processor' => 'processor2', 'attributes' => []]],
                self::identicalTo($context),
                self::identicalTo($this->ungroupedApplicableChecker),
                self::identicalTo($this->processorFactory)
            )
            ->willReturn($iterator);

        self::assertSame(
            $iterator,
            $this->processorBag->getProcessors($context)
        );
    }
}
