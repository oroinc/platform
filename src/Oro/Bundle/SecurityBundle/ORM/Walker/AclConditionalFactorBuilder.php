<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\InExpression;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\PathExpression;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclCondition;

/**
 * Class to build ACL conditions for AclWalker. Point to extend conditions.
 */
class AclConditionalFactorBuilder
{
    const EXPECTED_TYPE = 12;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * Params $query and $options is points to extend functionality
     *
     * @param array $aclConditionalFactors
     * @param mixed $condition
     * @param AbstractQuery $query
     * @param array|null $options
     *
     * @return array
     */
    public function addJoinAclConditionalFactor(
        array $aclConditionalFactors,
        $condition,
        AbstractQuery $query,
        array $options = null
    ) {
        if ($condition instanceof AclCondition) {
            $this->addConditionFactors($aclConditionalFactors, $condition);
        }

        return $aclConditionalFactors;
    }

    /**
     * Params $query and $options is points to extend functionality
     *
     * @param array $aclConditionalFactors
     * @param array $conditions
     * @param AbstractQuery $query
     * @param array|null $options
     *
     * @return array
     */
    public function addWhereAclConditionalFactors(
        array $aclConditionalFactors,
        array $conditions,
        AbstractQuery $query,
        array $options = null
    ) {
        foreach ($conditions as $condition) {
            if ($condition instanceof AclCondition) {
                $this->addConditionFactors($aclConditionalFactors, $condition);
            }
        }

        return $aclConditionalFactors;
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

        if ($condition->getValue() === null && $condition->getEntityField() === null) {
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
}
