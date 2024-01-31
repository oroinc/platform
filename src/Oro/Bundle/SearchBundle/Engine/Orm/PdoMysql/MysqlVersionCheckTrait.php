<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;

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
