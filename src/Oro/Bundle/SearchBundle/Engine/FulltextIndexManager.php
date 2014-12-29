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
     * @param Connection $connection
     * @param array      $configClasses
     */
    public function __construct(Connection $connection, array $configClasses)
    {
        $this->connection    = $connection;
        $this->configClasses = $configClasses;
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

        return $className::getPlainSql();
    }
}
