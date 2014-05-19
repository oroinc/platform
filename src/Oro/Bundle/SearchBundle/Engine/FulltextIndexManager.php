<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Connection;

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
        $config = $this->connection->getParams();

        if (isset($this->configClasses[$config['driver']])) {
            $className = $this->configClasses[$config['driver']];

            try {
                $this->connection->query($className::getPlainSql());

                return true;
            } catch (DBALException $exception) {
                return false;
            }
        }

        return false;
    }
}
