<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Available command placeholders:
 * - %database_host%
 * - %database_port%
 * - %database_name%
 * - %database_user%
 * - %database_password%
 * - %database_dump%
 */
class UnixMysqlIsolator extends AbstractDbOsRelatedIsolator
{
    /** @var string full path to DB dump file */
    protected $dbDump;

    /** {@inheritdoc} */
    public function getName()
    {
        return "MySQL DB";
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return $this->isApplicableOS()
            && DatabaseDriverInterface::DRIVER_MYSQL === $container->getParameter('database_driver')
            && false == getenv('BEHAT_DATABASE_LOCATION');
    }

    /** {@inheritdoc} */
    protected function getApplicableOs()
    {
        return [
            AbstractOsRelatedIsolator::LINUX_OS,
            AbstractOsRelatedIsolator::MAC_OS,
        ];
    }

    protected function getDropDbCommand(): string
    {
        return 'MYSQL_PWD="%database_password%" mysql -e "drop database %database_name%;" -h %database_host% '.
            '-P %database_port% -u %database_user%';
    }

    protected function getCreateDbCommand(): string
    {
        return 'MYSQL_PWD="%database_password%" mysql -e "create database %database_name%;" -h %database_host% '.
            '-P %database_port% -u %database_user%';
    }

    protected function getDumpDbCommand(): string
    {
        return 'MYSQL_PWD="%database_password%" mysqldump -h %database_host% -P %database_port% -u %database_user% '.
            '%database_name% > %database_dump%';
    }

    protected function getRestoreDbCommand(): string
    {
        return 'MYSQL_PWD="%database_password%" mysql -h %database_host% -P %database_port% -u %database_user% '.
            '%database_name% < %database_dump%';
    }

    protected function getDbExistsCommand(): string
    {
        return 'MYSQL_PWD="%database_password%" mysql -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA '.
            'WHERE SCHEMA_NAME = \'%database_name%\'" -h %database_host% -P %database_port% -u %database_user%';
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
        if (false === is_file($this->dbDump)) {
            return;
        }
        $event->writeln('<info>Restore Db from dump</info>');
        $this->restore();
        $event->writeln('<info>Remove Db dump</info>');
        unlink($this->dbDump);
    }

    /** {@inheritdoc} */
    public function restoreState(RestoreStateEvent $event)
    {
        if (false === is_file($this->dbDump)) {
            return;
        }
        parent::restoreState($event);
        $event->writeln('<info>Remove Db dump</info>');
        unlink($this->dbDump);
    }

    /** {@inheritdoc} */
    public function isOutdatedState()
    {
        return is_file($this->dbDump);
    }

    /** {@inheritdoc} */
    protected function dump(): void
    {
        $this->dbDump = sys_get_temp_dir().DIRECTORY_SEPARATOR.$this->dbName.TokenGenerator::generateToken('db');

        $this->runProcess(
            strtr(
                $this->getDumpDbCommand(),
                [
                    '%database_host%' => $this->dbHost,
                    '%database_port%' => $this->dbPort,
                    '%database_name%' => $this->dbName,
                    '%database_user%' => $this->dbUser,
                    '%database_password%' => $this->dbPass,
                    '%database_dump%' => $this->dbDump,
                ]
            )
        );
    }

    /** {@inheritdoc} */
    protected function restore(): void
    {
        $this->createDb();

        $this->runProcess(
            strtr(
                $this->getRestoreDbCommand(),
                [
                    '%database_host%' => $this->dbHost,
                    '%database_port%' => $this->dbPort,
                    '%database_name%' => $this->dbName,
                    '%database_user%' => $this->dbUser,
                    '%database_password%' => $this->dbPass,
                    '%database_dump%' => $this->dbDump,
                ]
            ),
            240
        );
    }

    protected function createDb()
    {
        if ($this->isDbExists($this->dbName)) {
            $this->dropDb($this->dbName);
        }

        $this->runProcess(
            strtr(
                $this->getCreateDbCommand(),
                [
                    '%database_host%' => $this->dbHost,
                    '%database_port%' => $this->dbPort,
                    '%database_name%' => $this->dbName,
                    '%database_user%' => $this->dbUser,
                    '%database_password%' => $this->dbPass,
                ]
            )
        );
    }

    protected function dropDb(string $dbName): void
    {
        if (!$this->isDbExists($dbName)) {
            return;
        }
        parent::dropDb($dbName);
    }

    /**
     * @param string $dbName
     * @return bool
     */
    private function isDbExists($dbName)
    {
        $process = $this->runProcess(
            strtr(
                $this->getDbExistsCommand(),
                [
                    '%database_host%' => $this->dbHost,
                    '%database_port%' => $this->dbPort,
                    '%database_name%' => $dbName,
                    '%database_user%' => $this->dbUser,
                    '%database_password%' => $this->dbPass,
                ]
            )
        );

        return false !== strpos($process->getOutput(), $dbName);
    }
}
