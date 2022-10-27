<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mysql isolator for docker
 */
class DockerMysqlIsolator extends UnixMysqlIsolator
{
    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return $this->isApplicableOS()
            && DatabaseDriverInterface::DRIVER_MYSQL === $container->getParameter('database_driver')
            && getenv('BEHAT_DATABASE_LOCATION') === 'docker';
    }

    protected function getDropDbCommand(): string
    {
        return $this->runInDocker(
            'mysql -e "drop database %database_name%;" -u %database_user%'
        );
    }

    protected function runInDocker(string $command): string
    {
        $containerName = getenv('BEHAT_DATABASE_CONTAINER_NAME') ?: 'mysql';

        return sprintf('docker exec -i --env=MYSQL_PWD="%%database_password%%" %s %s', $containerName, $command);
    }

    protected function getCreateDbCommand(): string
    {
        return $this->runInDocker(
            'mysql -e "create database %database_name%;" -u %database_user%'
        );
    }

    protected function getDumpDbCommand(): string
    {
        return $this->runInDocker(
            'mysqldump -u %database_user% %database_name% > %database_dump%'
        );
    }

    protected function getRestoreDbCommand(): string
    {
        return $this->runInDocker(
            'mysql -u %database_user% %database_name% < %database_dump%'
        );
    }

    protected function getDbExistsCommand(): string
    {
        return $this->runInDocker(
            'mysql -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA '.
            'WHERE SCHEMA_NAME = \'%database_name%\'" -u %database_user%'
        );
    }
}
