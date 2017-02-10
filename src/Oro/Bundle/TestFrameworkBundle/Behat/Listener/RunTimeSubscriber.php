<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterOutlineTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Mink\Mink;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;
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
//            BeforeStepTested::BEFORE  => ['beforeStep', 50],
            AfterScenarioTested::AFTER => ['afterScenario', 50],
            AfterOutlineTested::AFTER => ['afterOutline', 50],
        ];
    }

    /**
     * @param BeforeStepTested $scope
     */
    public function beforeStep(BeforeStepTested $scope)
    {
        $this->waitForAjaxBeforeStep($scope);
        $this->messageQueueIsolator->waitWhileProcessingMessages();
    }

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

    /**
     * @param BeforeStepTested $scope
     */
    protected function waitForAjaxBeforeStep(BeforeStepTested $scope)
    {
        if (false === $this->mink->isSessionStarted('first_session')) {
            return;
        }

        $session = $this->mink->getSession('first_session');
        /** @var OroSelenium2Driver $driver */
        $driver = $session->getDriver();

        $url = $session->getCurrentUrl();

        if (1 === preg_match('/^[\S]*\/user\/login\/?$/i', $url)) {
            $driver->waitPageToLoad();

            return;
        } elseif (0 === preg_match('/^https?:\/\//', $url)) {
            return;
        }

        // Don't wait when we need assert the flash message, because it can disappear until ajax in process
        if (preg_match('/^(?:|I )should see ".+"(?:| flash message| error message)$/', $scope->getStep()->getText())) {
            return;
        }

        $driver->waitForAjax();
    }
}
