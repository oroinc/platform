<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\ORM\Query;

/**
 * Trait can be used to add UNION query to SQL string
 * that can be constructed after walking down AST nodes
 */
trait HookUnionTrait
{
    /**
     * @var string
     */
    public static $walkerHookUnionKey = 'walker_hook_union';

    /**
     * @var string
     */
    public static $walkerHookUnionValue = 'walker_hook_union_value';

    /**
     * @param string $sql
     *
     * @return string
     */
    public function hookUnion($sql)
    {
        /** @var Query $query */
        $query = $this->getQuery();
        $hookIdentifier = $query->getHint(self::$walkerHookUnionKey);
        $unionQuery = $query->getHint(self::$walkerHookUnionValue);
        if ($hookIdentifier && $unionQuery && stripos($sql, $hookIdentifier) !== false) {
            $sql = str_ireplace($hookIdentifier, '', $sql).' UNION '. $unionQuery;
        }

        return $sql;
    }
}
