<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Index;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;

/**
 * The schema tool to update database schemas.
 */
class SaveSchemaTool extends SchemaTool
{
    protected ObjectManager|EntityManagerInterface $em;
    protected AbstractPlatform $platform;
    protected LoggerInterface $logger;

    /**
     * {@inheritdoc}
     */
    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        $this->em = $registry->getManager();
        $this->platform = $this->getConnection()->getDatabasePlatform();
        $this->logger = $logger;

        parent::__construct($this->em);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateSchemaSql(array $classes, $saveMode = false)
    {
        if (false === $saveMode) {
            return parent::getUpdateSchemaSql($classes, $saveMode);
        }

        $schemaDiff = $this->getSchemaDiff($classes);

        // clean removed indexes(except those which were created by EXTEND, detected by prefix) and removed columns
        // enable super save mode
        foreach ($schemaDiff->changedTables as $table) {
            // does not matter how they were created, extend mechanism does not allow column/association deletion
            $table->removedColumns = [];
            $table->removedForeignKeys = [];
            $table->removedIndexes = array_filter(
                $table->removedIndexes,
                function ($idx) {
                    $idxName = null;
                    if ($idx instanceof Index) {
                        $idxName = $idx->getName();
                    } elseif (is_string($idx)) {
                        $idxName = $idx;
                    }

                    return str_starts_with($idxName, ExtendDbIdentifierNameGenerator::CUSTOM_INDEX_PREFIX);
                }
            );
        }

        return $schemaDiff->toSaveSql($this->platform);
    }

    /**
     * @param array $classes
     *
     * @return \Doctrine\DBAL\Schema\SchemaDiff
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getSchemaDiff(array $classes)
    {
        $sm = $this->getConnection()->getSchemaManager();

        $fromSchema = $sm->createSchema();
        $toSchema   = $this->getSchemaFromMetadata($classes);

        $comparator = new Comparator();

        return $comparator->compare($fromSchema, $toSchema);
    }

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return $this->em->getConnection();
    }
}
