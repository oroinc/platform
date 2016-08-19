<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

class FulltextIndexManager
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $configClasses;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @param Connection $connection
     * @param array      $configClasses
     * @param string     $tableName
     */
    public function __construct(Connection $connection, array $configClasses, $tableName = 'oro_search_index_text')
    {
        $this->connection    = $connection;
        $this->configClasses = $configClasses;
        $this->tableName     = $tableName;
    }

    /**
     * @return bool
     */
    public function createIndexes()
    {
        try {
            $this->connection->query($this->getQuery());
        } catch (DBALException $exception) {
            return false;
        }

        return true;
    }

    /**
     * @throws \RuntimeException
     * @return string
     */
    public function getQuery()
    {
        $config = $this->connection->getParams();
        $driver = $config['driver'];

        if (!isset($this->configClasses[$driver])) {
            throw new \RuntimeException('Driver "%s" not found');
        }

        /** @var PdoMysql $className */
        $className = $this->configClasses[$driver];

        return $className::getPlainSql($this->tableName);
    }
}
