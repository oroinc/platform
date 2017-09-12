<?php

namespace Oro\Component\DoctrineUtils\ORM;

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
        $hookIdentifier = $this->getQuery()->getHint(self::$walkerHookUnionKey);
        $unionQuery = $this->getQuery()->getHint(self::$walkerHookUnionValue);
        if ($hookIdentifier && $unionQuery && stripos($sql, $hookIdentifier) !== false) {
            $sql = str_ireplace($hookIdentifier, ' UNION '. $unionQuery, $sql);
        }

        return $sql;
    }
}
