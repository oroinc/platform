<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Component\DoctrineUtils\ORM\Walker\AbstractOutputResultModifier;

/**
 * Dynamically applies limit to sub-query which is "hooked" by {@see SubQueryLimitHelper}.
 */
class SubQueryLimitOutputResultModifier extends AbstractOutputResultModifier
{
    public const WALKER_HOOK_LIMIT_KEY = 'walker_hook_for_limit';

    #[\Override]
    public function walkSubselect($subselect, string $result)
    {
        $hooks = $this->getQuery()->getHint(self::WALKER_HOOK_LIMIT_KEY);
        if (!$hooks) {
            return $result;
        }

        foreach ($hooks as [$hook, $limit, $fieldName]) {
            if (stripos($result, $hook) !== false) {
                $result = sprintf(
                    'SELECT %2$s.%3$s FROM (%1$s LIMIT %4$d) %2$s',
                    str_replace($hook, '1=1', $result),
                    'customTableAlias',
                    $fieldName,
                    $limit
                );
                break;
            }
        }

        return $result;
    }
}
