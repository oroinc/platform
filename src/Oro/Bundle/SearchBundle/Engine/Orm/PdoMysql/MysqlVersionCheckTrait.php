<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Psr\Container\ContainerInterface;

/**
 * Check MySQL Full Text compatibility
 *
 * @method ContainerInterface getContainer()
 */
trait MysqlVersionCheckTrait
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @return bool
     */
    protected function isMysqlPlatform()
    {
        return $this->platform instanceof MySqlPlatform;
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
        $connection = $this->getContainer()->get('doctrine.dbal.search_connection');
        $version = $connection->fetchColumn('select version()');

        return version_compare($version, '5.6.0', '>=');
    }
}
