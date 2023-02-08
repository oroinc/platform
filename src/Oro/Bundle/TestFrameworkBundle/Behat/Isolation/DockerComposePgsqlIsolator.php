<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pgsql isolator for docker compose
 */
class DockerComposePgsqlIsolator extends DockerPgsqlIsolator
{
    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return self::isApplicableOS() && getenv('BEHAT_DATABASE_LOCATION') === 'docker-compose';
    }

    protected function runInDocker(string $command): string
    {
        return sprintf(
            'docker exec -i --env=PGPASSWORD="%%database_password%%" $(docker compose ps -q pgsql) %s',
            $command
        );
    }
}
