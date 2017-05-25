<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;

/**
 * @TODO: This walker should be removed according to logic #BAP-13404
 * Dynamicly applies limit to subquery which is "hooked" by SubQueryLimitHelper
 */
class SqlWalker extends TranslationWalker
{
    const WALKER_HOOK_LIMIT_KEY = 'walker_hook_for_limit';
    const WALKER_HOOK_LIMIT_VALUE = 'walker_hook_limit_value';
    const WALKER_HOOK_LIMIT_ID = 'walker_hook_limit_id';

    /**
     * {@inheritdoc}
     */
    public function walkSubselect($subselect)
    {
        $sql = parent::walkSubselect($subselect);
        $hookIdentifier = $this->getQuery()->getHint(self::WALKER_HOOK_LIMIT_KEY);
        $limitValue = $this->getQuery()->getHint(self::WALKER_HOOK_LIMIT_VALUE);
        $identifierField = $this->getQuery()->getHint(self::WALKER_HOOK_LIMIT_ID);

        if ($identifierField && $hookIdentifier && $limitValue && stripos($sql, $hookIdentifier) !== false) {
            // Remove hook condition from sql
            $sql = str_ireplace($hookIdentifier, '1<>0', $sql);
            $sql = "SELECT customTableAlias.$identifierField FROM ($sql LIMIT $limitValue) customTableAlias";
        }

        return $sql;
    }
}
