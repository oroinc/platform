<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Mink\Mink;
use Behat\MinkExtension\Listener\SessionsListener as MinkSessionsListener;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;

/**
 * Manages Mink browser sessions across Behat features and scenarios.
 *
 * This listener extends the Mink sessions listener to handle session lifecycle events,
 * including stopping sessions between features and cleaning up resources. It supports
 * multiple JavaScript-capable sessions for parallel or multi-browser testing.
 */
class SessionsListener extends MinkSessionsListener
{
    protected $mink;
    protected $defaultSession;
    protected $javascriptSession;

    /**
     * @var string[] The available javascript sessions
     */
    protected $availableJavascriptSessions;

    /**
     * Initializes initializer.
     *
     * @param Mink        $mink
     * @param string      $defaultSession
     * @param string|null $javascriptSession
     * @param string[]    $availableJavascriptSessions
     */
    public function __construct(
        Mink $mink,
        $defaultSession,
        $javascriptSession,
        array $availableJavascriptSessions = array()
    ) {
        $this->mink              = $mink;
        $this->defaultSession    = $defaultSession;
        $this->javascriptSession = $javascriptSession;
        $this->availableJavascriptSessions = $availableJavascriptSessions;

        parent::__construct($mink, $defaultSession, $javascriptSession, $availableJavascriptSessions);
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return array(
            FeatureTested::BEFORE   => array('prepareDefaultMinkFeatureSession', 10),
            ExerciseCompleted::AFTER => array('tearDownMinkSessions', -10)
        );
    }

    public function prepareDefaultMinkFeatureSession(BeforeFeatureTested $event)
    {
        $this->mink->stopSessions();
        gc_collect_cycles();

        if (0 === count($event->getFeature()->getScenarios())) {
            return;
        }

        $newEvent = new BeforeScenarioTested(
            $event->getEnvironment(),
            $event->getFeature(),
            $event->getFeature()->getScenarios()[0]
        );

        parent::prepareDefaultMinkSession($newEvent);
    }
}
