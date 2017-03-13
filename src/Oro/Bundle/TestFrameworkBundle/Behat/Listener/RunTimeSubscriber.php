<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterOutlineTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Mink\Mink;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\MessageQueueIsolatorAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\MessageQueueIsolatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class RunTimeSubscriber implements EventSubscriberInterface, MessageQueueIsolatorAwareInterface
{
    /**
     * @var Mink
     */
    protected $mink;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var MessageQueueIsolatorInterface
     */
    protected $messageQueueIsolator;

    /**
     * @param Mink $mink
     */
    public function __construct(Mink $mink, KernelInterface $kernel)
    {
        $this->mink = $mink;
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            AfterScenarioTested::AFTER => ['afterScenario', 50],
            AfterOutlineTested::AFTER => ['afterOutline', 50],
        ];
    }

    /**
     * @param AfterScenarioTested $scope
     */
    public function afterScenario(AfterScenarioTested $scope)
    {
        if ($scope->getTestResult()->isPassed()) {
            return;
        }

        $screenshot = sprintf(
            '%s/%s-%s-line.png',
            $this->kernel->getLogDir(),
            $scope->getFeature()->getTitle(),
            $scope->getScenario()->getLine()
        );

        file_put_contents($screenshot, $this->mink->getSession()->getScreenshot());
    }

    /**
     * @param AfterOutlineTested $scope
     */
    public function afterOutline(AfterOutlineTested $scope)
    {
        if ($scope->getTestResult()->isPassed()) {
            return;
        }

        $screenshot = sprintf(
            '%s/%s-%s-%s-line.png',
            $this->kernel->getLogDir(),
            $scope->getFeature()->getTitle(),
            $scope->getOutline()->getTitle(),
            $scope->getOutline()->getLine()
        );

        file_put_contents($screenshot, $this->mink->getSession()->getScreenshot());
    }

    /**
     * {@inheritdoc}
     */
    public function setMessageQueueIsolator(MessageQueueIsolatorInterface $messageQueueIsolator)
    {
        $this->messageQueueIsolator = $messageQueueIsolator;
    }
}
