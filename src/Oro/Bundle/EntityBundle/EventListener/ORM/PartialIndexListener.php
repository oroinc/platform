<?php

namespace Oro\Bundle\EntityBundle\EventListener\ORM;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Remove where restriction for a given index for MySQL platform.
 */
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

    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $platform = $event->getEntityManager()->getConnection()->getDatabasePlatform();
        if (!$platform instanceof MySqlPlatform) {
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
