<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Processor\MessageQueueProcessorAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Processor\MessageQueueProcessorAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Process all messages in queue before make db dump
 */
class InitalMessageQueueIsolator implements MessageQueueProcessorAwareInterface
{
    use MessageQueueProcessorAwareTrait;

    /** @var KernelInterface */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Process messages before make db dump</info>');

        $this->kernel->boot();

        if ($this->kernel->getContainer()->has('oro_message_queue.mock_lifecycle_message.cache')) {
            $cache = $this->kernel->getContainer()->get('oro_message_queue.mock_lifecycle_message.cache');
            $cache->clear();
        }

        $this->messageQueueProcessor->startMessageQueue();
        $this->messageQueueProcessor->waitWhileProcessingMessages();
        $this->messageQueueProcessor->stopMessageQueue();
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
}
