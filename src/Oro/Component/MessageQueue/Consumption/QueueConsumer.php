<?php

namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Client\MessageProcessorRegistryInterface;
use Oro\Component\MessageQueue\Consumption\Exception\ConsumptionInterruptedException;
use Oro\Component\MessageQueue\Consumption\Exception\RejectMessageExceptionInterface;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\PhpUtils\Formatter\BytesFormatter;
use Psr\Log\NullLogger;

/**
 * Consuming messages from a queue
 *
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class QueueConsumer
{
    private ConnectionInterface $connection;

    private ExtensionInterface $extension;

    private ConsumerState $consumerState;

    private MessageProcessorRegistryInterface $messageProcessorRegistry;

    private int $idleMicroseconds;

    private array $boundMessageProcessors;

    /**
     * @param ConnectionInterface $connection
     * @param ExtensionInterface $extension
     * @param ConsumerState $consumerState
     * @param MessageProcessorRegistryInterface $messageProcessorRegistry
     * @param int $idleMicroseconds 100ms by default
     */
    public function __construct(
        ConnectionInterface $connection,
        ExtensionInterface $extension,
        ConsumerState $consumerState,
        MessageProcessorRegistryInterface $messageProcessorRegistry,
        int $idleMicroseconds = 100000
    ) {
        $this->connection = $connection;
        $this->extension = $extension;
        $this->consumerState = $consumerState;
        $this->messageProcessorRegistry = $messageProcessorRegistry;
        $this->idleMicroseconds = $idleMicroseconds;

        $this->boundMessageProcessors = [];
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Binds consumer to the specified queue and message processor.
     *
     * @param string $queueName
     * @param string $messageProcessorName
     *
     * @return self
     */
    public function bind(string $queueName, string $messageProcessorName = '')
    {
        if (empty($queueName)) {
            throw new \LogicException('The queue name must be not empty.');
        }
        if (array_key_exists($queueName, $this->boundMessageProcessors)) {
            throw new \LogicException(sprintf('The queue was already bound. Queue: %s', $queueName));
        }

        $this->boundMessageProcessors[$queueName] = $messageProcessorName;

        return $this;
    }

    /**
     * Runtime extension - is an extension or a collection of extensions which could be set on runtime.
     * Here's a good example: @see LimitsExtensionsCommandTrait
     *
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function consume(ExtensionInterface $runtimeExtension = null)
    {
        $session = $this->connection->createSession();

        /** @var MessageConsumerInterface[] $messageConsumers */
        $messageConsumers = [];
        foreach ($this->boundMessageProcessors as $queueName => $messageProcessorName) {
            $queue = $session->createQueue($queueName);
            $messageConsumers[$queueName] = $session->createConsumer($queue);
        }

        $context = new Context($session);

        $extension = $this->extension;
        if (null !== $runtimeExtension) {
            $extension = new ChainExtension([$runtimeExtension, $extension]);
        }
        $extension->onStart($context);

        $logger = $context->getLogger() ?: new NullLogger();
        $logger->info('Start consuming');

        while (true) {
            foreach ($this->boundMessageProcessors as $queueName => $messageProcessorName) {
                try {
                    $logger->debug(sprintf('Switch to a queue %s', $queueName));

                    $context = new Context($session);
                    $context->setLogger($logger);
                    $context->setQueueName($queueName);
                    $context->setMessageConsumer($messageConsumers[$queueName]);
                    $context->setMessageProcessorName($messageProcessorName);

                    $this->doConsume($extension, $context);
                } catch (ConsumptionInterruptedException $e) {
                    $logger->warning(\sprintf(
                        'Consuming interrupted. Queue: "%s", reason: "%s"',
                        $queueName,
                        $e->getMessage()
                    ));

                    $extension->onInterrupted($context);
                    $session->close();

                    return;
                } catch (RejectMessageExceptionInterface $exception) {
                    $context->setException($exception);
                    $context->getMessageConsumer()->reject($context->getMessage());
                    $session->close();
                    throw $exception;
                } catch (\Exception $exception) {
                    $context->setExecutionInterrupted(true);
                    $context->setException($exception);

                    try {
                        $this->onInterruptionByException($extension, $context);
                        $session->close();
                    } catch (\Exception $e) {
                        // for some reason finally does not work here on php5.5
                        $session->close();

                        throw $e;
                    }
                }
            }
        }
    }

    /**
     * @param ExtensionInterface $extension
     * @param Context $context
     *
     * @throws ConsumptionInterruptedException
     */
    protected function doConsume(ExtensionInterface $extension, Context $context): void
    {
        $session = $context->getSession();
        $messageConsumer = $context->getMessageConsumer();
        $logger = $context->getLogger();

        $extension->onBeforeReceive($context);

        if ($context->isExecutionInterrupted()) {
            throw new ConsumptionInterruptedException($context->getInterruptedReason());
        }
        $logger->debug('Pre receive Message');
        $message = $messageConsumer->receive(1);
        if (null !== $message) {
            $context->setMessage($message);
            $extension->onPreReceived($context);

            $logger->info('Message received', [
                'headers'    => $message->getHeaders(),
                'properties' => $message->getProperties()
            ]);

            $executionTime = 0;
            if (!$context->getStatus()) {
                $startTime = (int)(microtime(true) * 1000);

                $status = $this->messageProcessorRegistry
                    ->get($context->getMessageProcessorName())
                    ->process($message, $session);

                $executionTime = (int)(microtime(true) * 1000) - $startTime;
                $context->setStatus($status);
            }

            switch ($context->getStatus()) {
                case MessageProcessorInterface::ACK:
                    $messageConsumer->acknowledge($message);
                    $statusForLog = 'ACK';
                    break;
                case MessageProcessorInterface::REJECT:
                    $messageConsumer->reject($message, false);
                    $statusForLog = 'REJECT';
                    break;
                case MessageProcessorInterface::REQUEUE:
                    $messageConsumer->reject($message, true);
                    $statusForLog = 'REQUEUE';
                    break;
                default:
                    throw new \LogicException(sprintf('Status is not supported: %s', $context->getStatus()));
            }

            $loggerContext = [
                'status' => $statusForLog,
                'time_taken' => $executionTime
            ];
            $this->addMemoryUsageInfo($loggerContext);
            $logger->notice('Message processed: {status}. Execution time: {time_taken} ms', $loggerContext);

            $extension->onPostReceived($context);
        } else {
            $logger->info('Idle');

            usleep($this->idleMicroseconds);
            $extension->onIdle($context);
        }

        if ($context->isExecutionInterrupted()) {
            throw new ConsumptionInterruptedException($context->getInterruptedReason());
        }
    }

    /**
     * @throws \Exception
     */
    protected function onInterruptionByException(ExtensionInterface $extension, Context $context)
    {
        $logger = $context->getLogger();

        $exception = $context->getException();
        $logger->error(
            sprintf('Consuming interrupted by exception. "%s"', $exception->getMessage()),
            ['exception' => $exception]
        );

        try {
            $extension->onInterrupted($context);
        } catch (\Exception $e) {
            // logic is similar to one in Symfony's ExceptionListener::onKernelException
            $logger->error(sprintf(
                'Exception thrown when handling an exception (%s: %s at %s line %s)',
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));

            $wrapper = $e;
            while ($prev = $wrapper->getPrevious()) {
                if ($exception === $wrapper = $prev) {
                    throw $e;
                }
            }

            $prev = new \ReflectionProperty('Exception', 'previous');
            $prev->setAccessible(true);
            $prev->setValue($wrapper, $exception);

            throw $e;
        }

        throw $exception;
    }

    /**
     * Add information about memory usage difference and peak memory usage
     */
    protected function addMemoryUsageInfo(array &$loggerContext)
    {
        $memoryTaken = memory_get_usage() - $this->consumerState->getStartMemoryUsage();
        $loggerContext['peak_memory'] = BytesFormatter::format($this->consumerState->getPeakMemory());
        $loggerContext['memory_taken'] = BytesFormatter::format($memoryTaken);
    }
}
