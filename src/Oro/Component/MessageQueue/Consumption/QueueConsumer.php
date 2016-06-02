<?php
namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Consumption\Exception\ConsumptionInterruptedException;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\NullLogger;

class QueueConsumer
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var Extensions
     */
    private $extensions;

    /**
     * @var MessageProcessorInterface[]
     */
    private $boundMessageProcessors;

    /**
     * @var int
     */
    private $idleMicroseconds;

    /**
     * @param ConnectionInterface $connection
     * @param Extensions $extensions
     * @param int $idleMicroseconds 100ms by default
     */
    public function __construct(ConnectionInterface $connection, Extensions $extensions, $idleMicroseconds = 100000)
    {
        $this->connection = $connection;
        $this->extensions = $extensions;
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
            throw new \LogicException('The queue name is empty. Cannot bind a processor to an empty queue');
        }
        if (array_key_exists($queueName, $this->boundMessageProcessors)) {
            throw new \LogicException(sprintf('The queue was already bound. Queue: %s', $queueName));
        }

        $this->boundMessageProcessors[$queueName] = $messageProcessor;

        return $this;
    }

    /**
     * @param Extensions|null $runtimeExtensions
     *
     * @throws \Exception
     */
    public function consume(Extensions $runtimeExtensions = null)
    {
        $session = $this->connection->createSession();

        /** @var MessageConsumerInterface[] $messageConsumers */
        $messageConsumers = [];
        foreach ($this->boundMessageProcessors as $queueName => $messageProcessor) {
            $queue = $session->createQueue($queueName);
            $messageConsumers[$queueName] = $session->createConsumer($queue);
        }

        $extensions = $this->extensions;
        if ($runtimeExtensions) {
            $extensions = new Extensions([$extensions, $runtimeExtensions]);
        }

        $context = new Context($session);
        $extensions->onStart($context);

        $logger = $context->getLogger() ?: new NullLogger();
        $logger->info('Start consuming');

        while (true) {
            try {
                foreach ($this->boundMessageProcessors as $queueName => $messageProcessor) {
                    $logger->debug(sprintf('Switch to a queue %s', $queueName));

                    $messageConsumer = $messageConsumers[$queueName];

                    $context = new Context($session);
                    $context->setLogger($logger);
                    $context->setQueueName($queueName);
                    $context->setMessageConsumer($messageConsumer);
                    $context->setMessageProcessor($messageProcessor);

                    $this->doConsume($session, $extensions, $context);
                }
            } catch (ConsumptionInterruptedException $e) {
                $logger->info(sprintf('Consuming interrupted'));

                $context->setExecutionInterrupted(true);

                $extensions->onInterrupted($context);
                $session->close();

                return;
            } catch (\Exception $e) {
                $logger->error(sprintf('Consuming interrupted by exception'));
                
                $context->setExecutionInterrupted(true);
                $context->setException($e);
                $extensions->onInterrupted($context);

                $session->close();

                throw $e;
            }
        }
    }

    /**
     * @param SessionInterface $session
     * @param Extensions $extensions
     * @param Context $context
     *
     * @throws ConsumptionInterruptedException
     *
     * @return bool
     */
    protected function doConsume(SessionInterface $session, Extensions $extensions, Context $context)
    {
        $messageProcessor = $context->getMessageProcessor();
        $messageConsumer = $context->getMessageConsumer();
        $logger = $context->getLogger();
        
        $extensions->onBeforeReceive($context);

        if ($context->isExecutionInterrupted()) {
            throw new ConsumptionInterruptedException();
        }
        
        if (false == $context->isExecutionInterrupted() && $message = $messageConsumer->receive($timeout = 1)) {
            $logger->info('Message received');
            $logger->debug(sprintf('Headers: %s', var_export($message->getHeaders(), true)));
            $logger->debug(sprintf('Properties: %s', var_export($message->getProperties(), true)));
            $logger->debug(sprintf('Payload: %s', var_export($message->getBody(), true)));

            $context->setMessage($message);

            $extensions->onPreReceived($context);
            if (false == $context->getStatus()) {
                $status = $messageProcessor->process($message, $session);
                $status = $status ?: MessageProcessorInterface::ACK;
                $context->setStatus($status);
            }

            if (MessageProcessorInterface::ACK === $context->getStatus()) {
                $messageConsumer->acknowledge($message);
            } elseif (MessageProcessorInterface::REJECT === $context->getStatus()) {
                $messageConsumer->reject($message, false);
            } elseif (MessageProcessorInterface::REQUEUE === $context->getStatus()) {
                $messageConsumer->reject($message, true);
            } else {
                throw new \LogicException(sprintf('Status is not supported: %s', $context->getStatus()));
            }

            $logger->info(sprintf('Message processed: %s', $context->getStatus()));

            $extensions->onPostReceived($context);
        } else {
            $logger->info(sprintf('Idle'));

            usleep($this->idleMicroseconds);
            $extensions->onIdle($context);
        }

        if ($context->isExecutionInterrupted()) {
            throw new ConsumptionInterruptedException();
        }
    }
}
