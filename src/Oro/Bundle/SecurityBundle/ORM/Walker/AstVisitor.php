<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\AST;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr;
use Oro\Bundle\SecurityBundle\AccessRule\Visitor;

/**
 * Converts access rule expressions to DBAL AST conditions.
 */
class AstVisitor extends Visitor
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var string */
    private $alias;

    /** @var QueryComponentCollection */
    private $queryComponents;

    public function __construct(
        EntityManagerInterface $em,
        string $alias,
        QueryComponentCollection $queryComponents
    ) {
        $this->em = $em;
        $this->alias = $alias;
        $this->queryComponents = $queryComponents;
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparison(Expr\Comparison $comparison): AST\ConditionalPrimary
    {
        $leftExpression = new AST\ArithmeticExpression();
        $leftExpression->simpleArithmeticExpression = $comparison->getLeftOperand()->visit($this);

        $operator = $comparison->getOperator();

        switch ($operator) {
            case Expr\Comparison::IN:
                $resultExpression = new AST\InExpression($leftExpression);
                $resultExpression->literals = $comparison->getRightOperand()->visit($this);
                break;
            case Expr\Comparison::NIN:
                $resultExpression = new AST\InExpression($leftExpression);
                $resultExpression->not = true;
                $resultExpression->literals = $comparison->getRightOperand()->visit($this);
                break;
            default:
                $rightExpression = new AST\ArithmeticExpression();
                $rightExpression->simpleArithmeticExpression = $comparison->getRightOperand()->visit($this);

                $resultExpression = new AST\ComparisonExpression(
                    $leftExpression,
                    $comparison->getOperator(),
                    $rightExpression
                );
        }

        $primaryConditional = new AST\ConditionalPrimary();
        $primaryConditional->simpleConditionalExpression = $resultExpression;

        return $primaryConditional;
    }

    /**
     * {@inheritdoc}
     */
    public function walkValue(Expr\Value $value)
    {
        // unfortunately we have to use literals
        // because it is not possible to add query parameters in a query walker;
        // walkers are executed only if a query is not cached in the query cache yet,
        // as result it is not possible to prepare parameters for a cached query
        if (\is_array($value->getValue())) {
            $literalValues = [];
            foreach ($value->getValue() as $arrayItemValue) {
                $literalValues[] = $this->getValueLiteral($arrayItemValue);
            }

            return $literalValues;
        }

        return $this->getValueLiteral($value->getValue());
    }

    /**
     * {@inheritdoc}
     */
    public function walkCompositeExpression(Expr\CompositeExpression $expr)
    {
        $factors = [];
        foreach ($expr->getExpressionList() as $expression) {
            $factor = $expression->visit($this);
            if ($factor instanceof AST\ConditionalExpression || $factor instanceof AST\ConditionalTerm) {
                $conditionalPrimary = new AST\ConditionalPrimary();
                $conditionalPrimary->conditionalExpression = $factor;
                $factor = $conditionalPrimary;
            }
            $factors[] = $factor;
        }

        if ($expr->getType() === Expr\CompositeExpression::TYPE_AND) {
            return new AST\ConditionalTerm($factors);
        }

        $terms = [];
        foreach ($factors as $factor) {
            $terms[] = new AST\ConditionalTerm([$factor]);
        }

        return new AST\ConditionalExpression($terms);
    }

    /**
     * {@inheritdoc}
     */
    public function walkAccessDenied(Expr\AccessDenied $accessDenied): AST\ConditionalPrimary
    {
        $leftExpression = new AST\ArithmeticExpression();
        $leftExpression->simpleArithmeticExpression = new AST\Literal(AST\Literal::NUMERIC, 1);

        $rightExpression = new AST\ArithmeticExpression();
        $rightExpression->simpleArithmeticExpression = new AST\Literal(AST\Literal::NUMERIC, 0);

        $expression = new AST\ComparisonExpression($leftExpression, '=', $rightExpression);

        $primaryConditional = new AST\ConditionalPrimary();
        $primaryConditional->simpleConditionalExpression = $expression;

        return $primaryConditional;
    }

    /**
     * {@inheritdoc}
     */
    public function walkAssociation(Expr\Association $association)
    {
        $alias = $this->alias;
        $associationName = $association->getAssociationName();

        /** @var ClassMetadataInfo $sourceMetadata */
        $sourceMetadata = $this->queryComponents->get($alias)->getMetadata();
        if (empty($sourceMetadata->associationMappings[$associationName])) {
            throw new \RuntimeException(\sprintf(
                'Parameter of Association expression should be the name of existing association'
                . ' for alias \'%s\'. Given name: \'%s\'.',
                $alias,
                $associationName
            ));
        }
        $associationMapping = $sourceMetadata->associationMappings[$associationName];
        if (!($associationMapping['type'] & ClassMetadataInfo::TO_ONE)) {
            throw new \RuntimeException(\sprintf(
                'Parameter of Association expression should be to-one association. Given name: \'%s\'.',
                $associationName
            ));
        }
        $targetEntityAlias = \sprintf('_%s__%s_', $alias, $associationName);
        $targetEntityClass = $associationMapping['targetEntity'];

        /** @var ClassMetadataInfo $targetEntityMetadata */
        $targetMetadata = $this->em->getClassMetadata($targetEntityClass);

        $queryComponentRelation = $associationMapping;
        if (!$associationMapping['isOwningSide']) {
            $mappedBy = $associationMapping['mappedBy'];
            $queryComponentRelation = $targetMetadata->associationMappings[$mappedBy];
            $leftPathExpression = new Expr\Path($targetMetadata->getSingleIdentifierFieldName(), $alias);
            $rightPathExpression = new Expr\Path($mappedBy, $targetEntityAlias);
        } else {
            $leftPathExpression = new Expr\Path($targetMetadata->getSingleIdentifierFieldName(), $targetEntityAlias);
            $rightPathExpression = new Expr\Path($associationName, $alias);
        }

        $this->queryComponents->add(
            $targetEntityAlias,
            new QueryComponent($targetMetadata, $queryComponentRelation)
        );

        $subqueryCriteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, $targetEntityClass, $targetEntityAlias);
        $subqueryCriteria->andExpression(
            new Expr\Comparison($leftPathExpression, Expr\Comparison::EQ, $rightPathExpression)
        );

        $existExpression = new Expr\Exists(
            new Expr\Subquery(
                $targetEntityClass,
                $targetEntityAlias,
                $subqueryCriteria
            )
        );

        return $existExpression->visit($this);
    }

    /**
     * {@inheritdoc}
     */
    public function walkPath(Expr\Path $path): AST\PathExpression
    {
        $alias = $path->getAlias() ?: $this->alias;
        $field = $path->getField();

        /** @var ClassMetadata $metadata */
        $metadata = $this->queryComponents->get($alias)->getMetadata();
        if ($metadata->isSingleValuedAssociation($field)) {
            $type = AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;
        } elseif ($metadata->isCollectionValuedAssociation($field)) {
            $type = AST\PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION;
        } else {
            $type = AST\PathExpression::TYPE_STATE_FIELD;
        }

        $expression = new AST\PathExpression(
            AST\PathExpression::TYPE_STATE_FIELD | AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            $alias,
            $field
        );
        $expression->type = $type;

        return $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubquery(Expr\Subquery $subquery): AST\Subselect
    {
        $from = $subquery->getFrom();
        $alias = $subquery->getAlias();
        $subqueryCriteria = $subquery->getCriteria();

        $literal = new AST\Literal(AST\Literal::NUMERIC, 1);
        $simpleSelectEx = new AST\SimpleSelectExpression($literal);
        $simpleSelect = new AST\SimpleSelectClause($simpleSelectEx, false);
        $rangeVarDeclaration = new AST\RangeVariableDeclaration($from, $alias, true);
        $idVarDeclaration = new AST\IdentificationVariableDeclaration($rangeVarDeclaration, null, []);
        $subSelectFrom = new AST\SubselectFromClause([$idVarDeclaration]);

        if (!$this->queryComponents->has($alias)) {
            $this->queryComponents->add(
                $alias,
                new QueryComponent($this->em->getClassMetadata($from))
            );
        }

        $visitor = new self($this->em, $alias, $this->queryComponents);

        $whereCondition = $visitor->dispatch($subqueryCriteria->getExpression());

        $subSelect = new AST\Subselect($simpleSelect, $subSelectFrom);
        $subSelect->whereClause = new AST\WhereClause($whereCondition);

        return $subSelect;
    }

    /**
     * {@inheritdoc}
     */
    public function walkExists(Expr\Exists $existsExpr): AST\ConditionalPrimary
    {
        $exist = new AST\ExistsExpression($existsExpr->getExpression()->visit($this));
        $exist->not = $existsExpr->isNot();

        $primaryConditional = new AST\ConditionalPrimary();
        $primaryConditional->simpleConditionalExpression = $exist;

        return $primaryConditional;
    }

    /**
     * {@inheritdoc}
     */
    public function walkNullComparison(Expr\NullComparison $comparison): AST\ConditionalPrimary
    {
        $expression = new AST\NullComparisonExpression($comparison->getExpression()->visit($this));
        $expression->not = $comparison->isNot();

        $primaryConditional = new AST\ConditionalPrimary();
        $primaryConditional->simpleConditionalExpression = $expression;

        return $primaryConditional;
    }

    /**
     * @param mixed $value
     *
     * @return AST\Literal
     */
    protected function getValueLiteral($value): AST\Literal
    {
        if (\is_numeric($value)) {
            $type = AST\Literal::NUMERIC;
        } elseif (\is_bool($value)) {
            $type = AST\Literal::BOOLEAN;
            $value = $value ? 'true' : 'false';
        } else {
            $type = AST\Literal::STRING;
        }

        return new AST\Literal($type, $value);
    }
}
