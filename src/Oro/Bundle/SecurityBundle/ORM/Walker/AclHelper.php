<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclConditionStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\SubRequestAclConditionStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclCondition;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\JoinAclCondition;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\JoinAssociationCondition;
use Oro\Bundle\SecurityBundle\Event\ProcessSelectAfter;

/**
 * Class ACLHelper
 * This class analyse input query for acl and mark it with ORO_ACL_WALKER if it need to be ACL protected.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AclHelper
{
    const ORO_ACL_WALKER = 'Oro\Bundle\SecurityBundle\ORM\Walker\AclWalker';
    const ORO_USER_CLASS = 'Oro\Bundle\UserBundle\Entity\User';

    /** @var OwnershipConditionDataBuilder */
    protected $builder;

    /** @var EntityManager */
    protected $em;

    /** @var array */
    protected $entityAliases;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var AclConditionalFactorBuilder */
    protected $aclConditionFactorBuilder;

    /**
     * @param OwnershipConditionDataBuilder $builder
     * @param EventDispatcherInterface      $eventDispatcher
     * @param AclConditionalFactorBuilder   $aclConditionFactorBuilder
     */
    public function __construct(
        OwnershipConditionDataBuilder $builder,
        EventDispatcherInterface $eventDispatcher,
        AclConditionalFactorBuilder $aclConditionFactorBuilder
    ) {
        $this->builder = $builder;
        $this->eventDispatcher = $eventDispatcher;
        $this->aclConditionFactorBuilder = $aclConditionFactorBuilder;
    }

    /**
     * Add ACL checks to Criteria
     *
     * @param string   $className
     * @param Criteria $criteria
     * @param string   $permission
     *
     * @return Criteria
     */
    public function applyAclToCriteria($className, Criteria $criteria, $permission, $mapField = [])
    {
        $conditionData = $this->builder->getAclConditionData($className, $permission);
        if (!empty($conditionData)) {
            list($entityField, $value, , $organizationField, $organizationValue, $ignoreOwner) = $conditionData;

            if (isset($mapField[$organizationField])) {
                $organizationField = $mapField[$organizationField];
            }

            if (isset($mapField[$entityField])) {
                $entityField = $mapField[$entityField];
            }

            if (!is_null($organizationField) && !is_null($organizationValue)) {
                $criteria->andWhere(Criteria::expr()->in($organizationField, [$organizationValue]));
            }
            if (!$ignoreOwner && !empty($value)) {
                if (!is_array($value)) {
                    $value = [$value];
                }
                $criteria->andWhere(Criteria::expr()->in($entityField, $value));
            }
        }

        return $criteria;
    }

    /**
     * Mark query as acl protected
     *
     * @param Query|QueryBuilder $query
     * @param string             $permission
     * @param bool               $checkRelations
     *
     * @return Query
     */
    public function apply($query, $permission = 'VIEW', $checkRelations = true)
    {
        $this->entityAliases = [];

        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }
        /** @var Query $query */
        $this->em = $query->getEntityManager();

        $ast = $query->getAST();
        if ($ast instanceof SelectStatement) {
            list ($whereConditions, $joinConditions) = $this->processSelect($ast, $permission);
            $conditionStorage = new AclConditionStorage($whereConditions, $checkRelations ? $joinConditions : []);
            if ($ast->whereClause) {
                $this->processSubselects($ast, $conditionStorage, $permission);
            }

            // We have access level check conditions. So mark query for acl walker.
            if (!$conditionStorage->isEmpty()) {
                $walkers = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);
                if (false === $walkers) {
                    $walkers = [self::ORO_ACL_WALKER];
                    $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $walkers);
                } elseif (!in_array(self::ORO_ACL_WALKER, $walkers, true)) {
                    $walkers[] = self::ORO_ACL_WALKER;
                    $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $walkers);
                }
                $query->setHint(AclWalker::ORO_ACL_CONDITION, $conditionStorage);
                $query->setHint(AclWalker::ORO_ACL_FACTOR_BUILDER, $this->aclConditionFactorBuilder);
            }
        }

        return $query;
    }

    /**
     * Check subrequests for acl access level
     *
     * @param SelectStatement     $ast
     * @param AclConditionStorage $storage
     * @param                     $permission
     */
    protected function processSubselects(SelectStatement $ast, AclConditionStorage $storage, $permission)
    {
        $conditionalExpression = $ast->whereClause->conditionalExpression;
        if (isset($conditionalExpression->conditionalPrimary)) {
            $conditionalExpression = $conditionalExpression->conditionalPrimary;
        }

        if ($conditionalExpression instanceof ConditionalPrimary) {
            // we have request with only one where condition
            $expression = $conditionalExpression->simpleConditionalExpression;
            if (isset($expression->subselect)
                && $expression->subselect instanceof Subselect
            ) {
                $subRequestAclStorage = $this->processSubselect($expression->subselect, $permission);
                if (!$subRequestAclStorage->isEmpty()) {
                    $subRequestAclStorage->setFactorId(0);
                    $storage->addSubRequests($subRequestAclStorage);
                }
            }
        } else {
            // we have request with only many where conditions
            $subQueryAcl = [];
            if (isset($conditionalExpression->conditionalFactors)) {
                $factors = $conditionalExpression->conditionalFactors;
            } else {
                $factors = $conditionalExpression->conditionalTerms;
            }
            foreach ($factors as $factorId => $expression) {
                if (isset($expression->simpleConditionalExpression->subselect)
                    && $expression->simpleConditionalExpression->subselect instanceof Subselect
                ) {
                    $subRequestAclStorage = $this->processSubselect(
                        $expression->simpleConditionalExpression->subselect,
                        $permission
                    );
                    if (!$subRequestAclStorage->isEmpty()) {
                        $subRequestAclStorage->setFactorId($factorId);
                        $subQueryAcl[] = $subRequestAclStorage;
                    }
                }
            }
            if (!empty($subQueryAcl)) {
                $storage->setSubRequests($subQueryAcl);
            }
        }
    }

    /**
     * Check Access levels for subrequest
     *
     * @param Subselect $subSelect
     * @param           $permission
     *
     * @return SubRequestAclConditionStorage
     */
    protected function processSubselect(Subselect $subSelect, $permission)
    {
        list ($whereConditions, $joinConditions) = $this->processSelect($subSelect, $permission);

        return new SubRequestAclConditionStorage($whereConditions, $joinConditions);
    }

    /**
     * Check request
     *
     * @param Subselect|SelectStatement $select
     * @param string                    $permission
     *
     * @return array [whereConditions, joinConditions]
     */
    protected function processSelect($select, $permission)
    {
        $whereConditions = [];
        $joinConditions  = [];

        $fromClause = $select instanceof SelectStatement ? $select->fromClause : $select->subselectFromClause;
        foreach ($fromClause->identificationVariableDeclarations as $fromKey => $identificationVariableDeclaration) {
            $condition = $this->processRangeVariableDeclaration(
                $identificationVariableDeclaration->rangeVariableDeclaration,
                $permission,
                false
            );
            if ($condition) {
                $whereConditions[] = $condition;
            }

            // check joins
            if (!empty($identificationVariableDeclaration->joins)) {
                /** @var $join Join */
                foreach ($identificationVariableDeclaration->joins as $joinKey => $join) {
                    //check if join is simple join (join some_table on (some_table.id = parent_table.id))
                    if ($join->joinAssociationDeclaration instanceof RangeVariableDeclaration) {
                        $condition = $this->processRangeVariableDeclaration(
                            $join->joinAssociationDeclaration,
                            $permission,
                            true
                        );
                    } else {
                        $condition = $this->processJoinAssociationPathExpression(
                            $identificationVariableDeclaration,
                            $joinKey,
                            $permission
                        );
                    }
                    if ($condition) {
                        $condition->setFromKey($fromKey);
                        $condition->setJoinKey($joinKey);
                        $joinConditions[] = $condition;
                    }
                }
            }
        }

        $event = new ProcessSelectAfter($select, $whereConditions, $joinConditions);
        $this->eventDispatcher->dispatch(ProcessSelectAfter::NAME, $event);

        $whereConditions = $event->getWhereConditions();
        $joinConditions  = $event->getJoinConditions();

        return [$whereConditions, $joinConditions];
    }

    /**
     * Process Joins without "on" statement
     *
     * @param IdentificationVariableDeclaration $declaration
     * @param                                   $key
     * @param                                   $permission
     *
     * @return JoinAssociationCondition|null
     */
    protected function processJoinAssociationPathExpression(
        IdentificationVariableDeclaration $declaration,
        $key,
        $permission
    ) {
        /** @var Join $join */
        $join = $declaration->joins[$key];

        $joinParentEntityAlias = $join->joinAssociationDeclaration
            ->joinAssociationPathExpression->identificationVariable;
        $joinParentClass = $this->entityAliases[$joinParentEntityAlias];
        $metadata = $this->em->getClassMetadata($joinParentClass);

        $fieldName = $join->joinAssociationDeclaration->joinAssociationPathExpression->associationField;

        $associationMapping = $metadata->getAssociationMapping($fieldName);
        $targetEntity = $associationMapping['targetEntity'];

        if (!isset($this->entityAliases[$join->joinAssociationDeclaration->aliasIdentificationVariable])) {
            $this->entityAliases[$join->joinAssociationDeclaration->aliasIdentificationVariable] = $targetEntity;
        }

        $resultData = null;
        if (!in_array($targetEntity, [self::ORO_USER_CLASS])) {
            $resultData = $this->builder->getAclConditionData($targetEntity, $permission);
        }

        if ($resultData && is_array($resultData)) {
            $entityField = $value = $pathExpressionType = $organizationField = $organizationValue = $ignoreOwner = null;
            if (!empty($resultData)) {
                list($entityField, $value, $pathExpressionType, $organizationField, $organizationValue, $ignoreOwner)
                    = $resultData;
            }

            return new JoinAssociationCondition(
                $join->joinAssociationDeclaration->aliasIdentificationVariable,
                $entityField,
                $value,
                $pathExpressionType,
                $organizationField,
                $organizationValue,
                $ignoreOwner,
                $targetEntity,
                $this->getJoinConditions($associationMapping)
            );
        }

        return null;
    }

    /**
     * Process where statement
     *
     * @param RangeVariableDeclaration $rangeVariableDeclaration
     * @param string                   $permission
     * @param bool                     $isJoin
     *
     * @return null|AclCondition|JoinAclCondition
     */
    protected function processRangeVariableDeclaration(
        RangeVariableDeclaration $rangeVariableDeclaration,
        $permission,
        $isJoin = false
    ) {
        $resultData = false;

        $this->addEntityAlias($rangeVariableDeclaration);

        $entityName = $rangeVariableDeclaration->abstractSchemaName;
        if ($entityName !== self::ORO_USER_CLASS || $rangeVariableDeclaration->isRoot) {
            $resultData = $this->builder->getAclConditionData($entityName, $permission);
        }

        if ($resultData !== false && ($resultData === null || !empty($resultData))) {
            $entityAlias = $rangeVariableDeclaration->aliasIdentificationVariable;
            $entityField = $value = $pathExpressionType = $organizationField = $organizationValue = $ignoreOwner = null;
            if (!empty($resultData)) {
                list($entityField, $value, $pathExpressionType, $organizationField, $organizationValue, $ignoreOwner)
                    = $resultData;
            }
            if ($isJoin) {
                return new JoinAclCondition(
                    $entityAlias,
                    $entityField,
                    $value,
                    $pathExpressionType,
                    $organizationField,
                    $organizationValue,
                    $ignoreOwner
                );
            } else {
                return new AclCondition(
                    $entityAlias,
                    $entityField,
                    $value,
                    $pathExpressionType,
                    $organizationField,
                    $organizationValue,
                    $ignoreOwner
                );
            }
        }

        return null;
    }

    /**
     * @param RangeVariableDeclaration $rangeDeclaration
     */
    protected function addEntityAlias(RangeVariableDeclaration $rangeDeclaration)
    {
        $alias = $rangeDeclaration->aliasIdentificationVariable;
        if (!isset($this->entityAliases[$alias])) {
            $this->entityAliases[$alias] = $rangeDeclaration->abstractSchemaName;
        }
    }

    /**
     * @param array $associationMapping
     *
     * @return array
     */
    protected function getJoinConditions(array $associationMapping)
    {
        $targetEntity = $associationMapping['targetEntity'];
        $type = $associationMapping['type'];
        $targetEntityMetadata = $this->em->getClassMetadata($targetEntity);
        $joinConditionsColumns = [];
        $joinConditions = [];

        switch ($type) {
            case ClassMetadataInfo::ONE_TO_ONE:
            case ClassMetadataInfo::MANY_TO_ONE:
                $joinConditionsColumns = $associationMapping['joinColumns'];
                break;
            case ClassMetadataInfo::ONE_TO_MANY:
                $joinConditionsColumns = [$associationMapping['mappedBy']];
                break;
            case ClassMetadataInfo::MANY_TO_MANY:
                break;
            default:
                return $joinConditions;
        }

        foreach ($joinConditionsColumns as $joinConditionsColumn) {
            if (is_string($joinConditionsColumn)) {
                $joinConditions[] = $joinConditionsColumn;
            } else {
                $joinConditions[] = $targetEntityMetadata
                    ->getFieldForColumn($joinConditionsColumn['referencedColumnName']);
            }
        }

        return $joinConditions;
    }
}
