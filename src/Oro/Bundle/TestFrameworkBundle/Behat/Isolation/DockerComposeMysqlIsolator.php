<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mysql isolator for docker compose
 */
class DockerComposeMysqlIsolator extends DockerMysqlIsolator
{
    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return $this->isApplicableOS()
            && DatabaseDriverInterface::DRIVER_MYSQL === $container->getParameter('database_driver')
            && getenv('BEHAT_DATABASE_LOCATION') === 'docker-compose';
    }

    protected function runInDocker(string $command): string
    {
        return sprintf(
            'docker exec -i --env=MYSQL_PWD="%%database_password%%" $(docker-compose ps -q mysql) %s',
            $command
        );
    }
}
