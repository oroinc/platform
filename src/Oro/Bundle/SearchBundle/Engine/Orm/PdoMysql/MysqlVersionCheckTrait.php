<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;

/**
 * Check MySQL Full Text compatibility
 */
trait MysqlVersionCheckTrait
{
    /**
     * @return bool
     */
    protected function isMysqlPlatform()
    {
        return $this->platform instanceof MySQLPlatform;
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
        $version = $connection->fetchOne('select version()');

        return version_compare($version, '5.6.0', '>=');
    }
}
