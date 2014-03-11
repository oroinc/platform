<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class EntityMetadataHelper
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var string[] {table name} => {class name}
     */
    protected $tableToClassMap;

    /**
     * @var string[] {class name} => {table name}
     */
    protected $classToTableMap;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Gets an entity full class name by entity table name
     *
     * @param string $tableName
     * @return string|null
     */
    public function getEntityClassByTableName($tableName)
    {
        $this->ensureNameMapsLoaded();

        return isset($this->tableToClassMap[$tableName])
            ? $this->tableToClassMap[$tableName]
            : null;
    }

    /**
     * Gets an entity table name by entity full class name
     *
     * @param string $className
     * @return string|null
     */
    public function getTableNameByEntityClass($className)
    {
        $this->ensureNameMapsLoaded();

        return isset($this->classToTableMap[$className])
            ? $this->classToTableMap[$className]
            : null;
    }

    /**
     * Gets an entity field name by entity table name and column name
     *
     * @param string $tableName
     * @param string $columnName
     * @return string|null
     */
    public function getFieldNameByColumnName($tableName, $columnName)
    {
        $className = $this->getEntityClassByTableName($tableName);
        if ($className) {
            $manager = $this->doctrine->getManagerForClass($className);
            if ($manager instanceof EntityManager) {
                return $manager->getClassMetadata($className)->getFieldName($columnName);
            }
        }

        return null;
    }

    /**
     * Makes sure that table name <-> entity class name maps loaded
     */
    protected function ensureNameMapsLoaded()
    {
        if (null === $this->tableToClassMap) {
            $this->loadNameMaps();
        }
    }

    /**
     * Loads table name <-> entity class name maps
     */
    protected function loadNameMaps()
    {
        $this->tableToClassMap = [];
        $this->classToTableMap = [];
        $names = array_keys($this->doctrine->getManagers());
        foreach ($names as $name) {
            $manager = $this->doctrine->getManager($name);
            if ($manager instanceof EntityManager) {
                $allMetadata = $this->getAllMetadata($manager);
                foreach ($allMetadata as $metadata) {
                    $tableName = $metadata->getTableName();
                    if (!empty($tableName)) {
                        $className = $metadata->getName();
                        $this->tableToClassMap[$tableName] = $className;
                        $this->classToTableMap[$className] = $tableName;
                    }
                }
            }
        }
    }

    /**
     * Loads the metadata of all entities known to the given entity manager
     * mapping driver.
     *
     * @param EntityManager $em
     * @return ClassMetadata[]
     */
    protected function getAllMetadata(EntityManager $em)
    {
        try {
            return $em->getMetadataFactory()->getAllMetadata();
        } catch (\ReflectionException $ex) {
            // one of a reason $em->getMetadataFactory()->getAllMetadata() fails is
            // because there are some dynamic fields, for example for manyToOne relations and in case if
            // a field declaration in PHP class and metadata created by an event listener is different
            // Doctrine MetadataFactory fails. One of example when it happens is renaming extended column.
            // try to load metadata using low level algorithm based on Doctrine drivers
            // please note that metadata retrieved in this way is not full and can be used only to get a table name
            $result = [];
            $configuration = $em->getConfiguration();
            $driver        = $configuration->getMetadataDriverImpl();
            $allClassNames = $driver->getAllClassNames();
            foreach ($allClassNames as $className) {
                $metadata = new ClassMetadata($className, $configuration->getNamingStrategy());
                $driver->loadMetadataForClass($className, $metadata);
                $result[] = $metadata;
            }

            return $result;
        }
    }
}
