<?php

namespace Oro\Component\MessageQueue\Consumption;

use Psr\Log\NullLogger;

use Oro\Component\MessageQueue\Consumption\Exception\ConsumptionInterruptedException;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Consumption\Exception\RejectMessageExceptionInterface;

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class QueueConsumer
{
    /** @var ConnectionInterface */
    private $connection;

    /** @var ExtensionInterface */
    private $extension;

    /** @var MessageProcessorInterface[] */
    private $boundMessageProcessors;

    /** @var int */
    private $idleMicroseconds;

    /**
     * @param ConnectionInterface $connection
     * @param ExtensionInterface  $extension
     * @param int                 $idleMicroseconds 100ms by default
     */
    public function __construct(
        ConnectionInterface $connection,
        ExtensionInterface $extension,
        $idleMicroseconds = 100000
    ) {
        $this->connection = $connection;
        $this->extension = $extension;
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
     * @param string $queueName
     * @param MessageProcessorInterface $messageProcessor
     *
     * @return self
     */
    public function bind($queueName, MessageProcessorInterface $messageProcessor)
    {
        if (empty($queueName)) {
            throw new \LogicException('The queue name must be not empty.');
        }
        if (array_key_exists($queueName, $this->boundMessageProcessors)) {
            throw new \LogicException(sprintf('The queue was already bound. Queue: %s', $queueName));
        }

        $this->boundMessageProcessors[$queueName] = $messageProcessor;

        return $this;
    }

    /**
     * Runtime extension - is an extension or a collection of extensions which could be set on runtime.
     * Here's a good example: @see LimitsExtensionsCommandTrait
     *
     * @param ExtensionInterface|null $runtimeExtension
     *
     * @throws \Exception
     */
    public function consume(ExtensionInterface $runtimeExtension = null)
    {
        $session = $this->connection->createSession();

        /** @var MessageConsumerInterface[] $messageConsumers */
        $messageConsumers = [];
        foreach ($this->boundMessageProcessors as $queueName => $messageProcessor) {
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
            try {
                foreach ($this->boundMessageProcessors as $queueName => $messageProcessor) {
                    $logger->debug(sprintf('Switch to a queue %s', $queueName));

                    $context = new Context($session);
                    $context->setLogger($logger);
                    $context->setQueueName($queueName);
                    $context->setMessageConsumer($messageConsumers[$queueName]);
                    $context->setMessageProcessor($messageProcessor);

                    $this->doConsume($extension, $context);
                }
            } catch (ConsumptionInterruptedException $e) {
                $logger->warning(sprintf('Consuming interrupted, reason: %s', $e->getMessage()));

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

    /**
     * @param ExtensionInterface $extension
     * @param Context $context
     *
     * @throws ConsumptionInterruptedException
     *
     * @return bool
     */
    protected function doConsume(ExtensionInterface $extension, Context $context)
    {
        $session = $context->getSession();
        $messageProcessor = $context->getMessageProcessor();
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
                $status = $messageProcessor->process($message, $session);
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

            $logger->notice('Message processed: {status}. Execution time: {time} ms', [
                'status' => $statusForLog,
                'time'   => $executionTime
            ]);

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
     * @param ExtensionInterface $extension
     * @param Context $context
     *
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
}
