<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Doctrine\ORM\QueryBuilder;

/**
 * This class makes possible to apply subquery with limit to query builder.
 * Because of reason that it is not possible on DQL. It applies "hook" to passed query builder.
 * And this hook will be processed by SqlWalker which actually adds limit to row sql subquery.
 */
class SubQueryLimitHelper
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param int $limit
     * @param string $fieldName Identifier which will be used in Select part
     * @return QueryBuilder
     */
    public function setLimit(QueryBuilder $queryBuilder, $limit, $fieldName)
    {
        $config = $queryBuilder->getEntityManager()->getConfiguration();
        $hooks = $config->getDefaultQueryHint(SubQueryLimitOutputResultModifier::WALKER_HOOK_LIMIT_KEY) ?: [];
        $limits = $config->getDefaultQueryHint(SubQueryLimitOutputResultModifier::WALKER_HOOK_LIMIT_VALUE) ?: [];
        $fieldNames = $config->getDefaultQueryHint(SubQueryLimitOutputResultModifier::WALKER_HOOK_LIMIT_ID) ?: [];

        $hook = sprintf(
            '\'%1$s\' = \'%1$s\'',
            SubQueryLimitOutputResultModifier::WALKER_HOOK_LIMIT_KEY . \count($hooks)
        );
        $queryBuilder->andWhere($hook);

        $hooks[] = $hook;
        $limits[] = $limit;
        $fieldNames[] = $fieldName;
        $config->setDefaultQueryHint(SubQueryLimitOutputResultModifier::WALKER_HOOK_LIMIT_KEY, $hooks);
        $config->setDefaultQueryHint(SubQueryLimitOutputResultModifier::WALKER_HOOK_LIMIT_VALUE, $limits);
        $config->setDefaultQueryHint(SubQueryLimitOutputResultModifier::WALKER_HOOK_LIMIT_ID, $fieldNames);

        return $queryBuilder;
    }
}
