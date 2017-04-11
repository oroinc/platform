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

    /**
     * @param BeforeStepTested $scope
     */
    public function beforeStep(BeforeStepTested $scope)
    {
        $mqProcess = $this->messageQueueIsolator->getProcess();

        if ($mqProcess->getStatus() == Process::STATUS_TERMINATED) {
            $mqProcess->start();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMessageQueueIsolator(MessageQueueIsolatorInterface $messageQueueIsolator)
    {
        $this->messageQueueIsolator = $messageQueueIsolator;
    }
}
