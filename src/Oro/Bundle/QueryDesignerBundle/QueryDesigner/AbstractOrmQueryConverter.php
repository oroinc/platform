<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

abstract class AbstractOrmQueryConverter extends AbstractQueryConverter
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ClassMetadataInfo[]
     */
    protected $classMetadataLocalCache;

    /**
     * Constructor
     *
     * @param FunctionProviderInterface $functionProvider
     * @param ManagerRegistry           $doctrine
     */
    public function __construct(FunctionProviderInterface $functionProvider, ManagerRegistry $doctrine)
    {
        parent::__construct($functionProvider);
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    protected function addJoinStatement($joinTableAlias, $joinFieldName, $joinAlias)
    {
        if ($this->isUnidirectionalJoin($joinAlias)) {
            $this->addUnidirectionalJoinStatement($joinTableAlias, $joinFieldName, $joinAlias);
        } else {
            $this->addBidirectionalJoinStatement($joinTableAlias, $joinFieldName, $joinAlias);
        }
    }

    /**
     * Checks if the given join alias represents unidirectional relationship
     *
     * @param string $joinAlias
     * @return bool
     */
    protected function isUnidirectionalJoin($joinAlias)
    {
        return 3 === count($this->getUnidirectionalJoinParts($joinAlias));
    }

    /**
     * Builds JOIN condition for unidirectional relationship
     *
     * @param string $joinTableAlias
     * @param string $joinFieldName
     * @param string $joinAlias
     * @return string
     */
    protected function getUnidirectionalJoinCondition($joinTableAlias, $joinFieldName, $joinAlias)
    {
        return sprintf('%s.%s = %s', $joinAlias, $joinFieldName, $joinTableAlias);
    }

    /**
     * Extracts entity name for unidirectional relationship
     *
     * @param string $joinAlias
     * @return string
     */
    protected function getUnidirectionalJoinEntity($joinAlias)
    {
        $joinParts = $this->getUnidirectionalJoinParts($joinAlias);

        return $joinParts[1];
    }

    /**
     * Splits the given unidirectional relationship into parts
     *
     * @param string $joinAlias
     * @return string
     */
    protected function getUnidirectionalJoinParts($joinAlias)
    {
        return explode(
            '::',
            $this->getJoinIdentifierLastPart($this->getJoinIdentifierByTableAlias($joinAlias))
        );
    }

    /**
     * Performs conversion of unidirectional JOIN statement
     *
     * @param string $joinTableAlias
     * @param string $joinFieldName
     * @param string $joinAlias
     */
    abstract protected function addUnidirectionalJoinStatement($joinTableAlias, $joinFieldName, $joinAlias);

    /**
     * Performs conversion of bidirectional JOIN statement
     *
     * @param string $joinTableAlias
     * @param string $joinFieldName
     * @param string $joinAlias
     */
    abstract protected function addBidirectionalJoinStatement($joinTableAlias, $joinFieldName, $joinAlias);

    /**
     * Gets a field data type
     *
     * @param string $className
     * @param string $fieldName
     * @return string
     */
    protected function getFieldType($className, $fieldName)
    {
        return $this->getClassMetadata($className)->getTypeOfField($fieldName);
    }

    /**
     * Check whether the given join is INNER JOIN or LEFT JOIN
     *
     * @param string $joinAlias
     * @param string $joinFieldName
     * @return bool true if INNER JOIN; otherwise, false
     */
    protected function isInnerJoin($joinAlias, $joinFieldName)
    {
        $metadata = $this->getClassMetadata(
            $this->getEntityClassName($this->getJoinIdentifierByTableAlias($joinAlias))
        );
        $nullable = true;
        $associationMapping = $metadata->getAssociationMapping($joinFieldName);
        if (isset($associationMapping['joinColumns'])) {
            $nullable = false;
            foreach ($associationMapping['joinColumns'] as $joinColumn) {
                $nullable = ($nullable || (isset($joinColumn['nullable']) ? $joinColumn['nullable'] : false));
            }
        }

        return !$nullable;
    }

    /**
     * Returns a metadata for the given entity
     *
     * @param string $className
     * @return ClassMetadataInfo
     */
    protected function getClassMetadata($className)
    {
        if (isset($this->classMetadataLocalCache[$className])) {
            return $this->classMetadataLocalCache[$className];
        }

        $classMetadata                             = $this->doctrine
            ->getManagerForClass($className)
            ->getClassMetadata($className);
        $this->classMetadataLocalCache[$className] = $classMetadata;

        return $classMetadata;
    }
}
