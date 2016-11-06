<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Behat\Testwork\EventDispatcher\Event\BeforeExerciseCompleted;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class TestIsolationSubscriber implements EventSubscriberInterface
{
    /** @var IsolatorInterface[] */
    protected $isolators;

    /** @var IsolatorInterface[] */
    protected $reverseIsolators;

    /**
     * @param IsolatorInterface[] $isolators
     */
    public function __construct(array $isolators, KernelInterface $kernel)
    {
        $kernel->boot();
        $container = $kernel->getContainer();
        $applicableIsolators = [];

        foreach ($isolators as $isolator) {
            if ($isolator->isApplicable($container)) {
                $applicableIsolators[] = $isolator;
            }
        }

        $this->isolators = $applicableIsolators;
        $this->reverseIsolators = array_reverse($applicableIsolators);

        $kernel->shutdown();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeExerciseCompleted::BEFORE => ['beforeExercise', 100],
            BeforeFeatureTested::BEFORE     => ['beforeFeature', 100],
            BeforeScenarioTested::BEFORE    => ['beforeScenario', 100],
            AfterScenarioTested::AFTER      => ['afterScenario', -100],
            AfterFeatureTested::AFTER       => ['afterFeature', -100],
            AfterExerciseCompleted::AFTER   => ['afterExercise', -100]
        ];
    }

    public function beforeExercise()
    {
        foreach ($this->isolators as $isolator) {
            $isolator->start();
        }
    }

    public function beforeFeature()
    {
        foreach ($this->isolators as $isolator) {
            $isolator->beforeTest();
        }
    }

    public function beforeScenario()
    {}

    public function afterScenario()
    {}

    public function afterFeature()
    {
        foreach ($this->reverseIsolators as $isolator) {
            $isolator->afterTest();
        }
    }

    public function afterExercise()
    {
        foreach ($this->reverseIsolators as $isolator) {
            $isolator->terminate();
        }
    }
}