<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Processor\MessageQueueProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Process all messages in queue before make db dump
 */
class InitalMessageQueueIsolator
{
    /** @var KernelInterface */
    private $kernel;

    /** @var MessageQueueProcessorInterface */
    private $dbalMessageQueueProcessor;

    /** @var MessageQueueProcessorInterface */
    private $amqpMessageQueueProcessor;

    /**
     * @param KernelInterface $kernel
     * @param MessageQueueProcessorInterface $dbalMessageQueueProcessor
     * @param MessageQueueProcessorInterface $amqpMessageQueueProcessor
     */
    public function __construct(
        KernelInterface $kernel,
        MessageQueueProcessorInterface $dbalMessageQueueProcessor,
        MessageQueueProcessorInterface $amqpMessageQueueProcessor
    ) {
        $this->kernel = $kernel;
        $this->dbalMessageQueueProcessor = $dbalMessageQueueProcessor;
        $this->amqpMessageQueueProcessor = $amqpMessageQueueProcessor;
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Process messages before make db dump</info>');

        $this->kernel->boot();
        $this->getMessageQueueProcessor()->startMessageQueue();
        $this->getMessageQueueProcessor()->waitWhileProcessingMessages();
        $this->getMessageQueueProcessor()->stopMessageQueue();
        $this->kernel->shutdown();
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return true;
    }

    /** {@inheritdoc} */
    public function restoreState(RestoreStateEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function isOutdatedState()
    {
        return false;
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return sprintf('Initial Message Queue Isolator');
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'inital_message_queue';
    }

    /**
     * @return MessageQueueProcessorInterface
     */
    private function getMessageQueueProcessor()
    {
        $container = $this->kernel->getContainer();
        if ($container->getParameter('message_queue_transport') === 'amqp') {
            return $this->amqpMessageQueueProcessor;
        }

        return $this->dbalMessageQueueProcessor;
    }
}
