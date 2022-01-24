<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor;

use Oro\Bundle\ApiBundle\Batch\Processor\BatchUpdateItemProcessor;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use Oro\Component\Testing\Logger\BufferingLogger;

class BatchUpdateItemProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ProcessorRegistryInterface */
    private $processorRegistry;

    /** @var ProcessorBagConfigBuilder */
    private $processorBagConfigBuilder;

    /** @var ProcessorBag */
    private $processorBag;

    /** @var BatchUpdateItemProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ProcessorRegistryInterface::class);
        $this->processorBagConfigBuilder = new ProcessorBagConfigBuilder();
        $this->processorBag = new ProcessorBag($this->processorBagConfigBuilder, $this->processorRegistry);
        $this->processorBagConfigBuilder->addGroup('initialize', 'batch_update_item', -1);
        $this->processorBagConfigBuilder->addGroup(ApiActionGroup::NORMALIZE_RESULT, 'batch_update_item', -2);

        $this->processor = new BatchUpdateItemProcessor($this->processorBag, 'batch_update_item');
    }

    private function getContext(): BatchUpdateItemContext
    {
        $context = new BatchUpdateItemContext();
        $context->setAction('batch_update_item');
        $context->getRequestType()->add(RequestType::REST);
        $context->getRequestType()->add(RequestType::JSON_API);
        $context->setVersion('1.2');
        $context->setClassName('Test\Entity');

        return $context;
    }

    private function setLogger(): BufferingLogger
    {
        $logger = new BufferingLogger();
        $this->processor->setLogger($logger);

        return $logger;
    }

    /**
     * @param array $processors [processorId => groupName, ...]
     *
     * @return ProcessorInterface[]|\PHPUnit\Framework\MockObject\MockObject[]
     */
    private function addProcessors(array $processors): array
    {
        $createdProcessors = [];
        $processorRegistryMap = [];
        foreach ($processors as $processorId => $groupName) {
            $this->processorBagConfigBuilder->addProcessor(
                $processorId,
                [],
                'batch_update_item',
                $groupName
            );
            $processor = $this->createMock(ProcessorInterface::class);
            $createdProcessors[] = $processor;
            $processorRegistryMap[] = [$processorId, $processor];
        }
        $this->processorRegistry->expects(self::any())
            ->method('getProcessor')
            ->willReturnMap($processorRegistryMap);

        return $createdProcessors;
    }

    public function testCreateContextObject()
    {
        self::assertInstanceOf(BatchUpdateItemContext::class, $this->processor->createContext());
    }

    public function testGetLogContext()
    {
        $logger = $this->setLogger();

        $context = $this->getContext();
        $context->setTargetAction('create');
        $context->setId(123);

        $exception = new \Exception('test exception');

        [$processor1, $processor2, $processor10] = $this->addProcessors([
            'processor1'  => 'initialize',
            'processor2'  => 'initialize',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);
        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        $context->setFirstGroup('initialize');
        $context->setLastGroup('initialize');
        $this->processor->process($context);

        self::assertEquals(
            [
                [
                    'error',
                    'The execution of "processor1" processor is failed.',
                    [
                        'exception'    => $exception,
                        'action'       => 'batch_update_item',
                        'requestType'  => 'rest,json_api',
                        'version'      => '1.2',
                        'class'        => 'Test\Entity',
                        'id'           => 123,
                        'targetAction' => 'create'
                    ]
                ]
            ],
            $logger->cleanLogs()
        );
    }

    public function testGetLogContextWhenContextDoesNotContainEntityId()
    {
        $logger = $this->setLogger();

        $context = $this->getContext();
        $context->setTargetAction('create');

        $exception = new \Exception('test exception');

        [$processor1, $processor2, $processor10] = $this->addProcessors([
            'processor1'  => 'initialize',
            'processor2'  => 'initialize',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);
        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        $context->setFirstGroup('initialize');
        $context->setLastGroup('initialize');
        $this->processor->process($context);

        self::assertEquals(
            [
                [
                    'error',
                    'The execution of "processor1" processor is failed.',
                    [
                        'exception'    => $exception,
                        'action'       => 'batch_update_item',
                        'requestType'  => 'rest,json_api',
                        'version'      => '1.2',
                        'class'        => 'Test\Entity',
                        'targetAction' => 'create'
                    ]
                ]
            ],
            $logger->cleanLogs()
        );
    }
}
