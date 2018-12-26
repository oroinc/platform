<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\ConditionalFactor;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ConditionalTerm;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\AST\WhereClause;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Oro\Bundle\SecurityBundle\AccessRule\AclAccessRule;
use Oro\Bundle\SecurityBundle\AccessRule\ChainAccessRule;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Walker that apply access rule conditions to DBAL query.
 */
class AccessRuleWalker extends TreeWalkerAdapter
{
    public const CONTEXT = 'oro_access_rule.context';
    public const ORM_RULES_TYPE = 'ORM';

    /** @var ChainAccessRule */
    private $chainAccessRule;

    /** @var QueryComponent[] */
    private $queryComponents;

    /**
     * @inheritdoc
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        /** @var Query $query */
        $query = $this->_getQuery();
        $em = $query->getEntityManager();

        $this->collectQueryComponents();

        $context = $query->getHint(self::CONTEXT);

        if ($context->getOption(AclHelper::CHECK_ROOT_ENTITY, true)) {
            $this->processSelect($AST, $context, $em);
        }
        if ($context->getOption(AclHelper::CHECK_RELATIONS, true)) {
            $this->processJoins($AST, $context, $em);
        }

        if ($AST->whereClause) {
            $this->processSubselects($AST, $this->getSubselectContext($context), $em);
        }

        $this->applyNewQueryComponents();
    }

    /**
     * @param SelectStatement $AST
     * @param AccessRuleWalkerContext $context
     * @param ObjectManager $em
     */
    private function processSubselects(
        SelectStatement $AST,
        AccessRuleWalkerContext $context,
        ObjectManager $em
    ): void {
        $conditionalExpression = $AST->whereClause->conditionalExpression;
        if (isset($conditionalExpression->conditionalPrimary)) {
            $conditionalExpression = $conditionalExpression->conditionalPrimary;
        }

        if ($conditionalExpression instanceof ConditionalPrimary) {
            // we have request with only one where condition
            $expression = $conditionalExpression->simpleConditionalExpression;
            if (isset($expression->subselect)
                && $expression->subselect instanceof Subselect
            ) {
                $this->processSelect($expression->subselect, $context, $em);
                $this->processJoins($expression->subselect, $context, $em);
            }
        } else {
            // we have request with many where conditions
            if (isset($conditionalExpression->conditionalFactors)) {
                $factors = $conditionalExpression->conditionalFactors;
            } else {
                $factors = $conditionalExpression->conditionalTerms;
            }
            foreach ($factors as $factorId => $expression) {
                if ($expression instanceof ConditionalPrimary) {
                    $conditionalExpression = $expression->conditionalExpression;
                    if (isset($conditionalExpression->simpleConditionalExpression->subselect)
                        && $conditionalExpression->simpleConditionalExpression->subselect instanceof Subselect
                    ) {
                        $this->processSelect(
                            $conditionalExpression->simpleConditionalExpression->subselect,
                            $context,
                            $em
                        );
                        $this->processJoins(
                            $conditionalExpression->simpleConditionalExpression->subselect,
                            $context,
                            $em
                        );
                    }
                }
                if (isset($expression->simpleConditionalExpression->subselect)
                    && $expression->simpleConditionalExpression->subselect instanceof Subselect
                ) {
                    $this->processSelect($expression->simpleConditionalExpression->subselect, $context, $em);
                    $this->processJoins($expression->simpleConditionalExpression->subselect, $context, $em);
                }
            }
        }
    }

    /**
     * @param Node $AST
     * @param AccessRuleWalkerContext $context
     * @param ObjectManager $em
     */
    private function processSelect(Node $AST, AccessRuleWalkerContext $context, ObjectManager $em): void
    {
        $fromClause = $AST instanceof SelectStatement ? $AST->fromClause : $AST->subselectFromClause;
        foreach ($fromClause->identificationVariableDeclarations as $identificationVariableDeclaration) {
            $rangeVariableDeclaration = $identificationVariableDeclaration->rangeVariableDeclaration;

            $entityName = $rangeVariableDeclaration->abstractSchemaName;
            $alias = $rangeVariableDeclaration->aliasIdentificationVariable;

            $criteria = $this->getEntityAccessRulesCriteria($entityName, $context, $alias, true);

            $criteriaExpression = $criteria->getExpression();
            if ($criteriaExpression) {
                $visitor = new AstVisitor();
                $visitor->setAlias($alias);
                $visitor->setQueryComponents($this->queryComponents);
                $visitor->setObjectManager($em);

                $whereExpression = null === $AST->whereClause ? null : $AST->whereClause->conditionalExpression;
                $conditionalExpression = $this->mergeExpressions(
                    $visitor->dispatch($criteria->getExpression()),
                    $whereExpression
                );
                $this->queryComponents = $visitor->getQueryComponents();

                if (null === $AST->whereClause) {
                    $AST->whereClause = new WhereClause($conditionalExpression);
                } else {
                    $AST->whereClause->conditionalExpression = $conditionalExpression;
                }
            }
        }
    }

    /**
     * @param SelectStatement|Subselect $select
     * @param AccessRuleWalkerContext $context
     * @param ObjectManager $em
     */
    private function processJoins($select, AccessRuleWalkerContext $context, ObjectManager $em): void
    {
        $fromClause = $select instanceof SelectStatement ? $select->fromClause : $select->subselectFromClause;
        foreach ($fromClause->identificationVariableDeclarations as $fromKey => $identificationVariableDeclaration) {
            if (!empty($identificationVariableDeclaration->joins)) {

                /** @var $join Join */
                foreach ($identificationVariableDeclaration->joins as $joinKey => $join) {
                    $joinAlias = $join->joinAssociationDeclaration->aliasIdentificationVariable;

                    $parentClass = null;
                    $parentField = null;

                    //check if join in format "join some_table on (some_table.id = parent_table.id)"
                    if ($join->joinAssociationDeclaration instanceof RangeVariableDeclaration) {
                        $joinEntity = $join->joinAssociationDeclaration->abstractSchemaName;
                    } else {
                        $joinQueryComponent = $this->queryComponents[$joinAlias];
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
                        $visitor = new AstVisitor();
                        $visitor->setAlias($joinAlias);
                        $visitor->setQueryComponents($this->queryComponents);
                        $visitor->setObjectManager($em);

                        $join->conditionalExpression = $this->mergeExpressions(
                            $visitor->dispatch($criteria->getExpression()),
                            $join->conditionalExpression
                        );
                        $this->queryComponents = $visitor->getQueryComponents();
                    }
                }
            }
        }
    }

    /**
     * Creates the context for subselects.
     *
     * @param AccessRuleWalkerContext $context
     *
     * @return AccessRuleWalkerContext
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
     *
     * @param string $entityClass
     * @param AccessRuleWalkerContext $context
     * @param string $alias
     * @param bool $isRoot
     * @param array $options
     *
     * @return Criteria
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

        $this->getChainAccessRule($context->getContainer())->process($criteria);

        return $criteria;
    }

    /**
     * @param ConditionalTerm|ConditionalPrimary $ruleExpression
     * @param ConditionalTerm|ConditionalPrimary|Node null $queryExpression
     *
     * @return ConditionalTerm|ConditionalPrimary
     */
    private function mergeExpressions($ruleExpression, $queryExpression = null)
    {
        if (null === $queryExpression) {
            return $ruleExpression;
        }

        return new ConditionalTerm(
            array_merge(
                $this->getConditionalFactors($queryExpression),
                $this->getConditionalFactors($ruleExpression)
            )
        );
    }

    /**
     * @param Node $queryExpression
     *
     * @return ConditionalFactor[]
     */
    private function getConditionalFactors(Node $queryExpression): array
    {
        // in case if $queryExpression is some kind if comparison expression
        // - wrap it with ConditionalPrimary expression
        if (!($queryExpression instanceof ConditionalPrimary) && !($queryExpression instanceof ConditionalTerm)) {
            $conditionalExpressionPrimary = new ConditionalPrimary();
            $conditionalExpressionPrimary->conditionalExpression = $queryExpression;
            $queryExpression = $conditionalExpressionPrimary;
        }

        return $queryExpression instanceof ConditionalPrimary
            ? [$queryExpression]
            : $queryExpression->conditionalFactors;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return ChainAccessRule
     */
    private function getChainAccessRule(ContainerInterface $container): ChainAccessRule
    {
        if (!$this->chainAccessRule) {
            $this->chainAccessRule = $container->get('oro_security.access_rule.chain_access_rule');
        }

        return $this->chainAccessRule;
    }

    /**
     * Collects existing array query components to array of objects.
     */
    private function collectQueryComponents(): void
    {
        $result = [];
        foreach ($this->getQueryComponents() as $alias => $component) {
            $componentObject = QueryComponent::fromArray($component);
            if (null !== $componentObject) {
                $result[$alias] = QueryComponent::fromArray($component);
            }
        }

        $this->queryComponents = $result;
    }

    /**
     * Adds new query components to existing query components.
     *
     * @throws Query\QueryException
     */
    private function applyNewQueryComponents(): void
    {
        foreach ($this->queryComponents as $alias => $queryComponent) {
            if (!array_key_exists($alias, $this->getQueryComponents())) {
                $this->setQueryComponent($alias, $queryComponent->toArray());
            }
        }
    }
}
