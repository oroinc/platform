<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Processor;

use Doctrine\Common\Cache\Cache;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Bridge\Doctrine\RegistryInterface;
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

    /**
     * @param KernelInterface $kernel
     * @param MessageQueueProcessorInterface $baseMessageQueueProcessor
     */
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

        $message = 'Message Queue was not process messages during time limit.';
        $message .= sprintf(
            ' Following messages was not consumed: %s',
            implode(', ', array_keys($this->getMessages('send_messages')))
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
        $container = $this->kernel->getContainer();

        /** @var RegistryInterface $doctrine */
        $doctrine = $container->get('doctrine');
        $connection = $doctrine->getConnection();

        // clear queue
        $this->getChannel()->exchange_delete(self::DEFAULT_EXCHANGE);
        $this->getChannel()->exchange_delete(self::DEFAULT_EXCHANGE_XDELAYED);
        $this->getChannel()->queue_delete(self::DEFAULT_QUEUE);

        $connection->executeQuery('DELETE FROM oro_message_queue_job');
        $connection->executeQuery('DELETE FROM oro_message_queue_job_unique');

        $this->closeConnections();

        if ($container->has('oro_message_queue.mock_lifecycle_message.cache')) {
            $cache = $container->get('oro_message_queue.mock_lifecycle_message.cache');
            $cache->delete('send_messages');
            $cache->delete('consume_messages');
        }
    }

    /**
     * @return bool
     */
    private function isQueueEmpty()
    {
        $messages = array_diff_key($this->getMessages('send_messages'), $this->getMessages('consume_messages'));

        return count($messages) < 1;
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

    /**
     * @param string $key
     *
     * @return array
     */
    private function getMessages($key)
    {
        $container = $this->kernel->getContainer();
        if (!$container->has('oro_message_queue.mock_lifecycle_message.cache')) {
            return [];
        }

        /** @var Cache $cache */
        $cache = $container->get('oro_message_queue.mock_lifecycle_message.cache');
        if (!$cache->contains($key)) {
            return [];
        }

        $messages = unserialize($cache->fetch($key));
        if (!is_array($messages)) {
            return [];
        }

        return $messages;
    }
}
