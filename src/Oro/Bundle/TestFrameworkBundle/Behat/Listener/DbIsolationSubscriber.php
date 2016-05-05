<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioLikeTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Mink\Mink;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class DbIsolationSubscriber implements EventSubscriberInterface
{
    const ANNOTATION = 'dbIsolation';

    /** @var string */
    protected $dbName;

    /** @var string */
    protected $dbPass;

    /** @var string */
    protected $dbUser;

    /** @var string */
    protected $cacheDir;

    /** @var Process */
    protected $dumpDbProcess;

    /**
     * DbIsolationSubscriber constructor.
     */
    public function __construct(KernelInterface $kernel)
    {
        $kernel->boot();
        $container = $kernel->getContainer();

        $this->cacheDir = $kernel->getCacheDir();
        $this->dbName = $container->getParameter('database_name');
        $this->dbUser = $container->getParameter('database_user');
        $this->dbPass = $container->getParameter('database_password');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FeatureTested::BEFORE => ['dbDump', 5],
            FeatureTested::AFTER  => ['dbRestore', 5],
        ];
    }

    public function dbDump(BeforeFeatureTested $event)
    {
        if (!in_array(self::ANNOTATION, $event->getFeature()->getTags())) {
            return;
        }

        $this->dumpDbProcess = new Process(sprintf(
            'mysqldump -u %s -p%s %s > %s/%s.sql',
            $this->dbUser,
            $this->dbPass,
            $this->dbName,
            $this->cacheDir,
            $this->dbName
        ));
        $this->dumpDbProcess->setTimeout(30);
        $this->dumpDbProcess->start();
    }

    public function dbRestore(AfterFeatureTested $event)
    {
        if (!in_array(self::ANNOTATION, $event->getFeature()->getTags())) {
            return;
        }

        while ($this->dumpDbProcess->isRunning()) {
        // waiting for process to finish
        }

        $process = new Process(sprintf(
            "mysql -e 'drop database %s;' -u %s -p%s".
            " && mysql -e 'create database %s;' -u %s -p%s".
            " && mysql -u %s -p%s %s < %s.sql",
            $this->dbName,
            $this->dbUser,
            $this->dbPass,
            $this->dbName,
            $this->dbUser,
            $this->dbPass,
            $this->dbUser,
            $this->dbPass,
            $this->dbName,
            $this->dbName
        ));
        $process->run();
    }
}
