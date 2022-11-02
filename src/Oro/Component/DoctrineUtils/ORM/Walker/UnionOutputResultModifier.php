<?php

namespace Oro\Component\DoctrineUtils\ORM\Walker;

/**
 * Add UNION SQL statement to query
 */
class UnionOutputResultModifier extends AbstractOutputResultModifier
{
    public const HINT_UNION_KEY = 'walker_hook_union';
    public const HINT_UNION_VALUE = 'walker_hook_union_value';

    /**
     * {@inheritdoc}
     */
    public function walkSubselect($subselect, string $result)
    {
        $query = $this->getQuery();
        if (!$query->hasHint(self::HINT_UNION_KEY) || !$query->hasHint(self::HINT_UNION_VALUE)) {
            return $result;
        }

        $hookIdentifier = $query->getHint(self::HINT_UNION_KEY);
        $unionQuery = $query->getHint(self::HINT_UNION_VALUE);
        if ($hookIdentifier && $unionQuery && stripos($result, $hookIdentifier) !== false) {
            $result = str_ireplace($hookIdentifier, '', $result).' UNION '. $unionQuery;
        }

        return $result;
    }
}
