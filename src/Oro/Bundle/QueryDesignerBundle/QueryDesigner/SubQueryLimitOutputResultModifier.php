<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Component\DoctrineUtils\ORM\Walker\AbstractOutputResultModifier;

/**
 * Dynamically applies limit to sub-query which is "hooked" by SubQueryLimitHelper
 */
class SubQueryLimitOutputResultModifier extends AbstractOutputResultModifier
{
    public const WALKER_HOOK_LIMIT_KEY = 'walker_hook_for_limit';
    public const WALKER_HOOK_LIMIT_VALUE = 'walker_hook_limit_value';
    public const WALKER_HOOK_LIMIT_ID = 'walker_hook_limit_id';

    /**
     * {@inheritdoc}
     */
    public function walkSubselect($subselect, string $result)
    {
        $hookIdentifier = $this->getQuery()->getHint(self::WALKER_HOOK_LIMIT_KEY);
        $limitValue = $this->getQuery()->getHint(self::WALKER_HOOK_LIMIT_VALUE);
        $identifierField = $this->getQuery()->getHint(self::WALKER_HOOK_LIMIT_ID);

        if ($identifierField && $hookIdentifier && $limitValue && stripos($result, $hookIdentifier) !== false) {
            // Remove hook condition from sql
            $result = str_ireplace($hookIdentifier, '1=1', $result);
            $result = "SELECT customTableAlias.$identifierField FROM ($result LIMIT $limitValue) customTableAlias";
        }

        return $result;
    }
}
