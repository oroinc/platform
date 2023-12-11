<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Component\DoctrineUtils\ORM\Walker\AbstractOutputResultModifier;

/**
 * Dynamically applies limit to sub-query which is "hooked" by {@see SubQueryLimitHelper}.
 */
class SubQueryLimitOutputResultModifier extends AbstractOutputResultModifier
{
    public const WALKER_HOOK_LIMIT_KEY = 'walker_hook_for_limit';
    public const WALKER_HOOK_LIMIT_VALUE = 'walker_hook_limit_value';
    public const WALKER_HOOK_LIMIT_ID = 'walker_hook_limit_id';

    /**
     * {@inheritDoc}
     */
    public function walkSubselect($subselect, string $result)
    {
        $hooks = $this->getQuery()->getHint(self::WALKER_HOOK_LIMIT_KEY);
        if (!$hooks) {
            return $result;
        }

        $limits = $this->getQuery()->getHint(self::WALKER_HOOK_LIMIT_VALUE);
        if (!$limits) {
            return $result;
        }

        $fieldNames = $this->getQuery()->getHint(self::WALKER_HOOK_LIMIT_ID);
        if (!$fieldNames) {
            return $result;
        }

        foreach ($hooks as $i => $hook) {
            if (stripos($result, $hook) !== false) {
                $result = sprintf(
                    'SELECT %2$s.%3$s FROM (%1$s LIMIT %4$d) %2$s',
                    str_replace($hook, '1=1', $result),
                    'customTableAlias',
                    $fieldNames[$i],
                    $limits[$i]
                );
                break;
            }
        }

        return $result;
    }
}
