<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\AST\Node;
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
    const ORO_ACL_WALKER            = 'Oro\Bundle\SecurityBundle\ORM\Walker\AclWalker';
    const ORO_ACL_OUTPUT_SQL_WALKER = 'Oro\Bundle\SecurityBundle\ORM\Walker\SqlWalker';
    const ORO_USER_CLASS            = 'Oro\Bundle\UserBundle\Entity\User';

    /**
     * @var OwnershipConditionDataBuilder
     */
    protected $builder;

    /**
     * @var EntityManager
     */
    protected $em;

    /** @var array */
    protected $entityAliases;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var array */
    protected $queryComponents = [];

    /**
     * @param OwnershipConditionDataBuilder $builder
     * @param EventDispatcherInterface      $eventDispatcher
     */
    public function __construct(OwnershipConditionDataBuilder $builder, EventDispatcherInterface $eventDispatcher)
    {
        $this->builder         = $builder;
        $this->eventDispatcher = $eventDispatcher;
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
            $entityField = $value = $pathExpressionType = $organizationField = $organizationValue = $ignoreOwner = null;
            list($entityField, $value, $pathExpressionType, $organizationField, $organizationValue, $ignoreOwner)
                = $conditionData;

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
        $this->queryComponents = [];

        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }
        /** @var Query $query */
        $this->em = $query->getEntityManager();

        $ast = $query->getAST();
        if ($ast instanceof SelectStatement) {
            list ($whereConditions, $joinConditions, $shareCondition) = $this->processSelect($ast, $permission);
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
            }

            $this->addShareConditionToQuery($query, $shareCondition);

            if (!empty($this->queryComponents)) {
                $query->setHint(SqlWalker::ORO_ACL_QUERY_COMPONENTS, $this->queryComponents);
                $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::ORO_ACL_OUTPUT_SQL_WALKER);
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
     * @return array [whereConditions, joinConditions, shareCondition]
     */
    protected function processSelect($select, $permission)
    {
        if ($select instanceof SelectStatement) {
            $isSubRequest = false;
        } else {
            $isSubRequest = true;
        }

        $whereConditions = [];
        $joinConditions  = [];
        $shareCondition = null;
        $fromClause      = $isSubRequest ? $select->subselectFromClause : $select->fromClause;

        foreach ($fromClause->identificationVariableDeclarations as $fromKey => $identificationVariableDeclaration) {
            $condition = $this->processRangeVariableDeclaration(
                $identificationVariableDeclaration->rangeVariableDeclaration,
                $permission,
                false,
                $isSubRequest
            );
            if ($condition) {
                $whereConditions[] = $condition;
                $shareCondition = $this->processRangeVariableDeclarationShare(
                    $identificationVariableDeclaration->rangeVariableDeclaration,
                    $permission
                );
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
                            true,
                            $isSubRequest
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

        return [$whereConditions, $joinConditions, $shareCondition];
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
     * @param bool                     $isSubRequest
     *
     * @return null|AclCondition|JoinAclCondition
     */
    protected function processRangeVariableDeclaration(
        RangeVariableDeclaration $rangeVariableDeclaration,
        $permission,
        $isJoin = false,
        $isSubRequest = false
    ) {
        $this->addEntityAlias($rangeVariableDeclaration);
        $entityName = $rangeVariableDeclaration->abstractSchemaName;
        $entityAlias = $rangeVariableDeclaration->aliasIdentificationVariable;


        $isUserTable = in_array($rangeVariableDeclaration->abstractSchemaName, [self::ORO_USER_CLASS]);
        $resultData = false;
        if (!$isUserTable || $rangeVariableDeclaration->isRoot) {
            $resultData = $this->builder->getAclConditionData($entityName, $permission);
        }

        if ($resultData !== false && ($resultData === null || !empty($resultData))) {
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

    /**
     * Process where and join statements
     *
     * @param RangeVariableDeclaration $rangeVariableDeclaration
     * @param string                   $permission
     *
     * @return Node|null
     */
    protected function processRangeVariableDeclarationShare(
        RangeVariableDeclaration $rangeVariableDeclaration,
        $permission
    ) {
        $entityName = $rangeVariableDeclaration->abstractSchemaName;
        $entityAlias = $rangeVariableDeclaration->aliasIdentificationVariable;

        $resultData = $this->builder->getAclShareData($entityName, $entityAlias, $permission);

        if (!empty($resultData)) {
            list($shareCondition, $queryComponents) = $resultData;
            $this->addQueryComponents($queryComponents);
            return $shareCondition;
        }

        return null;
    }

    /**
     * Add query components which will add to query hints
     *
     * @param array $queryComponents
     * @throws QueryException
     */
    protected function addQueryComponents(array $queryComponents)
    {
        $requiredKeys = array('metadata', 'parent', 'relation', 'map', 'nestingLevel', 'token');

        foreach ($queryComponents as $dqlAlias => $queryComponent) {
            if (array_diff($requiredKeys, array_keys($queryComponent))) {
                throw QueryException::invalidQueryComponent($dqlAlias);
            }

            $this->queryComponents[$dqlAlias] = $queryComponent;
        }
    }

    /**
     * Add to query share condition
     *
     * @param Query     $query
     * @param Node|null $shareCondition
     */
    protected function addShareConditionToQuery(Query $query, $shareCondition)
    {
        if ($shareCondition) {
            $hints = $query->getHints();
            if (!empty($hints[Query::HINT_CUSTOM_TREE_WALKERS])) {
                $customHints = !in_array(self::ORO_ACL_WALKER, $hints[Query::HINT_CUSTOM_TREE_WALKERS])
                    ? array_merge($hints[Query::HINT_CUSTOM_TREE_WALKERS], [self::ORO_ACL_WALKER])
                    : $hints[Query::HINT_CUSTOM_TREE_WALKERS];
            } else {
                $customHints = [self::ORO_ACL_WALKER];
            }
            $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $customHints);
            $query->setHint(AclWalker::ORO_ACL_SHARE_CONDITION, $shareCondition);
        }
    }
}
