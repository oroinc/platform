<?php
namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Transport\ConnectionInterface;
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
     * @var int
     */
    private $idleTime;

    /**
     * @param ConnectionInterface $connection
     * @param Extensions $extensions
     * @param int $idleTime
     */
    public function __construct(ConnectionInterface $connection, Extensions $extensions, $idleTime = 1)
    {
        $this->connection = $connection;
        $this->extensions = $extensions;
        $this->idleTime = $idleTime;
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
     * @param Extensions $extensions
     *
     * @throws \Exception
     */
    public function consume($queueName, MessageProcessorInterface $messageProcessor, Extensions $extensions = null)
    {
        $session = $this->connection->createSession();
        $queue = $session->createQueue($queueName);
        $messageConsumer = $session->createConsumer($queue);

        if ($extensions) {
            $extensions = new Extensions([$this->extensions, $extensions]);
        } else {
            $extensions = $this->extensions;
        }

        $context = new Context($session, $messageConsumer, $messageProcessor, new NullLogger());
        $extensions->onStart($context);
        $logger = $context->getLogger();

        while (true) {
            $context = new Context($session, $messageConsumer, $messageProcessor, $logger);

            try {
                $extensions->onBeforeReceive($context);

                if ($message = $messageConsumer->receive($timeout = 1)) {
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

                    $extensions->onPostReceived($context);
                } else {
                    sleep($this->idleTime);
                    $extensions->onIdle($context);
                }

                if ($context->isExecutionInterrupted()) {
                    $extensions->onInterrupted($context);
                    $session->close();

                    return;
                }
            } catch (\Exception $e) {
                $context->setExecutionInterrupted(true);
                $context->setException($e);
                $extensions->onInterrupted($context);

                $session->close();

                throw $e;
            }
        }
    }
}
