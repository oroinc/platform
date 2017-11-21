<?php

namespace Oro\Bundle\EntityBundle\EventListener\ORM;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;

class PartialIndexListener
{
    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private $indexName;

    /**
     * @param string $textIndexTableName
     * @param string $indexName
     */
    public function __construct($textIndexTableName, $indexName)
    {
        $this->tableName = $textIndexTableName;
        $this->indexName = $indexName;
    }

    /**
     * @param LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $driverName = $event->getEntityManager()->getConnection()->getDriver()->getName();
        if ($driverName !== DatabaseDriverInterface::DRIVER_MYSQL) {
            return;
        }

        /** @var ClassMetadataInfo $classMetadata */
        $classMetadata = $event->getClassMetadata();
        if ($classMetadata->getTableName() !== $this->tableName) {
            return;
        }

        unset($classMetadata->table['indexes'][$this->indexName]['options']['where']);
    }
}
