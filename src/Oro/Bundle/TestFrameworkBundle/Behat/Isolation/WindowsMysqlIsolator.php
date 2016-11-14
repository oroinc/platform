<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WindowsMysqlIsolator extends OsRelatedIsolator implements IsolatorInterface
{
    use AbstractDbIsolator;

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
        $this->runProcess(sprintf(
            'SET MYSQL_PWD=%s& mysqldump -h %s -u %s %s > %s',
            $this->dbPass,
            $this->dbHost,
            $this->dbUser,
            $this->dbName,
            $this->dbDump
        ));
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {}

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $this->restoreDbFromDump();
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
        $event->writeln('<info>Remove Db dump</info>');
        unlink($this->dbDump);

    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return
            self::isApplicableOS()
            && 'pdo_mysql' === $container->getParameter('database_driver');
    }

    /**
     * {@inheritdoc}
     */
    public function isOutdatedState()
    {
        return is_file($this->dbDump);
    }

    /**
     * {@inheritdoc}
     */
    public function restoreState(RestoreStateEvent $event)
    {
        if (false === is_file($this->dbDump)) {
            throw new RuntimeException('Can\'t restore MysqlDb without sql dump');
        }
        $event->writeln('<info>Begin to restore the state of MysqlDb...</info>');
        $event->writeln('<info>Restore Db from dump</info>');

        $this->restoreDbFromDump();

        $event->writeln('<info>Remove Db dump</info>');

        unlink($this->dbDump);

        $event->writeln('<info>MysqlDb was restored from dump</info>');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'MySql Db';
    }

    /** {@inheritdoc} */
    protected function getApplicableOs()
    {
        return [
            OsRelatedIsolator::WINDOWS_OS,
        ];
    }


    private function restoreDbFromDump()
    {
        if (false === is_file($this->dbDump)) {
            throw new RuntimeException('You can restore DB state without dump');
        }

        $this->runProcess(sprintf(
            'SET MYSQL_PWD=%s&mysql -e "drop database %s;" -h %s -u %s'.
            ' && SET MYSQL_PWD=%1$s& mysql -e "create database %2$s;" -h %3$s -u %4$s'.
            ' && SET MYSQL_PWD=%1$s& mysql -h %3$s -u %4$s %2$s < %6$s',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser,
            $this->cacheDir,
            $this->dbDump
        ));
    }
}
