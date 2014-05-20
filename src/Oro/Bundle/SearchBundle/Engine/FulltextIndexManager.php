<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Connection;

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
        if ($query = $this->getQuery()) {
            try {
                $this->connection->query($query);

                return true;
            } catch (DBALException $exception) {
                return false;
            }
        }

        return false;
    }

    /**
     * @return string|null
     */
    public function getQuery()
    {
        $config = $this->connection->getParams();

        if (isset($this->configClasses[$config['driver']])) {
            /** @var PdoMysql $className */
            $className = $this->configClasses[$config['driver']];

            return $className::getPlainSql();
        }

        return null;
    }
}
