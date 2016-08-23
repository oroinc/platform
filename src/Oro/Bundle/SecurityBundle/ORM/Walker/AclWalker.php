<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ConditionalTerm;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\AST\WhereClause;

use Oro\Bundle\SecurityBundle\Exception\NotFoundAclConditionFactorBuilderException;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclConditionStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\JoinAssociationCondition;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\SubRequestAclConditionStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclCondition;

/**
 * Class AclWalker
 */
class AclWalker extends TreeWalkerAdapter
{
    const ORO_ACL_CONDITION = 'oro_acl.condition';
    const ORO_ACL_FACTOR_BUILDER = 'oro_acl.factor.builder';

    /** @var AclConditionalFactorBuilder */
    protected $aclConditionFactorBuilder;

    /**
     * @inheritdoc
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        /** @var Query $query */
        $query = $this->_getQuery();

        if (!$query->hasHint(self::ORO_ACL_FACTOR_BUILDER)
            || !$query->getHint(self::ORO_ACL_FACTOR_BUILDER) instanceof AclConditionalFactorBuilder) {
            throw new NotFoundAclConditionFactorBuilderException();
        }

        $this->aclConditionFactorBuilder = $query->getHint(self::ORO_ACL_FACTOR_BUILDER);

        if ($query->hasHint(self::ORO_ACL_CONDITION)) {
            /** @var AclConditionStorage $aclCondition */
            $aclCondition = $query->getHint(self::ORO_ACL_CONDITION);

            if (!$aclCondition->isEmpty()) {
                $this->addRequestConditions($AST, $aclCondition);
                $this->processSubRequests($AST, $aclCondition);
            }
        }

        return $AST;
    }

    /**
     * @param SelectStatement|Subselect                         $AST
     * @param AclConditionStorage|SubRequestAclConditionStorage $aclCondition
     */
    protected function addRequestConditions($AST, $aclCondition)
    {
        $whereConditions = $aclCondition->getWhereConditions();
        if (count($whereConditions)) {
            $this->addAclToWhereClause($AST, $whereConditions);
        }
        $joinConditions = $aclCondition->getJoinConditions();
        if (count($joinConditions)) {
            $this->addAclToJoinClause($AST, $joinConditions);
        }
    }

    /**
     * process subselects of query
     *
     * @param SelectStatement     $AST
     * @param AclConditionStorage $aclCondition
     */
    protected function processSubRequests(SelectStatement $AST, AclConditionStorage $aclCondition)
    {
        if (!is_null($aclCondition->getSubRequests())) {
            $subRequests = $aclCondition->getSubRequests();

            foreach ($subRequests as $subRequest) {
                /** @var SubRequestAclConditionStorage $subRequest */
                $conditionalExpression = $AST
                    ->whereClause
                    ->conditionalExpression;

                if (isset($conditionalExpression->conditionalFactors)) {
                    $factorId = $subRequest->getFactorId();
                    foreach ($conditionalExpression->conditionalFactors as $conditionalFactorId => $factor) {
                        $subSelect = $this->getSubSelectFromFactor($factor, $factorId, $conditionalFactorId);

                        if ($subSelect) {
                            $this->addRequestConditions($subSelect, $subRequest);
                        }
                    }
                } elseif (isset($conditionalExpression->simpleConditionalExpression)) {
                    $subSelect = $conditionalExpression->simpleConditionalExpression->subselect;

                    $this->addRequestConditions($subSelect, $subRequest);
                }
            }
        }
    }


    /**
     * @param ConditionalPrimary $factor
     * @param int                $factorId
     * @param int|null           $conditionalFactorId
     *
     * @return Subselect
     */
    protected function getSubSelectFromFactor(ConditionalPrimary $factor, $factorId, $conditionalFactorId = null)
    {
        $subSelect = null;

        if (isset($factor->conditionalExpression->conditionalFactors)) {
            $subSelect = $factor
                ->conditionalExpression
                ->conditionalFactors[$factorId]
                ->simpleConditionalExpression
                ->subselect;
        } elseif (isset($factor->conditionalExpression->conditionalTerms)) {
            $subSelect = $factor
                ->conditionalExpression
                ->conditionalTerms[$factorId]
                ->simpleConditionalExpression
                ->subselect;
        } elseif (isset($factor->simpleConditionalExpression->subselect) && $factorId === $conditionalFactorId) {
            $subSelect = $factor->simpleConditionalExpression->subselect;
        }

        return $subSelect;
    }

    /**
     * work with join statements of query
     *
     * @param SelectStatement $AST
     * @param array           $joinConditions
     */
    protected function addAclToJoinClause($AST, array $joinConditions)
    {
        if ($AST instanceof Subselect) {
            $fromClause = $AST->subselectFromClause;
        } else {
            $fromClause = $AST->fromClause;
        }
        foreach ($joinConditions as $condition) {
            if ($condition instanceof AclCondition) {
                /** @var Join $join */
                $join = $fromClause
                    ->identificationVariableDeclarations[$condition->getFromKey()]
                    ->joins[$condition->getJoinKey()];
                if (!($condition instanceof JoinAssociationCondition)) {
                    $aclConditionalFactors = $this->aclConditionFactorBuilder->addJoinAclConditionalFactor(
                        [], //default empty conditional factors
                        $condition,
                        $this->_getQuery()
                    );
                    if (!empty($aclConditionalFactors)) {
                        if ($join->conditionalExpression instanceof ConditionalPrimary) {
                            array_unshift($aclConditionalFactors, $join->conditionalExpression);
                            $join->conditionalExpression = new ConditionalTerm(
                                $aclConditionalFactors
                            );
                        } else {
                            $join->conditionalExpression->conditionalFactors = array_merge(
                                $join->conditionalExpression->conditionalFactors,
                                $aclConditionalFactors
                            );
                        }
                    }
                } else {
                    $conditionalFactors = $this->aclConditionFactorBuilder->addJoinAclConditionalFactor(
                        [], //default empty conditional factors
                        $condition,
                        $this->_getQuery()
                    );
                    if (!empty($conditionalFactors)) {
                        $join->conditionalExpression = new ConditionalTerm($conditionalFactors);
                        $fromClause
                            ->identificationVariableDeclarations[$condition->getFromKey()]
                            ->joins[$condition->getJoinKey()] = $join;
                    }
                }
            }
        }
    }

    /**
     * work with "where" statement of query
     *
     * @param SelectStatement $AST
     * @param array           $whereConditions
     */
    protected function addAclToWhereClause($AST, array $whereConditions)
    {
        $aclConditionalFactors = $this->aclConditionFactorBuilder->addWhereAclConditionalFactors(
            [], //default empty conditional factors
            $whereConditions,
            $this->_getQuery()
        );

        if (!empty($aclConditionalFactors)) {
            // we have query without 'where' part
            if ($AST->whereClause === null) {
                $AST->whereClause = new WhereClause(new ConditionalTerm($aclConditionalFactors));
            } else {
                // 'where' part has only one condition
                if ($AST->whereClause->conditionalExpression instanceof ConditionalPrimary) {
                    array_unshift($aclConditionalFactors, $AST->whereClause->conditionalExpression);
                    $AST->whereClause->conditionalExpression = new ConditionalTerm(
                        $aclConditionalFactors
                    );
                } else {
                    // 'where' part has more than one condition
                    if (isset($AST->whereClause->conditionalExpression->conditionalFactors)) {
                        $AST->whereClause->conditionalExpression->conditionalFactors = array_merge(
                            $AST->whereClause->conditionalExpression->conditionalFactors,
                            $aclConditionalFactors
                        );
                    } else {
                        $conditionalPrimary = new ConditionalPrimary();
                        $conditionalPrimary->conditionalExpression = $AST->whereClause->conditionalExpression;
                        array_unshift($aclConditionalFactors, $conditionalPrimary);
                        $AST->whereClause->conditionalExpression = new ConditionalTerm($aclConditionalFactors);
                    }
                }
            }
        }
    }
}
