<?php

namespace Oro\Bundle\SearchBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait MysqlVersionCheckTrait
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @return bool
     */
    protected function isMysqlPlatform()
    {
        return $this->platform->getName() === DatabasePlatformInterface::DATABASE_MYSQL;
    }

    /**
     * @return mixed
     */
    protected function isInnoDBFulltextIndexSupported()
    {
        if (!$this->isMysqlPlatform()) {
            throw new \LogicException('InnoDB engine is supported only by MySQL');
        }

        /** @var Connection $connection */
        $connection = $this->container->get('doctrine.dbal.default_connection');
        $version = $connection->fetchColumn('select version()');

        return version_compare($version, '5.6.0', '>=');
    }
}
