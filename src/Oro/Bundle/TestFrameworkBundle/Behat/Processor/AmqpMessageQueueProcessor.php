<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Processor;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Message queue processor for AMQP transport
 */
class AmqpMessageQueueProcessor implements MessageQueueProcessorInterface
{
    const DEFAULT_QUEUE = 'oro.default';
    const DEFAULT_EXCHANGE = 'oro.default';
    const DEFAULT_EXCHANGE_XDELAYED = 'oro.default.delayed';

    /** @var KernelInterface */
    private $kernel;

    /** @var MessageQueueProcessorInterface */
    private $baseMessageQueueProcessor;

    /** @var AMQPChannel */
    private $channel;

    public function __construct(KernelInterface $kernel, MessageQueueProcessorInterface $baseMessageQueueProcessor)
    {
        $this->kernel = $kernel;
        $this->baseMessageQueueProcessor = $baseMessageQueueProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function startMessageQueue()
    {
        $container = $this->kernel->getContainer();
        $driver = $container->get('oro_message_queue.client.driver');
        $driver->createQueue(self::DEFAULT_QUEUE);

        $this->baseMessageQueueProcessor->startMessageQueue();
    }

    /**
     * {@inheritdoc}
     */
    public function stopMessageQueue()
    {
        $this->baseMessageQueueProcessor->stopMessageQueue();
    }

    /**
     * {@inheritdoc}
     */
    public function waitWhileProcessingMessages($timeLimit = self::TIMEOUT)
    {
        $endTime = new \DateTime(sprintf('+%d seconds', $timeLimit));
        while (true) {
            $this->baseMessageQueueProcessor->waitWhileProcessingMessages();

            if ($this->isQueueEmpty()) {
                return;
            }

            usleep(100000);

            $now = new \DateTime();
            if ($now >= $endTime) {
                break;
            }
        }

        $message = sprintf(
            'The message queue has not been able to finish processing messages within the last %d seconds.',
            $timeLimit
        );
        $message .= sprintf(
            ' The following messages have not been yet consumed: %s',
            implode(', ', $this->getMessages())
        );

        throw new \RuntimeException($message);
    }

    /**
     * {@inheritdoc}
     */
    public function isRunning()
    {
        return $this->baseMessageQueueProcessor->isRunning();
    }

    /**
     * {@inheritdoc}
     */
    public function cleanUp()
    {
        $this->baseMessageQueueProcessor->cleanUp();

        $container = $this->kernel->getContainer();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $container->get('doctrine');
        $connection = $doctrine->getConnection('message_queue');

        // clear queue
        $this->getChannel()->exchange_delete(self::DEFAULT_EXCHANGE);
        $this->getChannel()->exchange_delete(self::DEFAULT_EXCHANGE_XDELAYED);
        $this->getChannel()->queue_delete(self::DEFAULT_QUEUE);

        $connection->executeQuery('DELETE FROM oro_message_queue_job');
        $connection->executeQuery('DELETE FROM oro_message_queue_job_unique');

        $this->closeConnections();

        $cache = $container->get('oro_message_queue.mock_lifecycle_message.cache');
        $cache->clear();
    }

    private function isQueueEmpty(): bool
    {
        return empty($this->getMessages());
    }

    /**
     * @return AMQPChannel
     */
    private function getChannel()
    {
        if (!$this->channel) {
            $container = $this->kernel->getContainer();

            $config = $container->getParameter('message_queue_transport_config');
            $amqpStreamConnection = new AMQPStreamConnection(
                $config['host'],
                $config['port'],
                $config['user'],
                $config['password'],
                $config['vhost']
            );

            $this->channel = $amqpStreamConnection->channel();
        }

        return $this->channel;
    }

    private function closeConnections()
    {
        if ($this->channel) {
            $this->channel->close();
            $this->channel = null;
        }
    }

    private function getMessages(): array
    {
        $container = $this->kernel->getContainer();

        /** @var Connection $connection */
        $connection = $container->get('doctrine')->getConnection('message_queue');

        // guard
        $cache = $container->get('oro_message_queue.mock_lifecycle_message.cache');
        $cache->getItem('oro_behat_message_queue');

        $messageIds = array_column($connection->fetchAll('SELECT item_id FROM oro_behat_message_queue'), 'item_id');

        $messages = [];
        foreach ($messageIds as $messageId) {
            $messages[] = $cache->getItem($messageId)->get();
        }

        return $messages;
    }
}
