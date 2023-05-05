<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pgsql isolator for docker
 */
class DockerPgsqlIsolator extends UnixPgsqlIsolator
{
    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return self::isApplicableOS() && getenv('BEHAT_DATABASE_LOCATION') === 'docker';
    }

    protected function getDropDbCommand(): string
    {
        return $this->runInDocker('dropdb --username="%database_user%" %database_name%');
    }

    protected function runInDocker(string $command): string
    {
        $containerName = getenv('BEHAT_DATABASE_CONTAINER_NAME') ?: 'pgsql';

        return sprintf('docker exec -i --env=PGPASSWORD="%%database_password%%" %s %s', $containerName, $command);
    }

    protected function getCreateDbCommand(): string
    {
        return $this->runInDocker(
            'createdb --username=%database_user% --owner=%database_user% --template=%database_template% %database_name%'
        );
    }

    protected function getKillDBConnectionsCommand(): string
    {
        return $this->runInDocker(
            'psql -U %database_user% -t -c "'.
            'SELECT pg_terminate_backend(pid) FROM pg_stat_activity '.
            'WHERE datname in (\'%database_name%\', \'%database_template%\') AND pid <> pg_backend_pid()" postgres'
        );
    }
}
