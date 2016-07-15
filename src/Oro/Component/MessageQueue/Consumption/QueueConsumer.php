<?php
namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Consumption\Exception\ConsumptionInterruptedException;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Util\VarExport;

use Psr\Log\NullLogger;

class QueueConsumer
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var ChainExtension
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
     * @param ChainExtension $extensions
     * @param int $idleMicroseconds 100ms by default
     */
    public function __construct(ConnectionInterface $connection, ChainExtension $extensions, $idleMicroseconds = 100000)
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
     * @param ExtensionInterface|ChainExtension|null $runtimeExtension
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

        $extensions = $this->extensions;
        if ($runtimeExtension) {
            $extensions = new ChainExtension([$extensions, $runtimeExtension]);
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

                    $this->doConsume($extensions, $context);
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
     * @param ChainExtension $extensions
     * @param Context $context
     *
     * @throws ConsumptionInterruptedException
     *
     * @return bool
     */
    protected function doConsume(ChainExtension $extensions, Context $context)
    {
        $session = $context->getSession();
        $messageProcessor = $context->getMessageProcessor();
        $messageConsumer = $context->getMessageConsumer();
        $logger = $context->getLogger();
        
        $extensions->onBeforeReceive($context);

        if ($context->isExecutionInterrupted()) {
            throw new ConsumptionInterruptedException();
        }
        
        if (false == $context->isExecutionInterrupted() && $message = $messageConsumer->receive($timeout = 1)) {
            $logger->info('Message received');
            $logger->debug('Headers: {headers}', ['headers' => new VarExport($message->getHeaders())]);
            $logger->debug('Properties: {properties}', ['properties' => new VarExport($message->getProperties())]);
            $logger->debug('Payload: {payload}', ['payload' => new VarExport($message->getBody())]);

            $context->setMessage($message);

            $extensions->onPreReceived($context);
            if (!$context->getStatus()) {
                $status = $messageProcessor->process($message, $session);
                $context->setStatus($status);
            }

            switch ($context->getStatus()) {
                case MessageProcessorInterface::ACK:
                    $messageConsumer->acknowledge($message);
                    break;
                case MessageProcessorInterface::REJECT:
                    $messageConsumer->reject($message, false);
                    break;
                case MessageProcessorInterface::REQUEUE:
                    $messageConsumer->reject($message, true);
                    break;
                default:
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
