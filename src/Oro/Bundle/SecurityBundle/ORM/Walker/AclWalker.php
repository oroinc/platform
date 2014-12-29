<?php
namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\TreeWalkerAdapter;

use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\ConditionalTerm;
use Doctrine\ORM\Query\AST\InExpression;
use Doctrine\ORM\Query\AST\WhereClause;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\ComparisonExpression;

use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclConditionStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\JoinAssociationCondition;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\SubRequestAclConditionStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclCondition;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\JoinAclCondition;

/**
 * Class AclWalker
 */
class AclWalker extends TreeWalkerAdapter
{
    const ORO_ACL_CONDITION = 'oro_acl.condition';

    const EXPECTED_TYPE = 12;

    /**
     * @inheritdoc
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        /** @var Query $query */
        $query = $this->_getQuery();
        if ($query->hasHint(self::ORO_ACL_CONDITION)) {
            /** @var AclConditionStorage $aclCondition */
            $aclCondition = $query->getHint(self::ORO_ACL_CONDITION);

            if (!$aclCondition->isEmpty()) {
                $whereConditions = $aclCondition->getWhereConditions();
                if (!is_null($whereConditions) && count($whereConditions)) {
                    $this->addAclToWhereClause($AST, $whereConditions);
                }
                $joinConditions = $aclCondition->getJoinConditions();
                if (!is_null($joinConditions) && count($joinConditions)) {
                    $this->addAclToJoinClause($AST, $joinConditions);
                }

                $this->processSubRequests($AST, $aclCondition);
            }
        }

        return $AST;
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
                    $subselect = $conditionalExpression->conditionalFactors[$subRequest->getFactorId()]
                        ->simpleConditionalExpression
                        ->subselect;
                } elseif (isset($conditionalExpression->conditionalTerms)) {
                    $subselect = $conditionalExpression->conditionalTerms[$subRequest->getFactorId()]
                        ->simpleConditionalExpression
                        ->subselect;
                } else {
                    $subselect = $conditionalExpression->simpleConditionalExpression->subselect;
                }

                $whereConditions = $subRequest->getWhereConditions();
                if (!is_null($whereConditions) && count($whereConditions)) {
                    $this->addAclToWhereClause($subselect, $whereConditions);
                }
                $joinConditions = $subRequest->getJoinConditions();
                if (!is_null($joinConditions) && count($joinConditions)) {
                    $this->addAclToJoinClause($subselect, $joinConditions);
                }
            }
        }
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
            /** @var Join $join */
            $join = $fromClause
                ->identificationVariableDeclarations[$condition->getFromKey()]
                ->joins[$condition->getJoinKey()];
            if (!($condition instanceof JoinAssociationCondition)) {
                $aclConditionalFactors = [];
                $this->addConditionFactors($aclConditionalFactors, $condition);
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
                $conditionalFactors = [];
                $this->addConditionFactors($conditionalFactors, $condition);
                if (!empty($conditionalFactors)) {
                    $join->conditionalExpression = new ConditionalTerm($conditionalFactors);
                    $fromClause
                        ->identificationVariableDeclarations[$condition->getFromKey()]
                        ->joins[$condition->getJoinKey()] = $join;
                }
            }
        }
    }

    protected function addConditionFactors(&$aclConditionalFactors, AclCondition $condition)
    {
        $conditionalFactor = $this->getConditionalFactor($condition);
        if ($conditionalFactor) {
            $aclConditionalFactors[] = $conditionalFactor;
        }
        $organizationConditionFactor = $this->getOrganizationCheckCondition($condition);
        if ($organizationConditionFactor) {
            $aclConditionalFactors[] = $organizationConditionFactor;
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
        $aclConditionalFactors = [];
        foreach ($whereConditions as $whereCondition) {
            $this->addConditionFactors($aclConditionalFactors, $whereCondition);
        }

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
                        $AST->whereClause->conditionalExpression->conditionalTerms = array_merge(
                            $AST->whereClause->conditionalExpression->conditionalTerms,
                            $aclConditionalFactors
                        );
                    }
                }
            }
        }
    }

    /**
     * Get acl access level condition
     *
     * @param AclCondition $condition
     * @return ConditionalPrimary
     */
    protected function getConditionalFactor(AclCondition $condition)
    {
        if ($condition->isIgnoreOwner()) {
            return null;
        }

        if ($condition->getValue() == null && $condition->getEntityField() == null) {
            $expression = $this->getAccessDeniedExpression();
        } else {
            $expression = $this->getInExpression($condition);
        }

        $resultCondition = new ConditionalPrimary();
        $resultCondition->simpleConditionalExpression = $expression;

        return $resultCondition;
    }

    /**
     * Generates "1=0" expression
     *
     * @return ComparisonExpression
     */
    protected function getAccessDeniedExpression()
    {
        $leftExpression = new ArithmeticExpression();
        $leftExpression->simpleArithmeticExpression = new Literal(Literal::NUMERIC, 1);
        $rightExpression = new ArithmeticExpression();
        $rightExpression->simpleArithmeticExpression = new Literal(Literal::NUMERIC, 0);

        return new ComparisonExpression($leftExpression, '=', $rightExpression);
    }

    /**
     * Generates "organization_id=value" condition
     *
     * @param AclCondition $whereCondition
     * @return ConditionalPrimary|bool
     */
    protected function getOrganizationCheckCondition(AclCondition $whereCondition)
    {
        if ($whereCondition->getOrganizationField() && $whereCondition->getOrganizationValue() !== null) {
            $pathExpression = new PathExpression(
                self::EXPECTED_TYPE,
                $whereCondition->getEntityAlias(),
                $whereCondition->getOrganizationField()
            );

            $pathExpression->type = PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;
            $leftExpression = new ArithmeticExpression();
            $leftExpression->simpleArithmeticExpression = $pathExpression;
            $rightExpression = new ArithmeticExpression();
            $rightExpression->simpleArithmeticExpression =
                new Literal(Literal::NUMERIC, (int) $whereCondition->getOrganizationValue());

            $resultCondition = new ConditionalPrimary();
            $resultCondition->simpleConditionalExpression =
                new ComparisonExpression($leftExpression, '=', $rightExpression);

            return $resultCondition;
        }

        return false;
    }


    /**
     * generate "in()" expression
     *
     * @param AclCondition $whereCondition
     * @return InExpression
     */
    protected function getInExpression(AclCondition $whereCondition)
    {
        $arithmeticExpression = new ArithmeticExpression();
        $arithmeticExpression->simpleArithmeticExpression = $this->getPathExpression($whereCondition);

        $expression = new InExpression($arithmeticExpression);
        $expression->literals = $this->getLiterals($whereCondition);

        return $expression;
    }

    /**
     * Generate path expression
     *
     * @param AclCondition $whereCondition
     * @return PathExpression
     */
    protected function getPathExpression(AclCondition $whereCondition)
    {
        $pathExpression = new PathExpression(
            self::EXPECTED_TYPE,
            $whereCondition->getEntityAlias(),
            $whereCondition->getEntityField()
        );

        $pathExpression->type = $whereCondition->getPathExpressionType();

        return $pathExpression;
    }

    /**
     * Get array with literal from acl condition value array
     *
     * @param AclCondition $whereCondition
     * @return array
     */
    protected function getLiterals(AclCondition $whereCondition)
    {
        $literals = [];

        if (!is_array($whereCondition->getValue())) {
            $whereCondition->setValue(array($whereCondition->getValue()));
        }
        foreach ($whereCondition->getValue() as $value) {
            $literals[] = new Literal(Literal::NUMERIC, $value);
        }

        return $literals;
    }
}
