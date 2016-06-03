<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Testwork\EventDispatcher\Event\BeforeExerciseCompleted;
use Oro\Bundle\TestFrameworkBundle\Behat\Dumper\CacheDumperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Dumper\DbDumperInterface;

class DumpEnvironmentSubscriber implements EventSubscriberInterface
{
    /** @var  DbDumperInterface  */
    protected $dbDumper;

    /** @var CacheDumperInterface */
    protected $cacheDumper;

    /**
     * @param DbDumperInterface $dbDumper
     */
    public function __construct(DbDumperInterface $dbDumper, CacheDumperInterface $cacheDumper)
    {
        $this->dbDumper = $dbDumper;
        $this->cacheDumper = $cacheDumper;
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
        $this->dbDumper->dump();
        $this->cacheDumper->dump();
    }
}
