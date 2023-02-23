<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\TransitActionProcessor;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransitActionProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessorBagConfigBuilder */
    private $processorBagConfigBuilder;

    /** @var ProcessorRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $processorRegistry;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var TransitActionProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processorBagConfigBuilder = new ProcessorBagConfigBuilder();
        $this->processorRegistry = $this->createMock(ProcessorRegistryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new TransitActionProcessor(
            new ProcessorBag($this->processorBagConfigBuilder, $this->processorRegistry),
            $this->logger
        );
    }

    public function testCreateContext()
    {
        self::assertInstanceOf(TransitionContext::class, $this->processor->createContext());
    }

    public function testExecuteProcessors()
    {
        $processor1 = $this->createMock(ProcessorInterface::class);
        $processor2 = $this->createMock(ProcessorInterface::class);

        $this->processorRegistry->expects(self::exactly(2))
            ->method('getProcessor')
            ->willReturnOnConsecutiveCalls($processor1, $processor2);

        $this->processorBagConfigBuilder->addProcessor('processor1', [], 'action1', null, 20);
        $this->processorBagConfigBuilder->addProcessor('processor2', [], 'action1', null, 10);

        $context = new TransitionContext();
        $context->setAction('action1');

        $this->logger->expects(self::exactly(4))
            ->method('debug')
            ->withConsecutive(
                ['Execute processor {processorId}', ['processorId' => 'processor1', 'processorAttributes' => []]],
                ['Context processed.', ['context' => $context->toArray()]],
                ['Execute processor {processorId}', ['processorId' => 'processor2', 'processorAttributes' => []]],
                ['Context processed.', ['context' => $context->toArray()]]
            );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        $this->processor->process($context);
    }

    public function testExecuteProcessorsFailure()
    {
        $processor1 = $this->createMock(ProcessorInterface::class);
        $processor2 = $this->createMock(ProcessorInterface::class);

        $this->processorRegistry->expects(self::exactly(2))
            ->method('getProcessor')
            ->willReturnOnConsecutiveCalls($processor1, $processor2);

        $this->processorBagConfigBuilder->addGroup('group1', 'action1', 20);
        $this->processorBagConfigBuilder->addGroup('group2', 'action1', 10);
        $this->processorBagConfigBuilder->addProcessor('processor1', [], 'action1', 'group1', 20);
        $this->processorBagConfigBuilder->addProcessor('processor2', [], 'action1', 'group2', 20);
        $this->processorBagConfigBuilder->addProcessor('processor3', [], 'action1', 'group2', 10);

        $context = new TransitionContext();
        $context->setAction('action1');

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException(new \Exception('Some error.'));

        try {
            $this->processor->process($context);
            self::fail('An exception expected');
        } catch (ExecutionFailedException $e) {
            self::assertEquals('Processor failed: "processor2". Reason: Some error.', $e->getMessage());
            self::assertEquals('processor2', $e->getProcessorId());
            self::assertEquals('action1', $e->getAction());
            self::assertEquals('group2', $e->getGroup());
            self::assertNotNull($e->getPrevious());
            self::assertEquals('Some error.', $e->getPrevious()->getMessage());
        } catch (\Exception $e) {
            self::fail(sprintf('ExecutionFailedException expected. Got: %s', get_class($e)));
        }
    }
}
