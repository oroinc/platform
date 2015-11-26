<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper as BaseHelper;
use Oro\Bundle\EntityBundle\ORM\QueryUtils;

class DoctrineHelper extends BaseHelper
{
    /**
     * Adds criteria to the query.
     *
     * @param QueryBuilder $qb
     * @param Criteria     $criteria
     */
    public function applyCriteria(QueryBuilder $qb, Criteria $criteria)
    {
        $joins = $criteria->getJoins();
        if (!empty($joins)) {
            $rootAlias = QueryUtils::getSingleRootAlias($qb);
            foreach ($joins as $join) {
                $alias     = $join->getAlias();
                $joinExpr  = str_replace(Criteria::ROOT_ALIAS_PLACEHOLDER, $rootAlias, $join->getJoin());
                $condition = $join->getCondition();
                if ($condition) {
                    $condition = strtr(
                        $condition,
                        [
                            Criteria::ROOT_ALIAS_PLACEHOLDER   => $rootAlias,
                            Criteria::ENTITY_ALIAS_PLACEHOLDER => $alias
                        ]
                    );
                }

                $method = strtolower($join->getJoinType()) . 'Join';
                $qb->{$method}($joinExpr, $alias, $join->getConditionType(), $condition, $join->getIndexBy());
            }
        }
        $qb->addCriteria($criteria);
    }

    /**
     * Gets the ORM metadata descriptor for target entity class of the given child association.
     *
     * @param string   $entityClass
     * @param string[] $associationPath
     *
     * @return ClassMetadata|null
     */
    public function findEntityMetadataByPath($entityClass, array $associationPath)
    {
        $metadata = $this->getEntityMetadataForClass($entityClass, false);
        if (null !== $metadata) {
            foreach ($associationPath as $associationName) {
                if (!$metadata->hasAssociation($associationName)) {
                    $metadata = null;
                    break;
                }
                $metadata = $this->getEntityMetadataForClass($metadata->getAssociationTargetClass($associationName));
            }
        }

        return $metadata;
    }

    /**
     * Gets ORDER BY expression that can be used to sort a collection by entity identifier.
     *
     * @param string $entityClass
     *
     * @return array|null
     */
    public function getOrderByIdentifier($entityClass)
    {
        $ids = $this->getEntityMetadata($entityClass)->getIdentifierFieldNames();
        if (empty($ids)) {
            return null;
        }

        $orderBy = [];
        foreach ($ids as $pk) {
            $orderBy[$pk] = Criteria::ASC;
        }

        return $orderBy;
    }
}
