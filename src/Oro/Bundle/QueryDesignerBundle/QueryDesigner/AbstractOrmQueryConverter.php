<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;

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
     * @param FunctionProviderInterface     $functionProvider
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     * @param ManagerRegistry               $doctrine
     */
    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        ManagerRegistry $doctrine
    ) {
        parent::__construct($functionProvider, $virtualFieldProvider);
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    protected function getJoinType($joinId)
    {
        $joinType = parent::getJoinType($joinId);

        if ($joinType === null) {
            $entityClassName = $this->getEntityClassName($joinId);
            if (!empty($entityClassName)) {
                $fieldName          = $this->getFieldName($joinId);
                $metadata           = $this->getClassMetadata($entityClassName);
                $associationMapping = $metadata->getAssociationMapping($fieldName);
                $nullable           = true;
                if (isset($associationMapping['joinColumns'])) {
                    $nullable = false;
                    foreach ($associationMapping['joinColumns'] as $joinColumn) {
                        $nullable = ($nullable || (isset($joinColumn['nullable']) ? $joinColumn['nullable'] : false));
                    }
                }
                $joinType = $nullable ? self::LEFT_JOIN : self::INNER_JOIN;
            }
        }

        return $joinType;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFieldType($className, $fieldName)
    {
        $result = parent::getFieldType($className, $fieldName);
        if (null === $result) {
            $result = $this->getClassMetadata($className)->getTypeOfField($fieldName);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUnidirectionalJoinCondition($joinTableAlias, $joinFieldName, $joinAlias, $entityClassName)
    {
        $metaData = $this->getClassMetadata($entityClassName);
        $associationMapping = $metaData->getAssociationMapping($joinFieldName);
        if ($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
            return sprintf('%s MEMBER OF %s.%s', $joinTableAlias, $joinAlias, $joinFieldName);
        }

        return sprintf('%s.%s = %s', $joinAlias, $joinFieldName, $joinTableAlias);
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
