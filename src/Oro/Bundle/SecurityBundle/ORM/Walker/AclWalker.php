<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use Doctrine\ORM\Query\AST\ConditionalExpression;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ConditionalTerm;
use Doctrine\ORM\Query\AST\ExistsExpression;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\InExpression;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\SimpleSelectClause;
use Doctrine\ORM\Query\AST\SimpleSelectExpression;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\AST\SubselectFromClause;
use Doctrine\ORM\Query\AST\WhereClause;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclConditionStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\JoinAssociationCondition;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\SubRequestAclConditionStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclCondition;

/**
 * Class AclWalker
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AclWalker extends TreeWalkerAdapter
{
    const ORO_ACL_CONDITION = 'oro_acl.condition';
    const ORO_ACL_SHARE_CONDITION = 'oro_acl.share.condition';

    const EXPECTED_TYPE = 12;

    /** @var Node */
    protected $whereShareCondition;

    /**
     * @inheritdoc
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        /** @var Query $query */
        $query = $this->_getQuery();
        $this->whereShareCondition = null;

        if ($query->hasHint(self::ORO_ACL_SHARE_CONDITION)) {
            $this->whereShareCondition = $query->getHint(self::ORO_ACL_SHARE_CONDITION);
        }

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
                    foreach ($conditionalExpression->conditionalFactors as $factor) {
                        $subSelect = $this->getSubSelectFromFactor($factor, $factorId);

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
     *
     * @return Subselect
     */
    protected function getSubSelectFromFactor(ConditionalPrimary $factor, $factorId)
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
        } elseif (isset($factor->simpleConditionalExpression->subselect)) {
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
                        $join->conditionalExpression          = new ConditionalTerm($conditionalFactors);
                        $fromClause
                            ->identificationVariableDeclarations[$condition->getFromKey()]
                            ->joins[$condition->getJoinKey()] = $join;
                    }
                }
            }
        }
    }

    /**
     * @param array        $aclConditionalFactors
     * @param AclCondition $condition
     */
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
            if ($whereCondition instanceof AclCondition) {
                $this->addConditionFactors($aclConditionalFactors, $whereCondition);
            }
        }

        if (!empty($aclConditionalFactors) && $this->whereShareCondition) {
            $aclConditionalFactors = $this->addShareFactor($aclConditionalFactors);
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
                        $conditionalPrimary                        = new ConditionalPrimary();
                        $conditionalPrimary->conditionalExpression = $AST->whereClause->conditionalExpression;
                        array_unshift($aclConditionalFactors, $conditionalPrimary);
                        $AST->whereClause->conditionalExpression = new ConditionalTerm($aclConditionalFactors);
                    }
                }
            }
        }
    }

    /**
     * Get acl access level condition
     *
     * @param AclCondition $condition
     *
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

        $resultCondition                              = new ConditionalPrimary();
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
        $leftExpression                              = new ArithmeticExpression();
        $leftExpression->simpleArithmeticExpression  = new Literal(Literal::NUMERIC, 1);
        $rightExpression                             = new ArithmeticExpression();
        $rightExpression->simpleArithmeticExpression = new Literal(Literal::NUMERIC, 0);

        return new ComparisonExpression($leftExpression, '=', $rightExpression);
    }

    /**
     * Generates "organization_id=value" condition
     *
     * @param AclCondition $whereCondition
     *
     * @return ConditionalPrimary|bool
     */
    protected function getOrganizationCheckCondition(AclCondition $whereCondition)
    {
        if ($whereCondition->getOrganizationField() && $whereCondition->getOrganizationValue() !== null) {
            $organizationValue = $whereCondition->getOrganizationValue();

            $pathExpression       = new PathExpression(
                self::EXPECTED_TYPE,
                $whereCondition->getEntityAlias(),
                $whereCondition->getOrganizationField()
            );
            $pathExpression->type = PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;
            $resultCondition      = new ConditionalPrimary();

            if (is_array($organizationValue)) {
                $resultCondition->simpleConditionalExpression = $this->getInExpression(
                    $whereCondition,
                    'organizationValue',
                    'organizationField'
                );

                return $resultCondition;

            } else {
                $leftExpression                              = new ArithmeticExpression();
                $leftExpression->simpleArithmeticExpression  = $pathExpression;
                $rightExpression                             = new ArithmeticExpression();
                $rightExpression->simpleArithmeticExpression =
                    new Literal(Literal::NUMERIC, (int)$organizationValue);

                $resultCondition->simpleConditionalExpression =
                    new ComparisonExpression($leftExpression, '=', $rightExpression);

                return $resultCondition;
            }
        }

        return false;
    }

    /**
     * generate "in()" expression
     *
     * @param AclCondition $whereCondition
     * @param string       $iterationValue = 'value'
     * @param string       $iterationField = 'entityField'
     *
     * @return InExpression
     */
    protected function getInExpression(
        AclCondition $whereCondition,
        $iterationValue = 'value',
        $iterationField = 'entityField'
    ) {
        $arithmeticExpression                             = new ArithmeticExpression();
        $arithmeticExpression->simpleArithmeticExpression = $this->getPathExpression(
            $whereCondition,
            $iterationField
        );

        $expression           = new InExpression($arithmeticExpression);
        $expression->literals = $this->getLiterals($whereCondition, $iterationValue);

        return $expression;
    }

    /**
     * Generate path expression
     *
     * @param AclCondition $whereCondition
     * @param string       $field
     *
     * @return PathExpression
     */
    protected function getPathExpression(AclCondition $whereCondition, $field = 'entityField')
    {
        $entityField    = $this->getPropertyAccessor()->getValue($whereCondition, $field);
        $pathExpression = new PathExpression(
            self::EXPECTED_TYPE,
            $whereCondition->getEntityAlias(),
            $entityField
        );

        $pathExpression->type = $whereCondition->getPathExpressionType();

        return $pathExpression;
    }

    /**
     * Get array with literal from acl condition value array
     *
     * @param AclCondition $whereCondition
     * @param string       $iterationField = 'value'
     *
     * @return array
     */
    protected function getLiterals(AclCondition $whereCondition, $iterationField = 'value')
    {
        $literals   = [];
        $whereValue = $this->getPropertyAccessor()->getValue($whereCondition, $iterationField);

        if (!is_array($whereValue)) {
            $whereValue = [$whereValue];
            $this->getPropertyAccessor()->setValue($whereCondition, $iterationField, $whereValue);
        }

        foreach ($whereValue as $row) {
            $literals[] = new Literal(Literal::NUMERIC, $row);
        }

        return $literals;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (empty($this->propertyAccessor)) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @param array $aclConditionalFactors
     *
     * @return array
     */
    protected function addShareFactor(array $aclConditionalFactors)
    {
        $prShareCondition = new ConditionalPrimary();
        $prShareCondition->simpleConditionalExpression = $this->buildShareCondition($this->whereShareCondition);
        $orgCondition = new ConditionalPrimary();
        $ownershipCondition = new ConditionalTerm($aclConditionalFactors);
        $orgCondition->conditionalExpression = new ConditionalExpression([$ownershipCondition, $prShareCondition]);
        return [$orgCondition];
    }

    /**
     * @param $data
     *
     * @return ExistsExpression
     */
    protected function buildShareCondition($data)
    {
        $literal = new Literal(Literal::NUMERIC, $data['existsSubselect']['select']);
        $simpleSelectEx = new SimpleSelectExpression($literal);
        $simpleSelect = new SimpleSelectClause($simpleSelectEx, false);

        $rangeVarDeclaration = new RangeVariableDeclaration(
            $data['existsSubselect']['from']['schemaName'],
            $data['existsSubselect']['from']['alias'],
            true
        );
        $idVarDeclaration = new IdentificationVariableDeclaration($rangeVarDeclaration, null, []);

        $subSelectFrom = new SubselectFromClause([$idVarDeclaration]);
        $subSelect = new Subselect($simpleSelect, $subSelectFrom);

        $shareCondition = new ExistsExpression($subSelect);
        $shareCondition->not = $data['not'];

        $subSelect->whereClause = new WhereClause(
            new ConditionalTerm($this->buildSubselectWhereConditions($data['existsSubselect']['where']))
        );

        return $shareCondition;
    }

    /**
     * @param $data
     *
     * @return array
     */
    protected function buildSubselectWhereConditions($data)
    {
        //Make expr rootEntity.id = acl_entries.record_id
        $condition = $data[0];
        $leftExpression = new ArithmeticExpression();
        $aclCondition = new AclCondition(
            $condition['left']['entityAlias'],
            $condition['left']['field'],
            null,
            $condition['left']['typeOperand']
        );
        $leftExpression->simpleArithmeticExpression = $this->getPathExpression($aclCondition);
        $rightExpression = new ArithmeticExpression();
        $aclCondition = new AclCondition(
            $condition['right']['entityAlias'],
            $condition['right']['field'],
            null,
            $condition['right']['typeOperand']
        );
        $rightExpression->simpleArithmeticExpression = $this->getPathExpression($aclCondition);
        $resultCondition      = new ConditionalPrimary();
        $resultCondition->simpleConditionalExpression =
            new ComparisonExpression($leftExpression, $condition['operation'], $rightExpression);
        $conditionalFactors[] = $resultCondition;

        //Make expr acl_entries.security_identity_id IN (?) or acl_entries.security_identity_id = ?
        $condition = $data[1];
        $aclCondition = new AclCondition(
            $condition['left']['entityAlias'],
            $condition['left']['field'],
            $condition['right']['value'],
            $condition['left']['typeOperand']
        );

        if ($condition['operation'] === 'IN') {
            $condition = new ConditionalPrimary();
            $condition->simpleConditionalExpression = $this->getInExpression($aclCondition);
            $conditionalFactors[] = $condition;

        } else {
            $pathExpression = $this->getPathExpression($aclCondition);
            $conditionalFactors[] = $this->getLiteralComparisonExpression(
                $pathExpression,
                $condition['right']['value'],
                $condition['operation']
            );
        }

        //Make expr acl_entries.class_id = ?
        $condition = $data[2];
        $aclCondition = new AclCondition(
            $condition['left']['entityAlias'],
            $condition['left']['field'],
            $condition['right']['value'],
            $condition['left']['typeOperand']
        );
        $pathExpression = $this->getPathExpression($aclCondition);
        $conditionalFactors[] = $this->getLiteralComparisonExpression(
            $pathExpression,
            $condition['right']['value'],
            $condition['operation']
        );

        return $conditionalFactors;
    }

    /**
     * @param PathExpression $pathExpression
     * @param mixed          $value
     * @param string         $operation
     * @param int            $type
     *
     * @return ConditionalPrimary
     */
    protected function getLiteralComparisonExpression(
        PathExpression $pathExpression,
        $value,
        $operation,
        $type = Literal::NUMERIC
    ) {
        $resultCondition = new ConditionalPrimary();
        $leftExpression = new ArithmeticExpression();
        $leftExpression->simpleArithmeticExpression = $pathExpression;
        $rightExpression  = new ArithmeticExpression();
        $rightExpression->simpleArithmeticExpression = new Literal($type, $value);
        $resultCondition->simpleConditionalExpression =
            new ComparisonExpression($leftExpression, $operation, $rightExpression);

        return $resultCondition;
    }
}
