<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Restore and backup PostgreSQL database between features
 *
 * Available command placeholders:
 * - %database_host%
 * - %database_port%
 * - %database_name%
 * - %database_user%
 * - %database_password%
 * - %database_template%
 */
class UnixPgsqlIsolator extends AbstractDbOsRelatedIsolator
{
    protected string $dbTemp = '';

    /** {@inheritdoc} */
    public function getName()
    {
        return 'PostgreSQL DB';
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return self::isApplicableOS() && false == getenv('BEHAT_DATABASE_LOCATION');
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
        return 'PGPASSWORD="%database_password%" dropdb --host=%database_host% --port=%database_port% '.
            '--username="%database_user%" %database_name%';
    }

    protected function getCreateDbCommand(): string
    {
        return 'PGPASSWORD="%database_password%" createdb --host=%database_host% --port=%database_port% '.
            '--username=%database_user% --owner=%database_user% --template=%database_template% %database_name%';
    }

    protected function getKillDBConnectionsCommand(): string
    {
        return 'PGPASSWORD="%database_password%" psql -h %database_host% --port=%database_port% '.
            '-U %database_user% -t -c "'.
            'SELECT pg_terminate_backend(pid) FROM pg_stat_activity '.
            'WHERE datname in (\'%database_name%\', \'%database_template%\') AND pid <> pg_backend_pid()" postgres';
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        if (!$this->dbTemp) {
            return;
        }
        parent::afterTest($event);
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
        if (!$this->dbTemp) {
            return;
        }

        $event->writeln('<info>Remove Db dump</info>');
        $this->dropDb($this->dbTemp);
    }

    /** {@inheritdoc} */
    public function restoreState(RestoreStateEvent $event)
    {
        if (!$this->dbTemp) {
            return;
        }
        parent::restoreState($event);
        $event->writeln('<info>Remove Db dump</info>');
        $this->dropDb($this->dbTemp);
    }

    /** {@inheritdoc} */
    public function isOutdatedState()
    {
        return (bool)$this->dbTemp;
    }

    /** {@inheritdoc} */
    protected function dump(): void
    {
        $this->dbTemp = $this->dbName.TokenGenerator::generateToken('db');

        $this->createDB($this->dbTemp, $this->dbName);
    }

    /** {@inheritdoc} */
    protected function restore(): void
    {
        $this->createDB($this->dbName, $this->dbTemp);
    }

    protected function createDB(string $dbName, string $template): void
    {
        $this->killConnections();

        $this->runProcess(
            strtr(
                $this->getCreateDbCommand(),
                [
                    '%database_host%' => $this->dbHost,
                    '%database_port%' => $this->dbPort,
                    '%database_name%' => $dbName,
                    '%database_template%' => $template,
                    '%database_user%' => $this->dbUser,
                    '%database_password%' => $this->dbPass,
                ]
            )
        );
    }

    protected function dropDb(string $dbName): void
    {
        $attempts = 1;
        while (true) {
            try {
                $this->killConnections();
                parent::dropDb($dbName);
            } catch (ProcessFailedException $e) {
                if ($attempts < 5) {
                    $attempts++;
                    continue;
                }
                throw $e;
            }
            break;
        }
    }

    private function killConnections()
    {
        $process = strtr(
            $this->getKillDBConnectionsCommand(),
            [
                '%database_host%' => $this->dbHost,
                '%database_port%' => $this->dbPort,
                '%database_name%' => $this->dbTemp,
                '%database_template%' => $this->dbName,
                '%database_user%' => $this->dbUser,
                '%database_password%' => $this->dbPass,
            ]
        );
        $this->runProcess($process);
    }
}
