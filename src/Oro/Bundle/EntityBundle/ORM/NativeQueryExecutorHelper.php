<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;

/**
 * Provides a set of methods to help the native SQL query execution.
 */
class NativeQueryExecutorHelper
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $tablesNames = [];

    /**
     * @var array
     */
    protected $tablesColumns = [];

    /**
     * @var array|ClassMetadata[]
     */
    protected $classesMetadata = [];

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Query $query
     * @return array
     * @throws QueryException
     */
    public function processParameterMappings(Query $query)
    {
        $paramMappings = QueryUtil::parseQuery($query)->getParameterMappings();

        $paramCount = count($query->getParameters());
        $mappingCount = count($paramMappings);
        if ($paramCount > $mappingCount) {
            throw QueryException::tooManyParameters($mappingCount, $paramCount);
        }
        if ($paramCount < $mappingCount) {
            throw QueryException::tooFewParameters($mappingCount, $paramCount);
        }

        return QueryUtil::processParameterMappings($query, $paramMappings);
    }

    /**
     * @param string $className
     * @return string
     */
    public function getTableName($className)
    {
        if (!array_key_exists($className, $this->tablesNames)) {
            $this->tablesNames[$className] = $this->getClassMetadata($className)->table['name'];
        }
        return $this->tablesNames[$className];
    }

    /**
     * @param string $className
     * @return EntityManager|null
     */
    public function getManager($className)
    {
        return $this->registry->getManagerForClass($className);
    }

    /**
     * @param string $className
     * @return ClassMetadata
     */
    public function getClassMetadata($className)
    {
        if (!array_key_exists($className, $this->classesMetadata)) {
            $this->classesMetadata[$className] = $this->getManager($className)->getClassMetadata($className);
        }

        return $this->classesMetadata[$className];
    }

    public function getColumns(string $className, array $fields): array
    {
        $result = [];

        foreach ($fields as $field) {
            if (!isset($this->tablesColumns[$className][$field])) {
                $classMetadata = $this->getClassMetadata($className);
                if (!$classMetadata->hasField($field) && !$classMetadata->hasAssociation($field)) {
                    throw new \InvalidArgumentException(sprintf('Field %s is not known for %s', $field, $className));
                }
                if ($classMetadata->hasAssociation($field)) {
                    $mapping = $classMetadata->getAssociationMapping($field);
                    $this->tablesColumns[$className][$field] = array_shift($mapping['joinColumnFieldNames']);
                } else {
                    $this->tablesColumns[$className][$field] = $classMetadata->getColumnName($field);
                }
            }
            $result[] = $this->tablesColumns[$className][$field];
        }

        return $result;
    }
}
