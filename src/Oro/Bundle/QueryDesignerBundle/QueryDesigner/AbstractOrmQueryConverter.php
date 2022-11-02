<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;

/**
 * Provides a base functionality to convert a query definition created by the query designer to an ORM query.
 */
abstract class AbstractOrmQueryConverter extends AbstractQueryConverter
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        VirtualRelationProviderInterface $virtualRelationProvider,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct($functionProvider, $virtualFieldProvider, $virtualRelationProvider);
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function getJoinType(string $joinId): ?string
    {
        $joinType = parent::getJoinType($joinId);

        if ($joinType === null) {
            $entityClass = $this->getEntityClass($joinId);
            if ($entityClass) {
                $fieldName = $this->getFieldName($joinId);
                $metadata = $this->getClassMetadata($entityClass);
                $associationMapping = $metadata->getAssociationMapping($fieldName);
                $nullable = true;
                if (isset($associationMapping['joinColumns'])) {
                    $nullable = false;
                    foreach ($associationMapping['joinColumns'] as $joinColumn) {
                        $nullable = ($nullable || ($joinColumn['nullable'] ?? false));
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
    protected function getFieldType(string $entityClass, string $fieldName): ?string
    {
        $result = parent::getFieldType($entityClass, $fieldName);
        if (null === $result) {
            $result = $this->getClassMetadata($entityClass)->getTypeOfField($fieldName);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUnidirectionalJoinCondition(
        string $joinTableAlias,
        string $joinFieldName,
        string $joinAlias,
        string $entityClass
    ): string {
        $metadata = $this->getClassMetadata($entityClass);

        // In the case of virtual fields, metadata may not have an association mapping
        if ($metadata->hasAssociation($joinFieldName)) {
            $associationMapping = $metadata->getAssociationMapping($joinFieldName);
            if ($associationMapping['type'] & ClassMetadata::TO_MANY) {
                return sprintf('%s MEMBER OF %s.%s', $joinTableAlias, $joinAlias, $joinFieldName);
            }
        }

        return sprintf('%s.%s = %s', $joinAlias, $joinFieldName, $joinTableAlias);
    }

    private function getClassMetadata(string $entityClass): ClassMetadata
    {
        return $this->doctrineHelper->getEntityMetadataForClass($entityClass);
    }
}
