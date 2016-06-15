<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Testwork\EventDispatcher\Event\BeforeExerciseCompleted;
use Oro\Bundle\TestFrameworkBundle\Behat\Dumper\DumperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DumpEnvironmentSubscriber implements EventSubscriberInterface
{
    /** @var DumperInterface[] */
    protected $dumpers;

    /**
     * @param DumperInterface[] $dumpers
     */
    public function __construct(array $dumpers)
    {
        $this->dumpers = $dumpers;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeExerciseCompleted::BEFORE => ['dump', 5]
        ];
    }

    /**
     * @param BeforeExerciseCompleted $event
     */
    public function dump(BeforeExerciseCompleted $event)
    {
        foreach ($this->dumpers as $dumper) {
            $dumper->dump();
        }
    }
}
