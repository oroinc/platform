<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\MessageQueueIsolatorAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\MessageQueueIsolatorInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\SkipIsolatorsTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class MessageQueueRunCheckSubscriber implements EventSubscriberInterface, MessageQueueIsolatorAwareInterface
{
    use SkipIsolatorsTrait;

    /**
     * @var MessageQueueIsolatorInterface
     */
    protected $messageQueueIsolator;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeStepTested::BEFORE => ['beforeStep', 150],
        ];
    }

    public function beforeStep()
    {
        if ($this->skip) {
            return;
        }

        if (in_array($this->messageQueueIsolator->getTag(), $this->skipIsolators, true)) {
            return;
        }

        $mqProcess = $this->messageQueueIsolator->getProcess();

        if (!$mqProcess || $mqProcess->isRunning()) {
            return;
        }

        if ($mqProcess->getStatus() !== Process::STATUS_TERMINATED) {
            return;
        }

        $this->messageQueueIsolator->beforeTest(new BeforeIsolatedTestEvent());
    }

    /**
     * {@inheritdoc}
     */
    public function setMessageQueueIsolator(MessageQueueIsolatorInterface $messageQueueIsolator)
    {
        $this->messageQueueIsolator = $messageQueueIsolator;
    }

    /**
     * {@inheritdoc}
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }
}
