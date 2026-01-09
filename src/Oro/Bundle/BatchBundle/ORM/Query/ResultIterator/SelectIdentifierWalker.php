<?php

namespace Oro\Bundle\BatchBundle\ORM\Query\ResultIterator;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\TreeWalkerAdapter;

/**
 * Changes the selectClause of the AST to select row Identifier only.
 */
class SelectIdentifierWalker extends TreeWalkerAdapter
{
    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    #[\Override]
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $this->validate($AST);

        // Get the root entity and alias from the AST fromClause
        $queryComponents = $this->getQueryComponents();
        $from = $AST->fromClause->identificationVariableDeclarations;
        if (count($from) !== 1) {
            throw new \LogicException('There is more then 1 From clause');
        }
        $fromRoot = reset($from);
        $rootAlias = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
        $rootClass = $queryComponents[$rootAlias]['metadata'];
        $identifierFieldName = $rootClass->getSingleIdentifierFieldName();

        $pathType = AST\PathExpression::TYPE_STATE_FIELD;
        if (isset($rootClass->associationMappings[$identifierFieldName])) {
            $pathType = AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;
        }

        $pathExpression = new AST\PathExpression(
            AST\PathExpression::TYPE_STATE_FIELD | AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            $rootAlias,
            $identifierFieldName
        );
        $pathExpression->type = $pathType;

        // Check possible primary key inconsistency cause by 'Group' sql clause
        $usedInGroupBy = false;
        if (isset($AST->groupByClause)) {
            foreach ($AST->groupByClause->groupByItems as $groupBy) {
                if ($groupBy instanceof AST\PathExpression) {
                    if ($groupBy->identificationVariable === $rootAlias && $groupBy->field === $identifierFieldName) {
                        $usedInGroupBy = true;
                        break;
                    }
                }
            }
            if (!$usedInGroupBy) {
                throw new \LogicException(
                    "Detected Primary key ({$rootAlias}.{$identifierFieldName}) should be used in Group By clause"
                );
            }
        }

        $AST->selectClause->selectExpressions = [new AST\SelectExpression($pathExpression, null)];

        // GROUP BY handling:
        // - If GROUP BY exists: REPLACE it with just identifier (required because SELECT now only contains id,
        //   so GROUP BY cannot reference columns that are no longer in SELECT).
        // - If no GROUP BY but has JOINs: add GROUP BY to eliminate duplicates.
        // - If no GROUP BY and no JOINs: skip GROUP BY (selecting by PK already guarantees unique rows).
        if (isset($AST->groupByClause) || !empty($fromRoot->joins)) {
            $AST->groupByClause = new AST\GroupByClause([$pathExpression]);
        }
    }

    /**
     * Validates that walker could be used on a Query
     * Queries that include Order By on a joined to-many field are not supported on PostgreSQL
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function validate(AST\SelectStatement $AST)
    {
        $platform = $this->_getQuery()->getEntityManager()->getConnection()->getDatabasePlatform();
        if (!$platform instanceof PostgreSqlPlatform) {
            return;
        }

        $queryComponents = $this->getQueryComponents();
        $query = $this->_getQuery();
        $from = $AST->fromClause->identificationVariableDeclarations;
        $fromRoot = reset($from);

        if ($query instanceof Query && $AST->orderByClause && count($fromRoot->joins)) {
            foreach ($AST->orderByClause->orderByItems as $orderByItem) {
                $expression = $orderByItem->expression;
                if (
                    $orderByItem->expression instanceof AST\PathExpression
                    && isset($queryComponents[$expression->identificationVariable])
                ) {
                    $queryComponent = $queryComponents[$expression->identificationVariable];
                    if (
                        isset($queryComponent['parent'])
                        && $queryComponent['relation']['type'] & ClassMetadataInfo::TO_MANY
                    ) {
                        throw new \LogicException('Query contains Order By joined to-many field');
                    }
                }
            }
        }
    }
}
