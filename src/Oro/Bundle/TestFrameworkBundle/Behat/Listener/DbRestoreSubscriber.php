<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Oro\Bundle\TestFrameworkBundle\Behat\Dumper\DbDumperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DbRestoreSubscriber implements EventSubscriberInterface
{
    const ANNOTATION = 'dbIsolation';

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
            FeatureTested::AFTER  => ['dbRestore', 5],
        ];
    }

    public function dbDump(BeforeFeatureTested $event)
    {
        if (!in_array(self::ANNOTATION, $event->getFeature()->getTags())) {
            return;
        }

        $this->dbDumper->dumpDb();
    }

    public function dbRestore(AfterFeatureTested $event)
    {
        if (!in_array(self::ANNOTATION, $event->getFeature()->getTags())) {
            return;
        }

        $this->dbDumper->restoreDb();
    }
}
