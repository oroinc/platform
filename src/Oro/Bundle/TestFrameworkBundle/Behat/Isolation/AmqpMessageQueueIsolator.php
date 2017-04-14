<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpQueue;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class AmqpMessageQueueIsolator implements IsolatorInterface, MessageQueueIsolatorInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var Process
     */
    private $process;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
        $command = sprintf(
            './console oro:message-queue:consume --env=%s %s -vvv > /tmp/rabbit.log',
            $this->kernel->getEnvironment(),
            $this->kernel->isDebug() ? '' : '--no-debug'
        );
        $this->process = new Process($command, $this->kernel->getRootDir());
        $this->process->start();
        self::waitWhileProcessingMessages();
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        self::waitWhileProcessingMessages();

        $this->process = new Process('pkill -f oro:message-queue:consume', $this->kernel->getRootDir());

        try {
            $this->process->run();
        } catch (RuntimeException $e) {
            //it's ok
        }
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return 'amqp' === $container->getParameter('message_queue_transport');
    }

    /**
     * {@inheritdoc}
     */
    public function isOutdatedState()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function restoreState(RestoreStateEvent $event)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'AMQP Message Queue';
    }

    /**
     * @param int $timeLimit
     */
    public function waitWhileProcessingMessages($timeLimit = 60)
    {
        $time = $timeLimit;

        $queue = $this->createAmqpQueue();
        $channel = $this->createAmqpStreamConnection()->channel();
        $messagesNumber = $this->getQueueMessageNumber($channel, $queue);

        while (0 !== $messagesNumber) {
            if ($time <= 0) {
                throw new RuntimeException('Message Queue was not process messages during time limit');
            }

            $messagesNumber = $this->getQueueMessageNumber($channel, $queue);

            usleep(250000);
            $time -= 0.25;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'message-queue';
    }

    /**
     * @return AMQPStreamConnection
     */
    private function createAmqpStreamConnection()
    {
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
     * @param AmqpQueue $queue
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

    /**
     * @return Process
     */
    public function getProcess()
    {
        return $this->process;
    }
}
