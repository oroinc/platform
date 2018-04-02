<?php

namespace Oro\Bundle\SearchBundle\EventListener\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

class FulltextIndexListener
{
    /**
    * @var Connection
    */
    protected $connection;

    /**
     * @var string
     */
    protected $textIndexTableName;

    /**
     * @param string $textIndexTableName
     * @param Connection $connection
     */
    public function __construct($textIndexTableName, Connection $connection)
    {
        $this->textIndexTableName = $textIndexTableName;
        $this->connection = $connection;
    }

    /**
     * @param LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $driverName = $this->connection->getDriver()->getName();
        if (in_array($driverName, ['pdo_mysql', 'mysqli']) === false) {
            return;
        }

        /** @var ClassMetadataInfo $classMetadata */
        $classMetadata = $event->getClassMetadata();

        if ($classMetadata->getTableName() !== $this->textIndexTableName) {
            return;
        }

        $engine = PdoMysql::ENGINE_MYISAM;
        $version = $this->connection->fetchColumn('select version()');
        if (version_compare($version, '5.6.0', '>=')) {
            $engine = PdoMysql::ENGINE_INNODB;
        }

        $classMetadata->table['options']['engine'] = $engine;
        $classMetadata->table['indexes']['value'] = ['columns' => ['value'], 'flags' => ['fulltext']];
    }
}
