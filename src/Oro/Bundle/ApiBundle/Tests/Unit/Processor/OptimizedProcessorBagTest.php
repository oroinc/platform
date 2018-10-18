<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\OptimizedProcessorBag;
use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorApplicableCheckerFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;
use Oro\Component\ChainProcessor\ProcessorIteratorFactoryInterface;

class OptimizedProcessorBagTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ProcessorFactoryInterface */
    private $processorFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProcessorApplicableCheckerFactoryInterface */
    private $applicableCheckerFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProcessorIteratorFactoryInterface */
    private $processorIteratorFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProcessorApplicableCheckerFactoryInterface */
    private $ungroupedApplicableCheckerFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProcessorIteratorFactoryInterface */
    private $ungroupedProcessorIteratorFactory;

    /** @var ChainApplicableChecker */
    private $applicableChecker;

    /** @var ChainApplicableChecker */
    private $ungroupedApplicableChecker;

    /** @var OptimizedProcessorBag */
    private $processorBag;

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

        $processorBagConfigBuilder = new ProcessorBagConfigBuilder();
        $this->processorBag = new OptimizedProcessorBag(
            $processorBagConfigBuilder,
            $this->processorFactory,
            false,
            $this->applicableCheckerFactory,
            $this->processorIteratorFactory,
            $this->ungroupedApplicableCheckerFactory,
            $this->ungroupedProcessorIteratorFactory
        );

        $processorBagConfigBuilder->addGroup('group1', 'action_with_groups');
        $processorBagConfigBuilder->addProcessor('processor1', [], 'action_with_groups', 'group1');
        $processorBagConfigBuilder->addProcessor('processor2', [], 'action_without_groups');
    }

    public function testBagWithGroups()
    {
        $context = new Context();
        $context->setAction('action_with_groups');

        $iterator = $this->createMock(ProcessorIterator::class);

        $this->processorIteratorFactory->expects(self::once())
            ->method('createProcessorIterator')
            ->with(
                [['processor1', ['group' => 'group1']]],
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
                [['processor2', []]],
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
