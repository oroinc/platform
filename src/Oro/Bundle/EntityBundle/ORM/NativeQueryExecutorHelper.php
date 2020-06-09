<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;
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
     * @var array|ClassMetadata[]
     */
    protected $classesMetadata = [];

    /**
     * @param ManagerRegistry $registry
     */
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
}
