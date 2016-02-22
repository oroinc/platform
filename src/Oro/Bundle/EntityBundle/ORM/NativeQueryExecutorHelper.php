<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;

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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * Copy of Doctrine\ORM\Query::processParameterMappings
     *
     * @param Query $query
     * @return array
     * @throws QueryException
     */
    public function processParameterMappings(Query $query)
    {
        $parser = new Parser($query);
        $parseResult = $parser->parse();
        $paramMappings = $parseResult->getParameterMappings();
        $resultSetMapping = $parseResult->getResultSetMapping();

        $paramCount = count($query->getParameters());
        $mappingCount = count($paramMappings);

        if ($paramCount > $mappingCount) {
            throw QueryException::tooManyParameters($mappingCount, $paramCount);
        } elseif ($paramCount < $mappingCount) {
            throw QueryException::tooFewParameters($mappingCount, $paramCount);
        }

        $sqlParams = [];
        $types = [];

        foreach ($query->getParameters() as $parameter) {
            $key = $parameter->getName();
            $value = $parameter->getValue();
            $rsm = $resultSetMapping;

            if (!isset($paramMappings[$key])) {
                throw QueryException::unknownParameter($key);
            }

            if (isset($rsm->metadataParameterMapping[$key]) && $value instanceof ClassMetadata) {
                $value = $value->getMetadataValue($rsm->metadataParameterMapping[$key]);
            }

            $value = $query->processParameterValue($value);
            $type = ($parameter->getValue() === $value)
                ? $parameter->getType()
                : Query\ParameterTypeInferer::inferType($value);

            foreach ($paramMappings[$key] as $position) {
                $types[$position] = $type;
            }

            $sqlPositions = $paramMappings[$key];

            // optimized multi value sql positions away for now,
            // they are not allowed in DQL anyways.
            $value = [$value];
            $countValue = count($value);

            for ($i = 0, $l = count($sqlPositions); $i < $l; $i++) {
                $sqlParams[$sqlPositions[$i]] = $value[($i % $countValue)];
            }
        }

        if (count($sqlParams) !== count($types)) {
            throw QueryException::parameterTypeMismatch();
        }

        if ($sqlParams) {
            ksort($sqlParams);
            $sqlParams = array_values($sqlParams);

            ksort($types);
            $types = array_values($types);
        }

        return [$sqlParams, $types];
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
