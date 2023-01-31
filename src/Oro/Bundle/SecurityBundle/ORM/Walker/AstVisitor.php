<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\AST;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr;
use Oro\Bundle\SecurityBundle\AccessRule\Visitor;

/**
 * Converts access rule expressions to DBAL AST conditions.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AstVisitor extends Visitor
{
    private const LIKE = 'LIKE';

    private EntityManagerInterface $em;
    private string $alias;
    private QueryComponentCollection $queryComponents;

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
    public function walkComparison(Expr\Comparison $comparison): mixed
    {
        $operator = $comparison->getOperator();
        switch ($operator) {
            case Expr\Comparison::IN:
                $resultExpression = new AST\InExpression($this->walkOperand($comparison->getLeftOperand()));
                $resultExpression->literals = $comparison->getRightOperand()->visit($this);
                break;
            case Expr\Comparison::NIN:
                $resultExpression = new AST\InExpression($this->walkOperand($comparison->getLeftOperand()));
                $resultExpression->not = true;
                $resultExpression->literals = $comparison->getRightOperand()->visit($this);
                break;
            case Expr\Comparison::CONTAINS:
                $resultExpression = $this->walkContainsComparison($comparison);
                break;
            default:
                $resultExpression = new AST\ComparisonExpression(
                    $this->walkOperand($comparison->getLeftOperand()),
                    $comparison->getOperator(),
                    $this->walkOperand($comparison->getRightOperand())
                );
        }

        $primaryConditional = new AST\ConditionalPrimary();
        $primaryConditional->simpleConditionalExpression = $resultExpression;

        return $primaryConditional;
    }

    /**
     * {@inheritdoc}
     */
    public function walkValue(Expr\Value $value): mixed
    {
        // unfortunately we have to use literals
        // because it is not possible to add query parameters in a query walker;
        // walkers are executed only if a query is not cached in the query cache yet,
        // as result it is not possible to prepare parameters for a cached query
        if (\is_array($value->getValue())) {
            $literalValues = [];
            foreach ($value->getValue() as $arrayItemValue) {
                $arithmeticExpression = new AST\ArithmeticExpression();
                $arithmeticExpression->simpleArithmeticExpression = new AST\SimpleArithmeticExpression(
                    [$this->getValueLiteral($arrayItemValue)]
                );
                $literalValues[] = $arithmeticExpression;
            }

            return $literalValues;
        }

        return $this->getValueLiteral($value->getValue());
    }

    /**
     * {@inheritdoc}
     */
    public function walkCompositeExpression(Expr\CompositeExpression $expr): mixed
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
    public function walkAccessDenied(Expr\AccessDenied $accessDenied): mixed
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
    public function walkAssociation(Expr\Association $association): mixed
    {
        $alias = $this->alias;
        $associationName = $association->getAssociationName();

        $sourceMetadata = $this->getMetadata($alias);
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
        if ($associationMapping['isOwningSide']) {
            $leftPathExpression = new Expr\Path($targetMetadata->getSingleIdentifierFieldName(), $targetEntityAlias);
            $rightPathExpression = new Expr\Path($associationName, $alias);
        } else {
            $mappedBy = $associationMapping['mappedBy'];
            $queryComponentRelation = $targetMetadata->associationMappings[$mappedBy];
            $leftPathExpression = new Expr\Path($targetMetadata->getSingleIdentifierFieldName(), $alias);
            $rightPathExpression = new Expr\Path($mappedBy, $targetEntityAlias);
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
    public function walkPath(Expr\Path $path): mixed
    {
        $alias = $path->getAlias() ?: $this->alias;
        $field = $path->getField();

        $metadata = $this->getMetadata($alias);
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
    public function walkSubquery(Expr\Subquery $subquery): mixed
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
    public function walkExists(Expr\Exists $existsExpr): mixed
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
    public function walkNullComparison(Expr\NullComparison $comparison): mixed
    {
        $expression = new AST\NullComparisonExpression($comparison->getExpression()->visit($this));
        $expression->not = $comparison->isNot();

        $primaryConditional = new AST\ConditionalPrimary();
        $primaryConditional->simpleConditionalExpression = $expression;

        return $primaryConditional;
    }

    private function walkOperand(Expr\ExpressionInterface $operand): AST\ArithmeticExpression
    {
        $expression = new AST\ArithmeticExpression();
        $expression->simpleArithmeticExpression = $operand->visit($this);

        return $expression;
    }

    private function walkContainsComparison(Expr\Comparison $comparison): AST\Node
    {
        $leftOperand = $comparison->getLeftOperand();
        if (!$leftOperand instanceof Expr\Path) {
            throw new \RuntimeException('The left operand for CONTAINS comparison must be a path.');
        }
        $rightOperand = $comparison->getRightOperand();
        if (!$rightOperand instanceof Expr\Value) {
            throw new \RuntimeException('The left operand for CONTAINS comparison must be a value.');
        }

        $fieldType = $this->getMetadata($leftOperand->getAlias() ?: $this->alias)
            ->getTypeOfField($leftOperand->getField());

        if (Types::JSON === $fieldType) {
            return $this->walkJsonArrayContainsComparison($leftOperand, $rightOperand);
        }

        if (!\is_string($rightOperand->getValue())) {
            throw new \RuntimeException('The right operand for string CONTAINS comparison must be a string.');
        }

        return new AST\ComparisonExpression(
            $this->walkOperand($leftOperand),
            self::LIKE,
            $this->getValueLiteral('%' . $rightOperand->getValue() . '%')
        );
    }

    private function walkJsonArrayContainsComparison(
        Expr\Path $leftOperand,
        Expr\Value $rightOperand
    ): AST\Node {
        $value = $rightOperand->getValue();
        if (\is_array($value) && count($value) === 1) {
            $value = reset($value);
        }

        $isPostgres = $this->em->getConnection()->getDatabasePlatform() instanceof PostgreSQL94Platform;

        if (\is_string($value)) {
            if ($isPostgres) {
                return $this->getPostgreSqlJsonbContainsExpression($leftOperand, $value);
            }

            return new AST\ComparisonExpression(
                $this->walkOperand($leftOperand),
                self::LIKE,
                $this->getValueLiteral('%"' . $value . '"%')
            );
        }

        if (\is_array($value) && !empty($value)) {
            $field = $this->walkOperand($leftOperand);
            $expressions = [];
            foreach ($value as $val) {
                $expression = new AST\ConditionalPrimary();
                $expression->simpleConditionalExpression = $isPostgres
                    ? $this->getPostgreSqlJsonbContainsExpression($field, $val)
                    : new AST\ComparisonExpression(
                        $field,
                        self::LIKE,
                        $this->getValueLiteral('%"' . $val . '"%')
                    );
                $expressions[] = $expression;
            }

            return new AST\ParenthesisExpression(new AST\ConditionalExpression($expressions));
        }

        throw new \RuntimeException(
            'The right operand for JSON array CONTAINS comparison must be a string or not empty array.'
        );
    }

    private function getPostgreSqlJsonbContainsExpression(
        Expr\Path|AST\ArithmeticExpression $leftOperand,
        mixed $value
    ) : AST\Node {
        if (!\is_array($value)) {
            $value = [$value];
        }

        return new AST\ComparisonExpression(
            $this->walkOperand($leftOperand),
            '@>',
            // Here used Numeric type to avoid the parameter escaping with quotes
            new Ast\Literal(Ast\Literal::NUMERIC, sprintf("'%s'::jsonb", json_encode($value, JSON_THROW_ON_ERROR)))
        );
    }

    private function getValueLiteral(mixed $value): AST\Literal
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

    private function getMetadata(string $alias): ClassMetadataInfo
    {
        return $this->queryComponents->get($alias)->getMetadata();
    }
}
