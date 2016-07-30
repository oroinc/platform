<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\MinkExtension\Listener\SessionsListener as MinkSessionsListener;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;

class SessionsListener extends MinkSessionsListener
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FeatureTested::BEFORE   => array('prepareDefaultMinkSession', 10),
            ExerciseCompleted::AFTER => array('tearDownMinkSessions', -10)
        );
    }

    public function prepareDefaultMinkSession(BeforeFeatureTested $event)
    {
        $newEvent = new BeforeScenarioTested(
            $event->getEnvironment(),
            $event->getFeature(),
            $event->getFeature()->getScenarios()[0]
        );

        parent::prepareDefaultMinkSession($newEvent);
    }
}
