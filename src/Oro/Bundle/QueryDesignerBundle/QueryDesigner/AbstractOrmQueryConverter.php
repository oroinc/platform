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

    /**
     * Get filter type for given field type
     *
     * @param string $fieldType
     * @return string
     */
    protected function getFilterType($fieldType)
    {
        switch ($fieldType) {
            case 'integer':
            case 'smallint':
            case 'bigint':
            case 'decimal':
            case 'float':
                return 'number';
            case 'boolean':
                return 'boolean';
            case 'date':
            case 'datetime':
                return 'datetime';
        }

        return 'string';
    }
}
