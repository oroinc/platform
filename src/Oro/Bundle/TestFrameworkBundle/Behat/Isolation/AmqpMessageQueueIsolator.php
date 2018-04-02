<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpQueue;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Exception\RuntimeException;

class AmqpMessageQueueIsolator extends AbstractMessageQueueIsolator
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContainerInterface $container)
    {
        return 'amqp' === $container->getParameter('message_queue_transport');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'AMQP Message Queue';
    }

    /**
     * {@inheritdoc}
     */
    public function waitWhileProcessingMessages($timeLimit = self::TIMEOUT)
    {
        $queue = $this->createAmqpQueue();
        $channel = $this->createAmqpStreamConnection()->channel();
        $messagesNumber = $this->getQueueMessageNumber($channel, $queue);
        // @todo: add wip messages?
        while (0 !== $messagesNumber) {
            $isRunning = $this->ensureMessageQueueIsRunning();
            if (!$isRunning) {
                throw new RuntimeException('Message Queue is not running');
            }

            if ($timeLimit <= 0) {
                throw new RuntimeException('Message Queue was not process messages during time limit');
            }

            $messagesNumber = $this->getQueueMessageNumber($channel, $queue);
            sleep(1);
            $timeLimit -= 1;
        }
    }

    protected function cleanUp()
    {
        // @todo: purge queue
    }

    /**
     * @return int
     */
    public function getMessageNumber()
    {
        $queue = $this->createAmqpQueue();
        $channel = $this->createAmqpStreamConnection()->channel();
        $messagesNumber = $this->getQueueMessageNumber($channel, $queue);

        return $messagesNumber;
    }

    /**
     * @return AMQPStreamConnection
     */
    private function createAmqpStreamConnection()
    {
        $this->kernel->boot();
        $appContainer = $this->kernel->getContainer();
        $messageQueueParameters = $appContainer->getParameter('message_queue_transport_config');

        return new AMQPStreamConnection(
            $messageQueueParameters['host'],
            $messageQueueParameters['port'],
            $messageQueueParameters['user'],
            $messageQueueParameters['password'],
            $messageQueueParameters['vhost']
        );
    }

    /**
     * @return AmqpQueue
     */
    private function createAmqpQueue()
    {
        $queue = new AmqpQueue('oro.default');
        $queue->setDurable(true);
        $queue->setAutoDelete(false);
        $queue->setTable(['x-max-priority' => 4]);

        return $queue;
    }

    /**
     * @param AMQPChannel $channel
     * @param AmqpQueue   $queue
     *
     * @return int
     */
    private function getQueueMessageNumber(AMQPChannel $channel, AmqpQueue $queue)
    {
        $result = $channel->queue_declare(
            $queue->getQueueName(),
            $queue->isPassive(),
            $queue->isDurable(),
            $queue->isExclusive(),
            $queue->isAutoDelete(),
            $queue->isNoWait(),
            $queue->getTable() ? new AMQPTable($queue->getTable()) : null
        );

        return $result[1];
    }
}
