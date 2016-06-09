<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Testwork\EventDispatcher\Event\BeforeExerciseCompleted;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\TestFrameworkBundle\Behat\Dumper\DbDumperInterface;

class DbDumpSubscriber implements EventSubscriberInterface
{
    /** @var  DbDumperInterface  */
    protected $dbDumper;

    /**
     * @param DbDumperInterface $dbDumper
     */
    public function __construct(DbDumperInterface $dbDumper)
    {
        $this->dbDumper = $dbDumper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeExerciseCompleted::BEFORE => ['dbDump', 5]
        ];
    }

    /**
     * @param BeforeExerciseCompleted $event
     */
    public function dbDump(BeforeExerciseCompleted $event)
    {
        $this->dbDumper->dumpDb();
    }
}
