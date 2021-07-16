<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Oro\Bundle\SecurityBundle\AccessRule\AclAccessRule;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;

/**
 * Walker that apply access rule conditions to DBAL query.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AccessRuleWalker extends TreeWalkerAdapter
{
    public const CONTEXT        = 'oro_access_rule.context';
    public const ORM_RULES_TYPE = 'ORM';

    /** @var EntityManagerInterface */
    private $em;

    /** @var QueryComponentCollection */
    private $queryComponents;

    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $query = $this->_getQuery();

        $this->em = $query->getEntityManager();
        $this->queryComponents = $this->collectQueryComponents();
        try {
            $this->processSelectStatement($AST, $query->getHint(self::CONTEXT));
            $this->applyNewQueryComponents($this->queryComponents);
        } finally {
            $this->em = null;
            $this->queryComponents = null;
        }
    }

    /**
     * Process select or subselect expression.
     *
     * @param AST\SelectStatement|AST\Subselect $select
     * @param AccessRuleWalkerContext           $context
     */
    private function processSelectStatement(AST\Node $select, AccessRuleWalkerContext $context): void
    {
        if ($context->getOption(AclHelper::CHECK_ROOT_ENTITY, true)) {
            $this->processSelect($select, $context);
        }
        if ($context->getOption(AclHelper::CHECK_RELATIONS, true)) {
            $this->processJoins($select, $context);
        }

        if ($select->whereClause) {
            $this->findAndProcessSubselects(
                $select->whereClause->conditionalExpression,
                $this->getSubselectContext($context)
            );
        }
        $this->processSubselectsInJoins($select, $this->getSubselectContext($context));
    }

    /**
     * Process subselext expressions in join expressions.
     *
     * @param AST\SelectStatement|AST\Subselect $select
     * @param AccessRuleWalkerContext           $context
     */
    private function processSubselectsInJoins(AST\Node $select, AccessRuleWalkerContext $context): void
    {
        $fromClause = $select instanceof AST\SelectStatement ? $select->fromClause : $select->subselectFromClause;
        foreach ($fromClause->identificationVariableDeclarations as $fromKey => $identificationVariableDeclaration) {
            if (!empty($identificationVariableDeclaration->joins)) {
                $i = 0;
                /** @var AST\Join $join */
                while ($i < \count($identificationVariableDeclaration->joins)) {
                    $keys = \array_keys($identificationVariableDeclaration->joins);
                    $join = $identificationVariableDeclaration->joins[$keys[$i]];

                    if (isset($join->conditionalExpression)) {
                        $this->findAndProcessSubselects($join->conditionalExpression, $context);
                    }
                    $i++;
                }
            }
        }
    }

    /**
     * Finds and processes subselects in given expression.
     */
    private function findAndProcessSubselects(AST\Node $conditionalExpression, AccessRuleWalkerContext $context): void
    {
        if (isset($conditionalExpression->conditionalPrimary)) {
            $conditionalExpression = $conditionalExpression->conditionalPrimary;
        }

        if ($conditionalExpression instanceof AST\ConditionalPrimary) {
            $expression = $conditionalExpression->simpleConditionalExpression;
            if ($expression && isset($expression->subselect)
                && $expression->subselect instanceof AST\Subselect
            ) {
                $this->processSelectStatement($expression->subselect, $context);
            } elseif (isset($conditionalExpression->conditionalExpression)) {
                $this->findAndProcessSubselects($conditionalExpression->conditionalExpression, $context);
            }
        } else {
            if (isset($conditionalExpression->conditionalFactors)) {
                $factors = $conditionalExpression->conditionalFactors;
            } else {
                $factors = $conditionalExpression->conditionalTerms;
            }
            foreach ($factors as $expression) {
                $this->findAndProcessSubselects($expression, $context);
            }
        }
    }

    private function processSelect(AST\Node $AST, AccessRuleWalkerContext $context): void
    {
        $fromClause = $AST instanceof AST\SelectStatement ? $AST->fromClause : $AST->subselectFromClause;
        foreach ($fromClause->identificationVariableDeclarations as $identificationVariableDeclaration) {
            $rangeVariableDeclaration = $identificationVariableDeclaration->rangeVariableDeclaration;

            $entityName = $rangeVariableDeclaration->abstractSchemaName;
            $alias = $rangeVariableDeclaration->aliasIdentificationVariable;

            $criteria = $this->getEntityAccessRulesCriteria($entityName, $context, $alias, true);

            $criteriaExpression = $criteria->getExpression();
            if ($criteriaExpression) {
                $visitor = new AstVisitor($this->em, $alias, $this->queryComponents);
                $whereExpression = null === $AST->whereClause ? null : $AST->whereClause->conditionalExpression;
                $conditionalExpression = $this->mergeExpressions(
                    $visitor->dispatch($criteria->getExpression()),
                    $whereExpression
                );
                if (null === $AST->whereClause) {
                    $AST->whereClause = new AST\WhereClause($conditionalExpression);
                } else {
                    $AST->whereClause->conditionalExpression = $conditionalExpression;
                }
            }
        }
    }

    /**
     * @param AST\SelectStatement|AST\Subselect $select
     * @param AccessRuleWalkerContext           $context
     */
    private function processJoins($select, AccessRuleWalkerContext $context): void
    {
        $fromClause = $select instanceof AST\SelectStatement ? $select->fromClause : $select->subselectFromClause;
        foreach ($fromClause->identificationVariableDeclarations as $fromKey => $identificationVariableDeclaration) {
            if (!empty($identificationVariableDeclaration->joins)) {
                $i = 0;
                /** @var AST\Join $join */
                while ($i < \count($identificationVariableDeclaration->joins)) {
                    $keys = \array_keys($identificationVariableDeclaration->joins);
                    $join = $identificationVariableDeclaration->joins[$keys[$i]];

                    $joinAlias = $join->joinAssociationDeclaration->aliasIdentificationVariable;

                    $parentClass = null;
                    $parentField = null;

                    //check if join in format "join some_table on (some_table.id = parent_table.id)"
                    if ($join->joinAssociationDeclaration instanceof AST\RangeVariableDeclaration) {
                        $joinEntity = $join->joinAssociationDeclaration->abstractSchemaName;
                    } else {
                        $joinQueryComponent = $this->queryComponents->get($joinAlias);
                        $joinEntityMetadata = $joinQueryComponent->getMetadata();
                        $joinEntity = $joinEntityMetadata->name;

                        $relationData = $joinQueryComponent->getRelation();
                        $parentClass = $relationData['sourceEntity'];
                        $parentField = $relationData['fieldName'];
                    }

                    $criteria = $this->getEntityAccessRulesCriteria(
                        $joinEntity,
                        $context,
                        $joinAlias,
                        false,
                        [
                            AclAccessRule::PARENT_CLASS => $parentClass,
                            AclAccessRule::PARENT_FIELD => $parentField
                        ]
                    );

                    $criteriaExpression = $criteria->getExpression();
                    if ($criteriaExpression) {
                        $visitor = new AstVisitor($this->em, $joinAlias, $this->queryComponents);
                        $join->conditionalExpression = $this->mergeExpressions(
                            $visitor->dispatch($criteria->getExpression()),
                            $join->conditionalExpression
                        );
                    }
                    $i++;
                }
            }
        }
    }

    /**
     * Creates the context for subselects.
     */
    private function getSubselectContext(AccessRuleWalkerContext $context): AccessRuleWalkerContext
    {
        $subselectContext = clone $context;
        $subselectContext->removeOption(AclHelper::CHECK_ROOT_ENTITY);
        $subselectContext->removeOption(AclHelper::CHECK_RELATIONS);

        return $subselectContext;
    }

    /**
     * Creates new criteria, process access rules with new criteria and returns it.
     */
    private function getEntityAccessRulesCriteria(
        string $entityClass,
        AccessRuleWalkerContext $context,
        string $alias,
        bool $isRoot,
        array $options = []
    ): Criteria {
        $criteria = new Criteria(self::ORM_RULES_TYPE, $entityClass, $alias, $context->getPermission(), $isRoot);
        foreach ($context->getOptions() as $optionName => $optionValue) {
            $criteria->setOption($optionName, $optionValue);
        }
        foreach ($options as $optionName => $optionValue) {
            $criteria->setOption($optionName, $optionValue);
        }

        $context->getAccessRuleExecutor()->process($criteria);

        return $criteria;
    }

    /**
     * @param AST\ConditionalTerm|AST\ConditionalPrimary               $ruleExpression
     * @param AST\ConditionalTerm|AST\ConditionalPrimary|AST\Node|null $queryExpression
     *
     * @return AST\ConditionalTerm|AST\ConditionalPrimary
     */
    private function mergeExpressions($ruleExpression, $queryExpression = null)
    {
        if (null === $queryExpression) {
            return $ruleExpression;
        }

        return new AST\ConditionalTerm(\array_merge(
            $this->getConditionalFactors($queryExpression),
            $this->getConditionalFactors($ruleExpression)
        ));
    }

    /**
     * @param AST\Node $queryExpression
     *
     * @return AST\ConditionalFactor[]
     */
    private function getConditionalFactors(AST\Node $queryExpression): array
    {
        // in case if $queryExpression is some kind if comparison expression
        // - wrap it with ConditionalPrimary expression
        if (!($queryExpression instanceof AST\ConditionalPrimary)
            && !($queryExpression instanceof AST\ConditionalTerm)
        ) {
            $conditionalExpressionPrimary = new AST\ConditionalPrimary();
            $conditionalExpressionPrimary->conditionalExpression = $queryExpression;
            $queryExpression = $conditionalExpressionPrimary;
        }

        return $queryExpression instanceof AST\ConditionalPrimary
            ? [$queryExpression]
            : $queryExpression->conditionalFactors;
    }

    /**
     * Collects existing array query components to array of objects.
     */
    private function collectQueryComponents(): QueryComponentCollection
    {
        $result = new QueryComponentCollection();
        $components = $this->getQueryComponents();
        foreach ($components as $alias => $componentArray) {
            $component = QueryComponent::fromArray($componentArray);
            if (null !== $component) {
                $result->add($alias, $component);
            }
        }

        return $result;
    }

    /**
     * Adds new query components to existing query components.
     */
    private function applyNewQueryComponents(QueryComponentCollection $queryComponents): void
    {
        /** @var QueryComponent $queryComponent */
        $components = $queryComponents->toArray();
        foreach ($components as $alias => $queryComponent) {
            if (!\array_key_exists($alias, $this->getQueryComponents())) {
                $this->setQueryComponent($alias, $queryComponent->toArray());
            }
        }
    }
}
