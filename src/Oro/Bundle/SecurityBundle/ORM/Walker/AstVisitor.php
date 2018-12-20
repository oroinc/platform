<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use Doctrine\ORM\Query\AST\ConditionalExpression;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ConditionalTerm;
use Doctrine\ORM\Query\AST\ExistsExpression;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\InExpression;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\NullComparisonExpression;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SimpleSelectClause;
use Doctrine\ORM\Query\AST\SimpleSelectExpression;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\AST\SubselectFromClause;
use Doctrine\ORM\Query\AST\WhereClause;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\AccessDenied;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Exists;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\NullComparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Subquery;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Value;
use Oro\Bundle\SecurityBundle\AccessRule\Visitor;

/**
 * Converts access rule expressions to DBAL AST conditions.
 */
class AstVisitor extends Visitor
{
    /** @var string */
    private $alias;

    /** @var QueryComponent[] */
    private $queryComponents;

    /** @var ObjectManager */
    private $em;

    /**
     * @param ObjectManager $em
     */
    public function setObjectManager(ObjectManager $em): void
    {
        $this->em = $em;
    }

    /**
     * @return QueryComponent[]
     */
    public function getQueryComponents(): array
    {
        return $this->queryComponents;
    }

    /**
     * @param string $alias
     */
    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }

    /**
     * @param QueryComponent[] $queryComponents
     */
    public function setQueryComponents(array $queryComponents): void
    {
        $this->queryComponents = $queryComponents;
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparison(Comparison $comparison): ConditionalPrimary
    {
        $leftExpression = new ArithmeticExpression();
        $leftExpression->simpleArithmeticExpression = $comparison->getLeftOperand()->visit($this);

        $operator = $comparison->getOperator();

        switch ($operator) {
            case Comparison::IN:
                $resultExpression = new InExpression($leftExpression);
                $resultExpression->literals = $comparison->getRightOperand()->visit($this);
                break;
            case Comparison::NIN:
                $resultExpression = new InExpression($leftExpression);
                $resultExpression->not = true;
                $resultExpression->literals = $comparison->getRightOperand()->visit($this);
                break;
            default:
                $rightExpression = new ArithmeticExpression();
                $rightExpression->simpleArithmeticExpression = $comparison->getRightOperand()->visit($this);

                $resultExpression = new ComparisonExpression(
                    $leftExpression,
                    $comparison->getOperator(),
                    $rightExpression
                );
        }

        $primaryConditional = new ConditionalPrimary();
        $primaryConditional->simpleConditionalExpression = $resultExpression;

        return $primaryConditional;
    }

    /**
     * {@inheritdoc}
     */
    public function walkValue(Value $value)
    {
        if (is_array($value->getValue())) {
            $literalsArray = [];
            foreach ($value->getValue() as $arrayItemValue) {
                $literalsArray[] = $this->getValueLiteral($arrayItemValue);
            }

            return $literalsArray;
        }

        return $this->getValueLiteral($value->getValue());
    }

    /**
     * {@inheritdoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $factors = [];
        foreach ($expr->getExpressionList() as $expression) {
            $factor = $expression->visit($this);
            if ($factor instanceof ConditionalExpression || $factor instanceof ConditionalTerm) {
                $conditionalPrimary = new ConditionalPrimary();
                $conditionalPrimary->conditionalExpression = $factor;
                $factor = $conditionalPrimary;
            }
            $factors[] = $factor;
        }

        if ($expr->getType() === CompositeExpression::TYPE_AND) {
            return new ConditionalTerm($factors);
        }

        $terms = [];
        foreach ($factors as $factor) {
            $terms[] = new ConditionalTerm([$factor]);
        }

        return new ConditionalExpression($terms);
    }

    /**
     * {@inheritdoc}
     */
    public function walkAccessDenied(AccessDenied $accessDenied): ConditionalPrimary
    {
        $leftExpression = new ArithmeticExpression();
        $leftExpression->simpleArithmeticExpression  = new Literal(Literal::NUMERIC, 1);

        $rightExpression = new ArithmeticExpression();
        $rightExpression->simpleArithmeticExpression = new Literal(Literal::NUMERIC, 0);

        $expression = new ComparisonExpression($leftExpression, '=', $rightExpression);

        $primaryConditional = new ConditionalPrimary();
        $primaryConditional->simpleConditionalExpression = $expression;

        return $primaryConditional;
    }

    /**
     * {@inheritdoc}
     */
    public function walkPath(Path $path): PathExpression
    {
        $alias = $path->getAlias() ?: $this->alias;
        $field = $path->getField();

        /** @var ClassMetadata $metadata */
        $metadata = $this->queryComponents[$alias]->getMetadata();
        if ($metadata->isSingleValuedAssociation($field)) {
            $type = PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;
        } elseif ($metadata->isCollectionValuedAssociation($field)) {
            $type = PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION;
        } else {
            $type = PathExpression::TYPE_STATE_FIELD;
        }

        $expression = new PathExpression(
            PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            $alias,
            $field
        );
        $expression->type = $type;

        return $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubquery(Subquery $subquery): Subselect
    {
        $from = $subquery->getFrom();
        $alias = $subquery->getAlias();
        $subqueryCriteria = $subquery->getCriteria();

        $literal = new Literal(Literal::NUMERIC, 1);
        $simpleSelectEx = new SimpleSelectExpression($literal);
        $simpleSelect = new SimpleSelectClause($simpleSelectEx, false);
        $rangeVarDeclaration = new RangeVariableDeclaration($from, $alias, true);
        $idVarDeclaration = new IdentificationVariableDeclaration($rangeVarDeclaration, null, []);
        $subSelectFrom = new SubselectFromClause([$idVarDeclaration]);

        $queryComponent = new QueryComponent();
        $queryComponent->setMetadata($this->em->getClassMetadata($from));

        $this->queryComponents[$alias] = $queryComponent;

        $visitor = new self();
        $visitor->setAlias($alias);
        $visitor->setQueryComponents($this->queryComponents);

        $whereCondition = $visitor->dispatch($subqueryCriteria->getExpression());
        $this->queryComponents = $visitor->getQueryComponents();

        $subSelect = new Subselect($simpleSelect, $subSelectFrom);
        $subSelect->whereClause = new WhereClause($whereCondition);

        return $subSelect;
    }

    /**
     * {@inheritdoc}
     */
    public function walkExists(Exists $existsExpr): ConditionalPrimary
    {
        $exist = new ExistsExpression($existsExpr->getExpression()->visit($this));
        $exist->not = $existsExpr->isNot();

        $primaryConditional = new ConditionalPrimary();
        $primaryConditional->simpleConditionalExpression = $exist;

        return $primaryConditional;
    }

    /**
     * {@inheritdoc}
     */
    public function walkNullComparison(NullComparison $comparison): ConditionalPrimary
    {
        $expression = new NullComparisonExpression($comparison->getExpression()->visit($this));
        $expression->not = $comparison->isNot();

        $primaryConditional = new ConditionalPrimary();
        $primaryConditional->simpleConditionalExpression = $expression;

        return $primaryConditional;
    }

    /**
     * @param mixed $value
     *
     * @return Literal
     */
    protected function getValueLiteral($value): Literal
    {
        if (is_numeric($value)) {
            $type = Literal::NUMERIC;
        } elseif (is_bool($value)) {
            $type = Literal::BOOLEAN;
            $value = $value ? 'true' : 'false';
        } else {
            $type = Literal::STRING;
        }

        return new Literal($type, $value);
    }
}
