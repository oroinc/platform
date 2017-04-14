<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Mink\Mink;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\MessageQueueIsolatorAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\MessageQueueIsolatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class MessageQueueRunCheckSubscriber implements EventSubscriberInterface, MessageQueueIsolatorAwareInterface
{
    /**
     * @var MessageQueueIsolatorInterface
     */
    protected $messageQueueIsolator;

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
        $mqProcess = $this->messageQueueIsolator->getProcess();

        if (null === $mqProcess) {
            return;
        }

        if ($mqProcess->getStatus() !== Process::STATUS_TERMINATED) {
            return;
        }

        $mqProcess->start(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'MessageQueueConsumer ERR > '.$buffer;
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function setMessageQueueIsolator(MessageQueueIsolatorInterface $messageQueueIsolator)
    {
        $this->messageQueueIsolator = $messageQueueIsolator;
    }
}
