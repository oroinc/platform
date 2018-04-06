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
     * @var string
     */
    protected $indexName;

    /**
     * @param Connection $connection
     * @param array $configClasses
     * @param string $tableName
     * @param string $indexName
     */
    public function __construct(
        Connection $connection,
        array $configClasses,
        $tableName = 'oro_search_index_text',
        $indexName = 'value'
    ) {
        $this->connection    = $connection;
        $this->configClasses = $configClasses;
        $this->tableName     = $tableName;
        $this->indexName     = $indexName;
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
            throw new \RuntimeException(sprintf('Driver "%s" not found', $driver));
        }

        /** @var PdoMysql $className */
        $className = $this->configClasses[$driver];

        return $className::getPlainSql($this->tableName, $this->indexName);
    }
}
